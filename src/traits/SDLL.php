<?
namespace Grithin;
use Grithin\Debug;
use Grithin\SingletonDefault;

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
		//load if not loaded
		if(!$this->loaded){
			call_user_func_array(array($this,'load'),(array)$this->constructArgs);
			$this->loaded = true;
		}
		return $this->$name;
	}
	function __call($fnName,$args){
		//load if not loaded
		if(!$this->loaded){
			call_user_func_array(array($this,'load'),(array)$this->constructArgs);
			$this->loaded = true;
		}
		return $this->__testCall($fnName,$args);
	}
	abstract function load();
}