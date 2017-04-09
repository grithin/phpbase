<?
namespace Grithin;
/* About
Intended to be a observable that matches a database record, allowing the handling of a record like an array, while allowing listeners to react to change events.
-	EVENT_CHANGE_BEFORE
-	EVENT_CHANGE_AFTER | EVENT_CHANGE
-	EVENT_UPDATE_BEFORE
-	EVENT_UPDATE_AFTER | EVENT_UPDATE



Update and Change events will fire if there were changes, otherwise they won't.

Listener can mutate record upon a `update, before` event prior to the setter being called.  Listener can also throw an exception at this point, but the exception is not handled inside this class

See Grithin\phpdb\StandardRecord for example use

Similar to SplSubject, but because SplSubject uses pointless SplObserver, SplSubject is not imlemented

@important:	don't use unset().  It is not detected as a change.  Consequently, if you want to unset a child of a non-scalar, re-set the entire toplevel value. Ex:
	-	$record['json'] = ['bob'=>'sue', 'phil'=>'jones'];
	-	$record['json'] = ['bob'=>'sue']
	instead of
	-	unset($record['json']['phil'])
*/

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
	const EVENT_NEW_KEY = 64;


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
		$this->record = Arrays::merge($this->record, $changes);
		$this->local_record = $this->record;
		$this->stored_record = Arrays::merge($this->stored_record, $changes);
	}
	public function offsetSet($offset, $value) {
		$this->update_local_with_changes([$offset=>$value]);
	}

	public function offsetExists($offset) {
		return isset($this->record[$offset]);
	}

	public function offsetUnset($offset) {
		$this->update_local_with_changes([$offset=>(new \Grithin\MissingValue)]);
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
	# return an callback for use as an observer than only response to particular events
	static function event_callback_wrap($event, $observer){
		return function($that, $type, $details) use ($event, $observer){
			if($type & $event){
				return $observer($that, $details);
			}
		};
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
		$this->notify('refreshed', $changes);
		return $changes;
	}
	# does not apply changes, just calculates potential
	public function calculate_changes($target){
		return Arrays::diff($target, $this->record);
	}

	public function stored_record_calculate_changes(){
		return Arrays::diff($this->record, $this->stored_record);
	}
	# alias `stored_record_calculate_changes`
	public function changes(){
		return call_user_func_array([$this, 'stored_record_calculate_changes'], func_get_args());
	}

	public $stored_record_previous;
	public function apply(){
		$this->stored_record_previous = $this->stored_record;
		$diff = new ArrayObject(Arrays::diff($this->record, $this->stored_record));
		if(count($diff)){
			$this->notify(self::EVENT_UPDATE_BEFORE, $diff);
			if(count($diff)){ # may have been mutated to nothing
				$this->stored_record = $this->record = $this->options['setter']($this, $diff);
				$this->notify(self::EVENT_UPDATE_AFTER, $diff);
			}
		}
		return $changes;
	}

	public function update_local_with_changes($changes){
		$new_record = Arrays::replace($this->record, $changes);
		return $this->update_local($new_record);
	}

	public $record_previous; # the $this->record prior to changes; potentially used by event handlers interested in the previous unsaved changes
	public function update_local($new_record){
		$this->record_previous = $this->record;
		$diff = new ArrayObject(Arrays::diff($new_record, $this->record));
		if(count($diff)){
			$this->notify(self::EVENT_CHANGE_BEFORE, $diff);
			if(count($diff)){ # may have been mutated to nothing
				$this->record = Arrays::diff_apply($this->record, $diff);
				$this->notify(self::EVENT_CHANGE_AFTER, $diff);
			}
		}
	}
	public function update($new_record){
		$this->update_local($new_record);
		$changes = $this->apply();
		return $changes;
	}
	public function update_with_changes($changes){
		$this->update_local_with_changes($changes);
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

