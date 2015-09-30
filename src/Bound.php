<?
/// for creating functions with preset prefixed arguments
/**
ex:
	$bound = new \Bound('\control\Route::regexReplacer',$replacementString);
	$replacement = preg_replace_callback($regex,$bound,$subject);
*/
class Bound{
	function __invoke(){
		return call_user_func_array($this->callable,array_merge($this->args,func_get_args()));
	}
	/**
	@param callable the callable to call on invoke
	@param remaining the arguments to prefix callable with
	*/
	function __construct($callable,$args){
		$this->callable = $callable;
		$this->args = $args;
	}
}