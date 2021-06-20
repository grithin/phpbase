<?php
namespace Grithin\Traits;
use Grithin\Debug;
use Grithin\Traits\SingletonDefault;

/**
The pattern: Load resource when used, not when class instantiated.  Calling a method, or getting an non-set property will cause a load.
*/
///Singleton default lazy loader
trait SDLL{
	use SingletonDefault;
	public $loaded = false;
	public $constructArgs = array();
	function __construct(){
		$this->constructArgs = func_get_args();
	}
	function &__get($name){
		$this->load_once();
		return $this->$name;
	}
	function __call($fnName,$args){
		$this->load_once();
		return $this->__testCall($fnName,$args);
	}
	abstract function load();
	function load_once(){
		//load if not loaded
		if(!$this->loaded){
			call_user_func_array(array($this,'load'),(array)$this->constructArgs);
			$this->loaded = true;
		}
	}
}