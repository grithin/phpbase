<?
namespace Grithin;

class Reflection{
	///get the first file that was executed
	static function firstFileExecuted(){
		return array_pop(debug_backtrace())['file'];
	}
	/// get the backtrace array for the caller existing outside the called class method
	static function externalCaller(){
		$backtrace = debug_backtrace();

		$class = $backtrace[0]['class'];
		if(!$class){
			throw new \Exception('No origin class');	}

		$count = count($backtrace);
		$found = [];
		for($i=1; $i < $count; $i++){
			if($backtrace[$i]['class'] == $class){
				$found = [];
				continue;	}

			if(!$backtrace[$i]['class'] && ($backtrace[$i]['function'] == 'call_user_func' || $backtrace[$i]['function'] == 'call_user_func_array')){
				$found = $backtrace[$i];
				continue;	}
			return $backtrace[$i];	}

		return $found;
	}
}