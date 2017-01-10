<?
namespace Grithin;
/* About
Intended to be a observable that matches a database record.

Similar to SplSubject, but because SplSubject uses pointless SplObserver, SplSubject is not imlemented
*/


class Record implements \ArrayAccess{
	public $stored_record; # the last known record state from the getter
	public $record; # the current record state, with potential changes

	/* params

		getter: < function(identifier, this, refresh) > < refresh indicates whether to not use cache (ex, some other part of code has memoized the record ) >
		setter: < function(changes, this) returns record >

		options:[initial_record: < used instead of initially calling getter >]

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
			throw new \Exception('record must be an array');
		}
	}

	public function offsetSet($offset, $value) {
		if(is_null($offset)){
			throw new \Exception('can not create new key on record');
		} else {
			$this->record[$offset] = $value;
		}
		$this->notify('local_change', [$offset=>$value]);
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


	static function static_decode_json($record){
		foreach($record as $k=>$v){
			if(substr($k, -6) == '__json'){
				$record[$k] = (array)json_decode($v, true);
			}
		}
		return $record;
	}
	static function static_encode_json($record){
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

	public function notify($type, $details=[]) {
		foreach ($this->observers as $observer) {
			$return = $observer($this, $type, $details);
			if($return === false){
				return false;
			}
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
			throw new \Exception('record must be an array');
		}

		$changes = $this->calculate_changes($previous);
		$this->notify('refreshed', $changes);
		return $changes;
	}
	# does not apply changes, just calculates potential
	public function calculate_changes($target){
		return self::static_calculate_changes($target, $this->record);
	}
	static function static_calculate_changes($target, $base){
		return array_diff_assoc($target, $base);
	}
	public function stored_record_calculate_changes(){
		return self::static_calculate_changes($this->record, $this->stored_record);
	}


	public $stored_record_previous;
	public function apply(){
		$this->stored_record_previous = $this->stored_record;

		$changes = $this->stored_record_calculate_changes();
		$this->notify('before_update', $changes);
		$changes = $this->stored_record_calculate_changes(); # in case observer changed record

		$this->stored_record = $this->record = $this->options['setter']($this, $changes);
		$changes = self::static_calculate_changes($this->record, $this->stored_record_previous); # extract changes from setter return, which might be different than changes sent to setter
		$this->notify('after_updated', $changes);
		return $changes;
	}

	public $record_previous; # the $this->record prior to changes
	public function update($changes){
		$this->record_previous = $this->record;
		$this->record = array_merge($this->record, $changes);
		return $this->apply();
	}
	public function jsonSerialize(){
		return $this->record;
	}
}

