<?
namespace Grithin;

/*
For handling static and instance based convenient memoizing (by __call and __callStatic function name matching).  Allows handling sub-memoize calls (`static_caller_requested_memoized`), and allows re-making (`static_call_and_memoize`).

Methods are intended to be overriden as disired (like to change the memoize get functions to use redis)
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
		if(!method_exists(self, $name)){
			throw new Exception(get_called_class().' Method not found: '.$name);
		}
	}
	static $static_memoized = [];
	static function static_get_memoized($name, $arguments){
		$key = self::static_make_key($name, $arguments);

		if(self::static_memoized_has_key($key)){
			return self::static_memoized_get_from_key($key);
		}else{
			return self::static_call_and_memoize($name, $arguments, $key);
		}
	}
	static function static_call_and_memoize($name, $arguments, $key=null){
		if(!$key){
			$key = self::static_make_key($name, $arguments);
		}
		$result_to_memoize = call_user_func_array([self, $name], $arguments);
		self::static_memoized_set_key($key, $result_to_memoize);
		return $result_to_memoize;
	}
	static function static_make_key($name, $arguments){
		return $name.'-'.md5(serialize($arguments));
	}
	static function static_memoized_has_key($key){
		return array_key_exists($key, self::$static_memoized);
	}
	static function static_memoized_get_from_key($key){
		return self::$static_memoized[$key];
	}
	static function static_memoized_set_key($key, $result){
		self::$static_memoized[$key] = $result;
	}

	/*
	There is a situation in which a function can be memoized, and can also call a memoized function (ex: `get_name()` can be memoized, and can call `get()` which can also be memoized)
	In such a situation, whether to use the memoized sub-function depends on whether the top function was requested as a memoized function.  This function indicate whether it was.
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
			$name = [self, $name];
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
		if(!method_exists(self, $name)){
			throw new Exception(get_called_class().' Method not found: '.$name);
		}
	}
	public $memoized = [];
	public function get_memoized($name, $arguments){
		$key = $this->make_key($name, $arguments);

		if($this->memoized_has_key($key)){
			return $this->memoized_get_from_key($key);
		}else{
			return $this->call_and_memoize($name, $arguments, $key);
		}
	}
	public function call_and_memoize($name, $arguments, $key=null){
		if(!$key){
			$key = $this->make_key($name, $arguments);
		}
		$result_to_memoize = call_user_func_array([self, $name], $arguments);
		$this->memoized_set_key($key, $result_to_memoize);
		return $result_to_memoize;
	}
	public function make_key($name, $arguments){
		return $name.'-'.md5(serialize($arguments));
	}
	public function memoized_has_key($key){
		return array_key_exists($key, $this->memoized);
	}
	public function memoized_get_from_key($key){
		return $this->memoized[$key];
	}
	public function memoized_set_key($key, $result){
		$this->memoized[$key] = $result;
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
			$name = [self, $name];
		}

		if($this->memoizing()){
			$name[1] = 'memoized_'.$name[1];
		}

		return call_user_func_array($name, $args);
	}

	#+	}
}

/* Test Static
class bob{
	use \Grithin\Memoized;
	static function test($x){
		if(self::static_caller_requested_memoized()){
			return self::memoized_bottom($x);
		}else{
			return self::bottom($x);
		}
	}
	static function test2($x){
		if(self::static_caller_requested_memoized()){
			return self::memoized_bottom($x);
		}else{
			return self::memoize_bottom($x);
		}
	}

	static function bottom($x){
		return $x.'1 '.microtime();
	}
}

$x1 = bob::memoized_test('bobs');
$x2 = bob::memoized_test('bobs');
if($x1 !== $x2){
	pp('memoized faliure');
}
$x3 = bob::memoize_test('bobs');
if($x3 == $x1){
	pp('memoize remake faliure');
}
$x4 = bob::memoized_test('bobs');
if($x3 !== $x4){
	pp('memoized faliure');
}
*/

/* Test Instance
class bob{
	use \Grithin\Memoized;
	public function test($x){
		if($this->caller_requested_memoized()){
			return $this->memoized_bottom($x);
		}else{
			return $this->bottom($x);
		}
	}
	public function test2($x){
		if($this->caller_requested_memoized()){
			return $this->memoized_bottom($x);
		}else{
			return $this->memoize_bottom($x);
		}
	}

	public function bottom($x){
		return $x.'1 '.microtime();
	}
}

$bob = new bob;
$x1 = $bob->memoized_test('bobs');
$x2 = $bob->memoized_test('bobs');
if($x1 !== $x2){
	pp('memoized faliure');
}
$x3 = $bob->memoize_test('bobs');
if($x3 == $x1){
	pp('memoize remake faliure');
}
$x4 = $bob->memoized_test('bobs');
if($x3 !== $x4){
	pp('memoized faliure');
}
*/
