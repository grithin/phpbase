<?php
namespace Grithin\Traits;
trait Singleton{
	static $singleton;
	static function singleton(){
		if(!self::$singleton){
			$class = new \ReflectionClass(get_called_class());
			$instance = $class->newInstanceArgs(func_get_args());
			self::$singleton = $instance;
		}
		return self::$singleton;
	}
}