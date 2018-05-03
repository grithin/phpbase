<?
namespace Grithin;

use \Exception;

use Grithin\Debug;
///General tools for use anywhere, either contexted by Tool or not (see bottom of file)
class Tool{
	# only consider array and objects non-scalars
	static function is_scalar($x){
		return !(is_array($x) || is_object($x));
	}
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
				$json = json_encode($x, $options, $depth);
			}
		}
		self::json_throw_on_error();

		return $json;
	}
	# return data array of json string, throwing error if failure
	static function json_decode($x, $options=[]){
		$options = array_merge(['array'=>true, 'default'=>[]], (array)$options);
		if(!$x){
			return $options['default'];
		}else{
			$data = json_decode($x, $options['array']);
		}
		self::json_throw_on_error();
		return $data;
	}
	static function json_throw_on_error(){
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
			throw new Exception('JSON encode error: '.$types[json_last_error()]);
		}
	}


	# turn a value into a non circular value (potentially by turning objects int)
	static function decirculate($source, $options=[], $parents=[]){

		#+ set the default circular value hander if not provided {
		if(!$options['circular_value'] && !array_key_exists('circular_value', $options)){
			$options['circular_value'] = function($v){
				if(is_object($v)){
					return ['_circular'=>true, '_class'=>get_class($v), '_reference'=>spl_object_hash($v)];
				}else{
					return ['_circular'=>true,  '_keys'=>array_keys($v)];
				}
			};
		}
		#+ }

		#+ set the default object data extraction hander if not provided {
		if(!$options['object_extracter'] && !array_key_exists('object_extracter', $options)){
			$options['object_extracter'] = function($v){
				return array_merge(['_class' => get_class($v)], get_object_vars($v));
			};
		}
		#+ }

		foreach($parents as $parent){
			if($parent === $source){
				if(is_callable($options['circular_value'])){
					return $options['circular_value']($source);
				}else{
					return $options['circular_value'];
				}
			}
		}
		if(is_object($source)){
			$parents[] = $source;
			return self::decirculate($options['object_extracter']($source), $options, $parents);
		}elseif(is_array($source)){
			$return = [];
			$parents[] = $source;
			foreach($source as $k=>$v){
				$return[$k] = self::decirculate($v, $options, $parents);
			}
			return $return;
		}else{
			return $source;
		}
	}

	/// remove circular references
	static function flat_json_encode($v, $json_options=0, $max_depth=512, $decirculate_options=[]){
		return self::json_encode(self::decirculate($v, $decirculate_options), $json_options, $max_depth);
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


	#+	MaterializedPaths {
	#< @NOTE	this expects collation to something that uses case sensitivity, like `alter table licenses change path path varchar(250) default '' collate latin1_bin;`
	/* Example
	$x = Tool::hierarchy_to_path([5,23,1,49]);
	$x = Tool::path_to_hierarchy($x);
	*/

	static function hierarchy_to_path($parents){
		$fn = function($v){ return Number::from_10_to_base($v); };
		return implode('/', array_map($fn, $parents));
	}
	static function path_to_hierarchy($path){
		$parents = explode('/', $path);
		$fn = function($v){ return Number::from_base_to_10($v); };
		return array_map($fn, $parents);
	}
	#+	}


	function password_hash($password){
		return password_hash($password, PASSWORD_BCRYPT);

	}
	function password_verify($password, $hash){
		return password_verify($password, $hash);
	}


	# Ex: cli_parse_args($argv)
	/*	args
		options: {default: < the value a flag argument should take.  Defaults to true >}
	*/
	static function cli_parse_args($args, $options=[]){
		$options = array_merge(['default'=>true], $options);
		$params = [];
		$current_key = '';
		foreach($args as $arg){
			if($arg[0] == '-'){
				if($arg[1] == '-'){
					if(strpos($arg, '=') !== false){
						list($key, $value) = explode('=', $arg);
						$params[substr($key, 2)] = $value;
					}else{
						$current_key = substr($arg, 2);
						$params[$current_key] = $options['default'];
					}
				}else{
					if(strlen($arg) > 2){
						$keys = str_split(substr($arg, 1));
						$current_key = array_pop($keys);
						$params[$current_key] = $options['default'];
						foreach($keys as $key){
							$params[$key] = $options['default'];
						}
					}else{
						$current_key = substr($arg, 1);
						$params[$current_key] = $options['default'];
					}
				}
			}else{
				if($current_key){
					$params[$current_key] = $arg;
					unset($current_key);
				}else{
					$params[] = $arg;
				}
			}
		}
		return $params;
	}
}
