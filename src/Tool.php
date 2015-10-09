<?
namespace Grithin;

use Grithin\Debug;
///General tools for use anywhere, either contexted by Tool or not (see bottom of file)
class Tool{
	///determines if string is a float
	static function isFloat($x){
		if((string)(float)$x == $x){
			return true;
		}
	}
	///determines if a string is an integer.  Limited to php int size
	static function isInt($x){
		if(is_int($var)){
			return true;
		}
		if((string)(int)$x === (string)$x && $x !== true & $x !== false && $x !== null){
			return true;
		}
	}
	///determines if a string is an integer.  Not limited to php int size
	static function isInteger($x){
		if(self::isInt($x)){
			return true;
		}
		if(preg_match('@\s*[0-9]+\s*@',$x)){
			return true;
		}
	}

	///runs callable in fork.  Returns on parent, exits on fork.
	///@note watch out for objects having __destroy() methods.  The closed fork will call those methods (and close resources in the parent)
	static function fork($callable){
		$pid = pcntl_fork();
		if ($pid == -1) {
			Debug::toss('could not fork');
		}elseif($pid) {
			// we are the parent
			return;
		}else{
			call_user_func_array($callable,array_slice(func_get_args(),1));
			exit;
		}
	}

	///checks whether a package is install on the machine
	static function checkPackage($package){
		exec('dpkg -s '.$package.' 2>&1',$out);
		if(preg_match('@not installed@',$out[0])){
			return false;
		}
		return true;
	}
	///will encode to utf8 on failing for bad encoding
	static function json_encode($x){
		$json = json_encode($x);
		if($json === false){
			if(json_last_error() == JSON_ERROR_UTF8){
				self::utf8_encode($x);
				$json = json_encode($x);	}	}
		if(json_last_error() != JSON_ERROR_NONE){
			\Debug::toss('JSON encode error: '.json_last_error());	}

		return $json;
	}
	///utf encode variably deep array
	static function &utf8_encode(&$x){
		if(!is_array($x)){
			$x = utf8_encode($x);
		}else{
			foreach($x as $k=>&$v){
				self::utf8_encode($v);	}	}
		return $x;
	}

	/// turn a value into a reference
	static function &reference($v){
		return $v;
	}
}