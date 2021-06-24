<?php
namespace Grithin;

class Reflection{
	/** get the first file that was executed */
	static function firstFileExecuted(){
		return debug_backtrace()[0]['file'];
	}
	/** attempt to get the backtrace array for the caller existing outside the called class method */
	/**
	The backtrace does not include whether the code point is within a class, so this function only flags as outside if either:
		1.	class of frame does not match origin class
		2.	file does not match origin file
	*/
	static function externalCaller(){
		$backtrace = debug_backtrace();

		for($i=0;$i<5;$i++){
			$origin_class = $backtrace[$i]['class'];
			if($origin_class != 'Grithin\Reflection'){
				break;
			}
		}
		if(!$origin_class){
			throw new \Exception('No origin class');	}

		$origin_file = $backtrace[$i]['file'];

		$count = count($backtrace);
		$found = [];
		for($i=$i+1; $i < $count; $i++){
			if($backtrace[$i]['class'] == $origin_class && $backtrace[$i]['file'] == $origin_file){
				$found = [];
				continue;	}

			# call_* functions can be inside or outside, not enough frame information to tell, so have to wait until the next frame
			if(empty($backtrace[$i]['class']) && ($backtrace[$i]['function'] == 'call_user_func' || $backtrace[$i]['function'] == 'call_user_func_array')){
				$found = $backtrace[$i];
				continue;	}

			if($found){
				return $found;
			}
			return $backtrace[$i];	}

		return $found;
	}
}