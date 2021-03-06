<?php
namespace Grithin\Traits; # so named because \Grithin\Trait causes syntax error

/* Like ArrayObject, but allows use of properties
B/c get_object_vars() return is not configurable per object, and extending it would not result in an object that can be greated both like object and array (since get_object_vars() on ArrayObject does not return the arrayed items)
*/

trait ObjectAsArray{
	public function offsetSet($offset, $value) {
		$this->$offset = $value;
	}

	public function offsetExists($offset) {
		return isset($this->$offset);
	}

	public function offsetUnset($offset) {
		$this->offset = null;
	}

	public function offsetGet($offset) {
		return $this->$offset;
	}
	public function jsonSerialize(){
		return get_object_vars($this);
	}
	public function __toArray(){ # hopefully PHP adds this at some point
		return get_object_vars($this);
	}
	public function count(){
		return count(get_object_vars($this));
	}
	# wont work with references
	public function getIterator() {
		return new \ArrayIterator(get_object_vars($this));
	}
}