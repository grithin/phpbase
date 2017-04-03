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
*/

use \Grithin\Arrays;
use \Grithin\Tool;

use \Exception;
use \ArrayObject;

class Record implements \ArrayAccess, \IteratorAggregate {
	public $stored_record; # the last known record state from the getter
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

		if($this->options['initial_record']){
			$this->stored_record = $this->record = $this->options['initial_record'];
		}else{
			$this->stored_record = $this->record = $this->options['getter']($this);
		}

		if(!is_array($this->record)){
			throw new Exception('record must be an array');
		}
	}
	public function getIterator() {
		return new \ArrayIterator($this->record);
	}

	public function newKey($offset, $value){
		$this->notify(self::EVENT_NEW_KEY, [$offset=>$value]);
		$this->record[$offset] = null;
		$this[$offset] = $value;
	}
	public function offsetSet($offset, $value) {
		$this->update_local([$offset=>$value]);
	}

	public function offsetExists($offset) {
		return isset($this->record[$offset]);
	}

	public function offsetUnset($offset) {
		unset($this->record[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->record[$offset]) ? $this->record[$offset] : null;
	}


	static function static_json_decode($record){
		foreach($record as $k=>$v){
			if(substr($k, -6) == '__json'){
				$record[$k] = (array)json_decode($v, true);
			}
		}
		return $record;
	}
	static function static_json_encode($record){
		foreach($record as $k=>$v){
			if(substr($k, -6) == '__json'){
				$record[$k] = \Grithin\Tool::json_encode($v);
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
		return self::static_calculate_changes($target, $this->record);
	}
	# get ArrayObject representing diff between two arrays/objects, wherein items in $target are different than in $base, but not vice versa (existing $base items may not exist in $target)
	/* Examples
	self(['bob'=>'sue'], ['bob'=>'sue', 'bill'=>'joe']);
	#> {}
	self(['bob'=>'suesss', 'noes'=>'bees'], ['bob'=>'sue', 'bill'=>'joe']);
	#> {"bob": "suesss", "noes": "bees"}
	*/
	static function static_calculate_changes($target, $base){
		return new ArrayObject(array_udiff_assoc((array)$target, (array)$base, [self,'compare_record_column_values']));
	}

	static function compare_record_column_values($target, $base){
		if(Tool::is_scalar($target)){
			return ((string)$target === (string)$base) ? 0 : 1;
		}
		return count(array_udiff_assoc(Arrays::from($target), Arrays::from($base), [self,'compare_record_column_values']));
	}
	public function stored_record_calculate_changes(){

		return self::static_calculate_changes($this->record, $this->stored_record);
	}
	# alias `stored_record_calculate_changes`
	public function changes(){
		return call_user_func_array([$this, 'stored_record_calculate_changes'], func_get_args());
	}


	public $stored_record_previous;
	public function apply(){
		$this->stored_record_previous = $this->stored_record;
		$calculated_changes = $this->stored_record_calculate_changes();
		if(count($calculated_changes)){
			$this->notify(self::EVENT_UPDATE_BEFORE, $calculated_changes);
			if(count($calculated_changes)){ # may have been mutated to nothing
				$this->stored_record = $this->record = $this->options['setter']($this, $calculated_changes);
				$this->notify(self::EVENT_UPDATE_AFTER, $calculated_changes);
			}
		}
		return $changes;
	}

	public $record_previous; # the $this->record prior to changes; potentially used by event handlers interested in the previous unsaved changes
	public function update_local($changes){
		$this->record_previous = $this->record;
		$calculated_changes = self::static_calculate_changes($changes, $this->record);
		if(count($calculated_changes)){
			$this->notify(self::EVENT_CHANGE_BEFORE, $calculated_changes);
			self::static_calculate_changes($calculated_changes, $this->record);
			if(count($calculated_changes)){ # may have been mutated to nothing
				$this->record = Arrays::merge($this->record, $calculated_changes);
				$this->notify(self::EVENT_CHANGE_AFTER, $calculated_changes);
			}
		}
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

