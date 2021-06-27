<?php
namespace Grithin\Traits;

/** For adding magic memoizing methods

prefix with `memoize_` to force a memoizing of a method
prefix with `memoized_` to use existing memoized cache, or memoize if none exists

*/

use Exception;

trait Memoized{

	static protected $static_memoized_count = 0;
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

	#+	static functions {
	/** determine, based on backtrace, if the current function was called with a memoize prefix
	@return bool whether caller called with memoized prefix
	*/
	static public function static_caller_requested_memoized(){
		$stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

		if(
			$stack[2]['function'] == 'call_user_func_array'
			&& $stack[3]['function'] == 'static_call_and_memoize'
		){
			return true;
		}
		return false;
	}

	/** whether currently in memoize stack
	@return bool
	 */
	static public function static_memoizing(){
		return (bool)self::$static_memoized_count;
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

	/** check if memoized key exists */
	static function static_memoized__has_key($key){
		return array_key_exists($key, self::$static_memoized);
	}
	/** get memoized value */
	static function static_memoized__get_from_key($key){
		return self::$static_memoized[$key];
	}
	/** set memoized value at key */
	static function static_memoized__set_key($key, $result){
		self::$static_memoized[$key] = $result;
	}



	#+ }

	#+	instance functions {
	/*< these just fully mimic static functions, but use an instance */

	public function __call($name, $arguments){
		if(substr($name,0,9) == 'memoized_'){
			self::$static_memoized_count++;
			$return = $this->get_memoized(substr($name,9), $arguments);
			self::$static_memoized_count--;
			return $return;
		}
		if(substr($name,0,8) == 'memoize_'){
			self::$static_memoized_count++;
			$return = $this->call_and_memoize(substr($name,8), $arguments);
			self::$static_memoized_count--;
			return $return;
		}
		if(!method_exists(__CLASS__, $name)){
			throw new ExceptionMissingMethod($name);
		}
	}

	/** determine, based on backtrace, if the current function was called with a memoize prefix
	@return bool whether caller called with memoized prefix
	*/
	public function caller_requested_memoized(){
		$stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

		if(
			$stack[2]['function'] == 'call_user_func_array'
			&& $stack[3]['function'] == 'call_and_memoize'
		){
			return true;
		}
		return false;
	}

	/** whether currently in memoize stack
	@return bool
	 */
	public function memoizing(){
		return self::static_memoizing();
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
	/** check if memoized key exists */
	public function memoized__has_key($key){
		return array_key_exists($key, $this->memoized);
	}
	/** get memoized value */
	public function memoized__get_from_key($key){
		return $this->memoized[$key];
	}
	/** set memoized value at key */
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


}