<?
namespace Grithin;
/// for creating functions with preset prefixed arguments
/**
Addresses two issues:
	The need for a similar functionality to javascripts .bind()
	The need for a function-type as a way to identify functions, instead of relying on interpretation of a string being a function for use on call_user_func

ex:
	.bind() handling
		function bob($firstArg, $secondArg){}
		$bound = new Bound('bob','first argument');
		$bound('second argument')
	function-type
		function bob(){}
		$bound = new Bound('bob')
*/
class Bound{
	function __invoke(){
		return call_user_func_array($this->callable,array_merge($this->args,func_get_args()));
	}
	/**
	@param callable the callable to call on invoke
	@param remaining the arguments to prefix callable with
	*/
	function __construct($callable,$args=[]){
		$this->callable = $callable;
		$this->args = (array)$args;
	}
}