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
	static function json_encode($x, $options =0, $depth = 512){
		$json = json_encode($x, $options, $depth);
		if($json === false){
			if(json_last_error() == JSON_ERROR_UTF8){
				self::utf8_encode($x);
				$json = json_encode($x, $options, $depth);	}	}
		if(json_last_error() != JSON_ERROR_NONE){
			$types = [
				JSON_ERROR_NONE=>'JSON_ERROR_NONE',
				JSON_ERROR_DEPTH=>'JSON_ERROR_DEPTH',
				JSON_ERROR_STATE_MISMATCH=>'JSON_ERROR_STATE_MISMATCH',
				JSON_ERROR_CTRL_CHAR=>'JSON_ERROR_CTRL_CHAR',
				JSON_ERROR_SYNTAX=>'JSON_ERROR_SYNTAX',
				JSON_ERROR_UTF8=>'JSON_ERROR_UTF8',
				JSON_ERROR_RECURSION=>'JSON_ERROR_RECURSION',
				JSON_ERROR_INF_OR_NAN=>'JSON_ERROR_INF_OR_NAN',
				JSON_ERROR_UNSUPPORTED_TYPE=>'JSON_ERROR_UNSUPPORTED_TYPE'];
			Debug::toss('JSON encode error: '.$types[json_last_error()]);	}

		return $json;
	}
	/// remove circular references
	static function flat_json_encode($v, $options=0, $depth=512){
		if(is_object($v)){
			try{
				return self::json_encode($v, $options, $depth);
			}catch(\Exception $e){
				self::flat_json_encode(get_object_vars($v), $options, $depth);
			}
		}elseif(is_array($v)){
			try{
				return self::json_encode($v, $options, $depth);
			}catch(\Exception $e){
				$acceptable = [];
				foreach($v as $k=>$v2){
					try{
						self::json_encode($v2, $options, $depth);
						$acceptable[$k] = $v2;
					}catch(\Exception $e){
						$acceptable[$k] = [];
					}
				}
				return self::json_encode($acceptable, $options, $depth);
			}
		}else{
			return self::json_encode($v, $options, $depth);
		}
	}
	static function to_jsonable($v){
		if(is_scalar($v)){
			return $v;
		}else{
			return json_decode(self::flat_json_encode($v), true);
		}
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