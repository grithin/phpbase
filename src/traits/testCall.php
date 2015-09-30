<?
namespace Grithin;
use Grithin\Debug;

///Makes failure of call_user_func_array on a overriden __call easier to read
trait testCall{
	function __call($fnName,$args){
		return $this->__testCall($fnName,$args);
	}

	function __testCall($fnName,$args){
		if(!method_exists($this,$fnName)){
			Debug::toss(get_called_class().' Method not found: '.$fnName);
		}
		return call_user_func_array(array($this,$fnName),$args);
	}
	function __methodExists($fnName){
		if(!method_exists($this,$fnName)){
			Debug::toss(get_called_class().' Method not found: '.$fnName);
		}
	}
}