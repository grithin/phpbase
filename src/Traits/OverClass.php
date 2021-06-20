<?php
namespace Grithin\Traits;
use Grithin\Debug;

/**
The pattern: Pass in type to over-class, and henceforth over-class uses instance of under-class mapped by type.  Used as an abstraction, used instead of factory (b/c more elegant), and b/c can't monkey patch.
@note the under class should have a public $_success to indicate whether to try next preference (on case of $_success = false)
@note __call doesn't take arguments by reference, so don't applly to classes requiring reference args
*/
trait OverClass{
	/// must define on traited class static $types;
	public $under;
	function __construct($typePreferences=null){
		call_user_func_array([$this,'load'],func_get_args());
	}
	/**
		@param	$typePreferences [type,...]
	*/
	function load($typePreferences=null){
		$this->typePreferences = $typePreferences ? (array)$typePreferences : $this->typePreferences;
		foreach((array)$this->typePreferences as $type){
			if(self::$types[$type]){
				$class = new \ReflectionClass(self::$types[$type]);
				$this->under = $class->newInstanceArgs(array_slice(func_get_args(),1));
				if($this->under->_success){
					$this->type = $type;
					break;
				}
			}
		}
		if(!$this->under){
			Debug::toss(__class__.' Failed to get under with preferences: '.Debug::toString($this->typePreferences));
		}
	}
	function __call($fnName,$args){
		if(method_exists($this,$fnName)){
			return call_user_func_array(array($this,$fnName),$args);
		}elseif(method_exists($this->under,$fnName)){
			return call_user_func_array(array($this->under,$fnName),$args);
		}elseif(method_exists($this->under,'__call')){//under may have it's own __call handling
			return call_user_func_array(array($this->under,$fnName),$args);
		}
		Debug::toss(__class__.' Method not found: '.$fnName);
	}
}