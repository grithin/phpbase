<?php
namespace Grithin\Traits;
use Grithin\Debug;
use Grithin\Traits\testCall;

trait SingletonDefault{
	use testCall;

	/** use the default name of `0` and call init */
	static function singleton(){
		$args = array_merge([0], (array)func_get_args());
		return call_user_func_array([__CLASS__,'init'], $args);
	}


	/** used to translate static calls to the primary instance */
	static function __callStatic($fnName,$args){
		return call_user_func(array(static::primary(),'__call'),$fnName,$args);
	}

	/** object representing the primary instance name. */
	/** @note Must use name because static::$instances[$instance_name] may change, and linking primary using reference will cause change of static::$instances[$instance_name] on change of primary */
	static $primary_singleton_name;


	/** For singletons that point to a different class
	With SingletonDefaultPublic, the actual instance is a different class, with the name $class.'Public'.  This
	function allows variable resolution of the class name. */
	static function class_name($className){
		return $className;
	}
	/** array of named instances */
	static $instances = array();
	static $affix = '';
	static $i = 0; #< default instance name incrementer

	/** make a new named singleton instance */
	/**
	@param	instance_name	if set to null, will increment starting with 0 for each init call.
	*/
	static function init($instance_name=null){
		$instance_name = $instance_name !== null ? $instance_name : self::$i++;
		if(!isset(static::$instances[$instance_name])){
			return self::init_make_instance($instance_name, array_slice(func_get_args(),1));
		}
		return static::$instances[$instance_name];
	}

	/** make a new instance regardless of whether one exists with the instance name */
	static function init_new($instance_name=null){
		$instance_name = $instance_name !== null ? $instance_name : self::$i++;
		$instance_name = self::init_resolve_name($instance_name);
		return self::init_make_instance($instance_name, array_slice(func_get_args(),1));
	}

	/** if no name, return increment */
	protected static function init_resolve_name($instance_name=null){
		return $instance_name !== null ? $instance_name : self::$i++;
	}
	/** construct an instance and set the key */
	public static function init_make_instance($instance_name, $constructor_args){
		$className = static::class_name(get_called_class());#< use of `static` to allow for override on which class is instantiated
		$class = new \ReflectionClass($className);
		$instance = $class->newInstanceArgs($constructor_args);
		static::$instances[$instance_name] = $instance;
		static::$instances[$instance_name]->singleton_name = $instance_name;

		//set primary if no instances except this one
		if(count(static::$instances) == 1){
			static::primary_set($instance_name,$className);#< use of `static` a,d `$className` override and custom handling
		}
		return static::$instances[$instance_name];
	}


	/** get named instance from $instance dictionary */
	static function instance($name=null){
		if(!$name){
			return static::primary();
		}
		if(!isset(self::$instances[$name])){
			throw new \Exception('No instance of name "'.$name.'"');
		}
		return self::$instances[$name];
	}
	/** map a key to another key */
	static function instance_map($alias, $target){
		self::$instances[$alias] = self::$instances[$target]; # instances are objects, to which variables keep references
	}

	/** sets primary to some named instance */
	static function primary_set($instance_name){
		$instance_name = $instance_name === null ? 0 : $instance_name;
		static::$primary_singleton_name = $instance_name;
	}
	/** set a singletone key to a given instance */
	static function singleton_set($instance_name,$instance){
		static::$instances[$instance_name] = $instance;
		static::$instances[$instance_name]->name = $instance_name;
	}
	/** get the primary instance */
	static function primary(){
		if(!static::$instances[static::$primary_singleton_name]){
			static::init();
		}
		return static::$instances[static::$primary_singleton_name];
	}
}