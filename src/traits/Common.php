<?php
namespace Grithin\Traits; # so named because \Grithin\Trait causes syntax error

/* About
Common class functions
*/

trait Common{
	# create an instance of the called class with args as constructor args
	function instance_with_args($args){
		$class = new \ReflectionClass(get_called_class());
		return $class->newInstanceArgs($args);
	}
}