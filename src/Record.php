<?
# See doc/Record.md

namespace Grithin;

use \Grithin\Arrays;
use \Grithin\Tool;
use \Grithin\SubRecordHolder;

use \Exception;
use \ArrayObject;

class Record implements \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable {
	public $stored_record; # the last known record state from the getter
	public $local_record; # the current record state, with potential changes
	public $record; # the current record state, with potential changes

	const EVENT_UPDATE = 1;
	const EVENT_CHANGE = 2;
	const EVENT_CHANGE_BEFORE = 4;
	const EVENT_CHANGE_AFTER = 8;
	const EVENT_UPDATE_BEFORE = 16;
	const EVENT_UPDATE_AFTER = 32;
	const EVENT_NEW_KEY = 64; # ?
	const EVENT_REFRESH = 128;

	# By default, the diff function will turn objects into arrays.  This is not desired for something like a Time object, so, instead, use a equals comparison comparer
	public $diff_options = ['object_comparer'=>['\Grithin\Arrays', 'diff_comparer_equals']];


	/* params

		getter: < function(identifier, this, refresh) > < refresh indicates whether to not use cache (ex, some other part of code has memoized the record ) >
		setter: < function(changes, this) returns record >

		options: [ initial_record: < used instead of initially calling getter > ]

	*/
	public function __construct($identifier, $getter, $setter, $options=[]) {
		$this->observers = new \SplObjectStorage();

		$this->identifier = $identifier;

		$this->options = array_merge($options, ['getter'=>$getter, 'setter'=>$setter]);

		if(array_key_exists('initial_record', $this->options)){
			$this->stored_record = $this->record = $this->options['initial_record'];
		}else{
			$this->stored_record = $this->record = $this->options['getter']($this);
		}

		if(!is_array($this->record)){
			throw new Exception('record must be an array');
		}
	}
	public function count(){
		return count($this->record);
	}
	public function getIterator() {
		return new \ArrayIterator($this->record);
	}

	/* update stored and local without notifying listeners */
	public function bypass_set($changes){
		$this->record = Arrays::replace($this->record, $changes);
		$this->local_record = $this->record;
		$this->stored_record = Arrays::replace($this->stored_record, $changes);
	}
	public function offsetSet($offset, $value) {
		$this->update_local([$offset=>$value]);
	}

	public function offsetExists($offset) {
		return isset($this->record[$offset]);
	}

	public function offsetUnset($offset) {
		$this->update_local([$offset=>(new \Grithin\MissingValue)]);
	}

	public function offsetGet($offset) {
		if(is_array($this->record[$offset])){
			return new SubRecordHolder($this, $offset, $this->record[$offset]);
		}

		return $this->record[$offset];
	}

	# only json encode non-null values
	static function static_json_decode_value($v){
		if($v === null){
			return null;
		}
		return Tool::json_decode($v, true);
	}
	static function static_json_decode($record){
		foreach($record as $k=>$v){
			if(substr($k, -6) == '__json'){
				$record[$k] = self::static_json_decode_value($v);
			}
		}
		return $record;
	}
	/*
	JSON column will only ever store something that is non-scalar (it would be pointless otherwise)
	*/
	static function static_json_encode_value($v){
		if(Tool::is_scalar($v)){
			return null;
		}
		return Tool::json_encode($v);
	}
	static function static_json_encode($record){
		foreach($record as $k=>$v){
			if(substr($k, -6) == '__json'){
				$record[$k] = self::static_json_encode_value($v);
			}
		}
		return $record;
	}

	public $observers; # observe all events
	public function attach($observer) {
		$this->observers->attach($observer);
	}

	public function detach($observer) {
		$this->observers->detach($observer);
	}
	# return an callback for use as an observer than only responds to particular events
	static function event_callback_wrap($event, $observer){
		if(is_int($event)){
			return function($that, $type, $details) use ($event, $observer){
				if(is_int($type) && #< ensure this is a numbered event
					$type & $event) #< filter fired event type against the type of listener
				{
					return $observer($that, $details);
				}
			};
		}else{
			return function($that, $type, $details) use ($event, $observer){
				if(!is_int($type) && #< ensure this is a numbered event
					$type == $event) #< filter fired event type against the type of listener
				{
					return $observer($that, $details);
				}
			};
		}

	}
	# wrapper for observers single-event-dedicated observers
	public function before_change($observer){
		$wrapped = $this->event_callback_wrap(self::EVENT_CHANGE_BEFORE, $observer);
		$this->attach($wrapped);
		return $wrapped;
	}
	public function after_change($observer){
		$wrapped = $this->event_callback_wrap(self::EVENT_CHANGE_AFTER, $observer);
		$this->attach($wrapped);
		return $wrapped;
	}
	public function before_update($observer){
		$wrapped = $this->event_callback_wrap(self::EVENT_UPDATE_BEFORE, $observer);
		$this->attach($wrapped);
		return $wrapped;
	}
	public function after_update($observer){
		$wrapped = $this->event_callback_wrap(self::EVENT_UPDATE_AFTER, $observer);
		$this->attach($wrapped);
		return $wrapped;
	}

	public function notify($type, $details=[]) {
		foreach ($this->observers as $observer) {
			$observer($this, $type, $details);
		}
	}
	# create a observer that only listens to one event
	public function single_event_observer($observer, $event){
		return function($incoming_event, $details) use ($observer, $event){
			if($incoming_event == $event){
				$observer($details);
			}
		};
	}

	# re-pulls record and returns differences, if any
	public function refresh(){
		$previous = $this->record;
		$this->record = $this->stored_record = $this->options['getter']($this);

		if(!is_array($this->record)){
			throw new Exception('record must be an array');
		}

		$changes = $this->calculate_changes($previous);
		$this->notify(self::EVENT_REFRESH, $changes);
		return $changes;
	}
	# does not apply changes, just calculates potential
	public function calculate_changes($target){
		return Collection::diff($target, $this->record, $this->diff_options);
	}

	public function stored_record_calculate_changes(){
		return Collection::diff($this->record, $this->stored_record, $this->diff_options);
	}
	# alias `stored_record_calculate_changes`
	public function changes(){
		return call_user_func_array([$this, 'stored_record_calculate_changes'], func_get_args());
	}

	public $stored_record_previous;
	public function apply(){
		$this->stored_record_previous = $this->stored_record;
		$diff = new ArrayObject(Collection::diff($this->record, $this->stored_record, $this->diff_options));
		if(count($diff)){
			$this->notify(self::EVENT_UPDATE_BEFORE, $diff);
			if(count($diff)){ # may have been mutated to nothing
				$this->stored_record = $this->record = $this->options['setter']($this, $diff);
				$this->notify(self::EVENT_UPDATE_AFTER, $diff);
			}
		}
		return $changes;
	}

	public function update_local($changes){
		$new_record = Arrays::replace($this->record, $changes);
		return $this->replace_local($new_record);
	}

	public $record_previous; # the $this->record prior to changes; potentially used by event handlers interested in the previous unsaved changes
	public function replace_local($new_record){
		$this->record_previous = $this->record;
		$diff = new ArrayObject(Collection::diff($new_record, $this->record, $this->diff_options));
		if(count($diff)){
			$this->notify(self::EVENT_CHANGE_BEFORE, $diff);
			if(count($diff)){ # may have been mutated to nothing
				$this->record = Collection::diff_apply($this->record, $diff);
				$this->notify(self::EVENT_CHANGE_AFTER, $diff);
			}
		}
	}

	# replace update the record to be the new
	public function replace($new_record){
		$this->replace_local($new_record);
		$changes = $this->apply();
		return $changes;
	}
	public function update($changes){
		$this->update_local($changes);
		$changes = $this->apply();
		return $changes;
	}
	public function jsonSerialize(){
		return $this->record;
	}
	public function __toArray(){ # hopefully PHP adds this at some point
		return $this->record;
	}
}
