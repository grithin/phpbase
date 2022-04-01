<?php
namespace Grithin\Traits;

/** Named instances of an object, accessible by the class
In many cases, there is one primary instance of an object, and occasionally additional supplementary instances.
For example, one primary database object, and one or more peripheral database objects.  Keeping track of these object
is most intuitively done within the class itself (not some service locator).

Intended to replace SingletonDefault

*/

trait ClassSingletons{
	public static $instances = array();

	/** use the default name of `0` and call init
	 A constructor may have zero args, so the arg count can not be used to determine if this
	is a get or set call.  So it is always a set call.
	Also, assume if the primary is already set, this is not attempting to overwrite
	*/
	public static function singleton(){
		if(isset(self::$instances[0])){ # return the current primary
			return self::$instances[0];
		}
		# set primary if not set
		$args = array_merge([0], (array)func_get_args());
		return call_user_func_array([__CLASS__,'named_singleton'], $args);
	}
	/** make a new named singleton instance */
	/**
	@param	instance_name	if set to null, will increment starting with 0 for each init call.
	*/
	public static function named_singleton($instance_name){
		if(!isset(static::$instances[$instance_name])){
			return self::singleton_make_instance($instance_name, array_slice(func_get_args(),1));
		}
		return static::$instances[$instance_name];
	}
	/** construct an instance and set the key */
	public static function singleton_make_instance($instance_name, $constructor_args){
		$className = get_called_class();#< use of `static` to allow for override on which class is instantiated
		$class = new \ReflectionClass($className);
		$instance = $class->newInstanceArgs($constructor_args);
		static::$instances[$instance_name] = $instance;
		static::$instances[$instance_name]->singleton_name = $instance_name;
		return static::$instances[$instance_name];
	}
}