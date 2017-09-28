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

class SubRecordHolder implements \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable {
	public $record; # the current record state, with potential changes


	/* params

		Record: < Record instance >,
		path_prefix: < the position of this sub record >
		record: the data representing this subrecord

	*/
	public function __construct($Record, $path_prefix, $record) {
		$this->Record = $Record;
		$this->path_prefix = $path_prefix;
		$this->record = $record;
	}
	public function count(){
		return count($this->record);
	}
	public function getIterator() {
		return new \ArrayIterator($this->record);
	}
	public function offsetSet($offset, $value) {
		if($offset === null && Arrays::is_numeric($this->record)){ #< in the case of something like `$bob[] = 'sue'`
			$this->record[] = $value;
		}else{ #< in all other cases, like `$bob['monkey'] = 'sue'`
			$this->record[$offset] = $value;
		}

		$subrecord_at_path = Arrays::set($this->path_prefix, $this->record, []);

		# send absolute path changes to primary Record holder
		$this->Record->update_local($subrecord_at_path);
		# get the result at the relative path representing this holder
		$this->record = Arrays::get($this->Record->record, $this->path_prefix);
	}

	public function offsetExists($offset) {
		return isset($this->record[$offset]);
	}

	public function offsetUnset($offset) {
		#$this->record[$offset] = new \Grithin\MissingValue;
		# does this trigger a change event?
		unset($this->record[$offset]);

		$subrecord_at_path = Arrays::set($this->path_prefix, $this->record, []);

		# send absolute path changes to primary Record holder
		$this->Record->update_local($subrecord_at_path);
		# get the result at the relative path representing this holder
		$this->record = Arrays::get($this->Record->record, $this->path_prefix);
	}

	public function offsetGet($offset) {
		if(is_array($this->record[$offset])){
			return new static($this->Record, $this->path_prefix.'.'.$offset, $this->record[$offset]);
		}
		return $this->record[$offset];
	}

	public function jsonSerialize(){
		return $this->record;
	}
	public function __toArray(){ # hopefully PHP adds this at some point
		return $this->record;
	}
}
