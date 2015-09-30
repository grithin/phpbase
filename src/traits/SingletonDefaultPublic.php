<?
namespace Grithin;
use Grithin\Debug;
use Grithin\SingletonDefault;

/**
Concept of public: Since with php, you can't make one method protected when called statically and public when called on an instance, for applying the singleton default pattern, you must apply 'protected' to all methods.  This, however, has the inefficiency of using a '__call' for methods that would otherwise go straight through (instance method calls).  To avoid this, make one class which handles the static calls, and another class which makes the instance and has public methods.

instantiate a public version of the class, where the main version serves as a placeholder for use statically.
	- needed because otherwise would need to set methods to 'protected' for static calls to work, which means a '__call' was needed, and that has overhead.
@note	It is expected the public version will be named $class.'Public'
*/
trait SingletonDefaultPublic{
	use SingletonDefault;

	static function className($called){
		return $called.'Public';
	}
	static function __callStatic($fnName,$args){
		return call_user_func_array(array(static::primary(),$fnName),$args);
	}
}