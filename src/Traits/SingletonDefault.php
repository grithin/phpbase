<?php
namespace Grithin\Traits;
use Grithin\Debug;
use Grithin\Traits\testCall;

/*
The convenience of acting like there is just one, with the ability to handle multiple
Static calls default to primary instance.  If no primary instance, attempt to create one.

@NOTE	__construct can not be protected because the RelfectionClass call to it is not considered a relative
@NOTE __call doesn't take arguments by reference (and func_get_args() doesn't return references), so don't apply to classes requiring reference args (unless php introduces pointers)

Ex, named instance
	Db::init('instance name', $config)
Ex, name defaulted instance
	Db::singleton($config)
*/
trait SingletonDefault{
	use testCall;
	/** object representing the primary instance name. */
	/** @note Must use name because static::$instances[$instanceName] may change, and linking primary using reference will cause change of static::$instances[$instanceName] on change of primary */
	static $primaryName;
	static function className($called){
		return $called;
	}
	/** array of named instances */
	static $instances = array();
	static $affix = '';
	static $i = 0; #< default instance name incrementer
	/**
	@param	instanceName	if set to null, will increment starting with 0 for each init call.
	*/
	static function init($instanceName=null){
		$instanceName = $instanceName !== null ? $instanceName : self::$i++;
		if(!isset(static::$instances[$instanceName])){
			# in the case of trait `SingletonDefaultPublic`, the called class is a shell for the public class, and so the className must be defined by the shell
			$className = static::className(get_called_class());

			$class = new \ReflectionClass($className);
			$instance = $class->newInstanceArgs(array_slice(func_get_args(),1));
			static::$instances[$instanceName] = $instance;
			static::$instances[$instanceName]->name = $instanceName;

			//set primary if no instances except this one
			if(count(static::$instances) == 1){
				static::setPrimary($instanceName,$className);#< use of `static` a,d `$className` override and custom handling
			}
		}
		return static::$instances[$instanceName];
	}
	static function init_force($instanceName=null){
		$instanceName = $instanceName !== null ? $instanceName : self::$i++;
		$className = static::className(get_called_class());#< use of `static` to allow for override on which class is instantiated
		$class = new \ReflectionClass($className);
		$instance = $class->newInstanceArgs(array_slice(func_get_args(),1));
		static::$instances[$instanceName] = $instance;
		static::$instances[$instanceName]->name = $instanceName;

		//set primary if no instances except this one
		if(count(static::$instances) == 1){
			static::setPrimary($instanceName,$className);#< use of `static` a,d `$className` override and custom handling
		}
		return static::$instances[$instanceName];
	}

	/** get named instance from $instance dictionary - convenience of expectation */
	static function instance($name=null){
		if(!$name){
			return static::primary();
		}
		if(!self::$instances[$name]){
			throw new \Exception('No instance of name "'.$name.'"');
		}
		return self::$instances[$name];
	}
	/** map a key to another key */
	static function instance_map($alias, $target){
		self::$instances[$alias] = self::$instances[$target]; # instances are objects, to which variables keep references
	}
	/** use the default name of `0` and call init */
	static function singleton(){
		$args = array_merge([0], (array)func_get_args());
		return call_user_func_array([__CLASS__,'init'], $args);
	}
	/** overwrite any existing primary with new construct */
	static function &resetPrimary($instanceName=null){
		$instanceName = $instanceName !== null ? $instanceName : 0;
		$class = new \ReflectionClass(static::className(get_called_class()));
		$instance = $class->newInstanceArgs(array_slice(func_get_args(),1));
		static::$instances[$instanceName] = $instance;
		static::$instances[$instanceName]->name = $instanceName;

		static::setPrimary($instanceName,$className);
		return static::$instances[$instanceName];
	}
	static function primary_overwrite(){
		$args = func_get_args();
		array_unshift($args, static::$primaryName);
		return call_user_func_array(['static', 'resetPrimary'], $args);
	}
	/** sets primary to some named instance */
	static function setPrimary($instanceName){
		$instanceName = $instanceName === null ? 0 : $instanceName;
		static::$primaryName = $instanceName;
	}
	/** overwrite existing instance with provided instance */
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

	/** used to translate static calls to the primary instance */
	static function __callStatic($fnName,$args){
		return call_user_func(array(static::primary(),'__call'),$fnName,$args);
	}
}