<?php
namespace Grithin;
trait SingletonInit{
	use Singleton;
	use testCall;
	static function __callStatic($fnName,$args){
		return call_user_func(array(static::singleton(),'__call'),$fnName,$args);
	}
}