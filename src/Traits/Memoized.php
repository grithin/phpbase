<?php
namespace Grithin\Traits;

/*
For handling static and instance based convenient memoizing (by __call and __callStatic function name matching).  Allows handling sub-memoize calls (`static_caller_requested_memoized`), and allows re-making (`static_call_and_memoize`).

Methods are intended to be overriden as desired (ex: change the memoize get functions to use redis instead of local copy)
*/

use Exception;

trait Memoized{

	#+	static functions {
	static $static_memoized_count = 0;
	static public function __callStatic($name, $arguments){
		if(substr($name,0,9) == 'memoized_'){
			self::$static_memoized_count++;
			$return =  self::static_get_memoized(substr($name,9), $arguments);
			self::$static_memoized_count--;
			return $return;
		}
		if(substr($name,0,8) == 'memoize_'){
			self::$static_memoized_count++;
			$return = self::static_call_and_memoize(substr($name,8), $arguments);
			self::$static_memoized_count--;
			return $return;
		}
		if(!method_exists(__CLASS__, $name)){
			throw new ExceptionMissingMethod($name);
		}
	}
	static $static_memoized = [];
	static function static_get_memoized($name, $arguments){
		$key = self::static_memoized__make_key($name, $arguments);

		if(self::static_memoized__has_key($key)){
			return self::static_memoized__get_from_key($key);
		}else{
			return self::static_call_and_memoize($name, $arguments, $key);
		}
	}
	static function static_call_and_memoize($name, $arguments, $key=null){
		if(!$key){
			$key = self::static_memoized__make_key($name, $arguments);
		}
		$result_to_memoize = call_user_func_array([__CLASS__, $name], $arguments);
		self::static_memoized__set_key($key, $result_to_memoize);
		return $result_to_memoize;
	}
	static function static_memoized__make_key($name, $arguments){
		return $name.'-'.md5(serialize($arguments));
	}
	static function static_memoized__has_key($key){
		return array_key_exists($key, self::$static_memoized);
	}
	static function static_memoized__get_from_key($key){
		return self::$static_memoized[$key];
	}
	static function static_memoized__set_key($key, $result){
		self::$static_memoized[$key] = $result;
	}

	/*
	There is a situation in which a function can be memoized, and can also call a memoized function (ex: `get_name()` can be memoized, and can call `get()` which can also be memoized)
	In such a situation, whether to use the memoized sub-function depends on whether the top function was requested as a memoized function.  This function indicates whether it was.
	*/
	public function static_caller_requested_memoized(){
		$stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

		if(
			$stack[2]['function'] == 'call_user_func_array'
			&& $stack[3]['function'] = 'static_call_and_memoize'
			&& $stack[4]['function'] == 'static_get_memoized'
		){
			return true;
		}
		return false;
	}
	/*
	Although search the is more dependable for determining whether a subsequent call should use a memoize, that which is within a memoize stack usually also uses memoize
	*/
	public function static_memoizing(){
		return (bool)self::$static_memoized_count;
	}
	# in the case we are within a stack that includes a memoize, call using memoize, otherwise, call regularly
	/* Examples
	$this->conditional_memoized('id_by_thing', ['user_role', $role]);
	$this->conditional_memoized('item_by_thing', ['user_role', $role]);
	*/
	public function static_conditional_memoized($name, $args){
		if(!is_array($name)){
			$name = [__CLASS__, $name];
		}

		if(self::static_memoizing()){
			$name[1] = 'memoized_'.$name[1];
		}

		return call_user_func_array($name, $args);
	}

	#+ }

	#+	instance functions {
	#< these just fully mimic static functions, but use an instance

	public $memoized_count = 0;
	public function __call($name, $arguments){
		if(substr($name,0,9) == 'memoized_'){
			$this->memoized_count++;
			$return = $this->get_memoized(substr($name,9), $arguments);
			$this->memoized_count--;
			return $return;
		}
		if(substr($name,0,8) == 'memoize_'){
			$this->memoized_count++;
			$return = $this->call_and_memoize(substr($name,8), $arguments);
			$this->memoized_count--;
			return $return;
		}
		if(!method_exists(__CLASS__, $name)){
			throw new ExceptionMissingMethod($name);
		}
	}
	public $memoized = [];
	public function get_memoized($name, $arguments){
		$key = $this->memoized__make_key($name, $arguments);

		if($this->memoized__has_key($key)){
			return $this->memoized__get_from_key($key);
		}else{
			return $this->call_and_memoize($name, $arguments, $key);
		}
	}
	public function call_and_memoize($name, $arguments, $key=null){
		if(!$key){
			$key = $this->memoized__make_key($name, $arguments);
		}
		$result_to_memoize = call_user_func_array([__CLASS__, $name], $arguments);
		$this->memoized__set_key($key, $result_to_memoize);
		return $result_to_memoize;
	}
	public function memoized__make_key($name, $arguments){
		return $name.'-'.md5(serialize($arguments));
	}
	public function memoized__has_key($key){
		return array_key_exists($key, $this->memoized);
	}
	public function memoized__get_from_key($key){
		return $this->memoized[$key];
	}
	public function memoized__set_key($key, $result){
		$this->memoized[$key] = $result;
	}
	public function memoized__set($name, $arguments, $result){
		$key = $this->memoized__make_key($name, $arguments);
		$this->memoized__set_key($key, $result);
	}
	public function memoized__unset($name, $arguments){
		$key = $this->memoized__make_key($name, $arguments);
		unset($this->memoized[$key]);
	}

	/*
	There is a situation in which a function can be memoized, and can also call a memoized function (ex: `get_name()` can be memoized, and can call `get()` which can also be memoized)
	In such a situation, whether to use the memoized sub-function depends on whether the top function was requested as a memoized function.  This function indicate whether it was.
	*/
	public function caller_requested_memoized(){
		$stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

		if(
			$stack[2]['function'] == 'call_user_func_array'
			&& $stack[3]['function'] = 'call_and_memoize'
			&& $stack[4]['function'] == 'get_memoized'
		){
			return true;
		}
		return false;
	}

	public function memoizing(){
		return (bool)$this->memoized_count;
	}

	# see static
	public function conditional_memoized($name, $args){
		if(!is_array($name)){
			$name = [__CLASS__, $name];
		}

		if($this->memoizing()){
			$name[1] = 'memoized_'.$name[1];
		}

		return call_user_func_array($name, $args);
	}

	#+	}
}