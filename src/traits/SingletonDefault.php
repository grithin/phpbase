<?
namespace Grithin;
use Grithin\Debug;
use Grithin\testCall;

/**
The convenience of acting like there is just one, with the ability to handle multiple
Static calls default to primary instance.  If no primary instance, attempt to create one.

@note	__construct can not be protected because the RelfectionClass call to it is not considered a relative
@note __call doesn't take arguments by reference (and func_get_args() doesn't return references), so don't apply to classes requiring reference args (unless php introduces pointers)
*/
trait SingletonDefault{
	use testCall;
	/// object representing the primary instance name.
	///@note Must use name because static::$instances[$instanceName] may change, and linking primary using reference will cause change of static::$instances[$instanceName] on change of primary
	static $primaryName;
	static function className($called){
		return $called;
	}
	/// array of named instances
	static $instances = array();
	static $affix = '';
	static function &init($instanceName=null){
		$instanceName = $instanceName !== null ? $instanceName : 0;
		if(!isset(static::$instances[$instanceName])){
			$class = new \ReflectionClass(static::className(get_called_class()));
			$instance = $class->newInstanceArgs(array_slice(func_get_args(),1));
			static::$instances[$instanceName] = $instance;
			static::$instances[$instanceName]->name = $instanceName;

			//set primary if no instances except this one
			if(count(static::$instances) == 1){
				static::setPrimary($instanceName,$className);
			}
		}
		return static::$instances[$instanceName];
	}
	/// overwrite any existing primary with new construct
	static function &resetPrimary($instanceName=null){
		$instanceName = $instanceName !== null ? $instanceName : 0;
		$class = new \ReflectionClass(static::className(get_called_class()));
		$instance = $class->newInstanceArgs(array_slice(func_get_args(),1));
		static::$instances[$instanceName] = $instance;
		static::$instances[$instanceName]->name = $instanceName;

		static::setPrimary($instanceName,$className);
		return static::$instances[$instanceName];
	}
	/// sets primary to some named instance
	static function setPrimary($instanceName){
		static::$primaryName = $instanceName;
	}
	/// overwrite existing instance with provided instance
	static function forceInstance($instanceName,$instance){
		static::$instances[$instanceName] = $instance;
		static::$instances[$instanceName]->name = $instanceName;
	}
	static function primary(){
		if(!static::$instances[static::$primaryName]){
			static::init();
		}
		return static::$instances[static::$primaryName];
	}

	/// used to translate static calls to the primary instance
	static function __callStatic($fnName,$args){
		return call_user_func(array(static::primary(),'__call'),$fnName,$args);
	}
}