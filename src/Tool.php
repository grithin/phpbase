<?php
namespace Grithin;

use \Exception;

use Grithin\Debug;
///General tools for use anywhere, either contexted by Tool or not (see bottom of file)
class Tool{
	/** only consider array and objects non-scalars */
	static function is_scalar($x){
		return !(is_array($x) || is_object($x));
	}
	/** determines if string is a float */
	static function is_float($x){
		if((string)(float)$x == $x){
			return true;
		}
	}
	/** determines if a string is an integer.  Limited to php int size */
	static function is_int($x){
		if(is_int($x)){
			return true;
		}
		if((string)(int)$x === (string)$x && $x !== true & $x !== false && $x !== null){
			return true;
		}
	}
	/** determines if a string is an integer.  Not limited to php int size */
	static function is_integer($x){
		if(self::is_int($x)){
			return true;
		}
		if(preg_match('@\s*[0-9]+\s*@',$x)){
			return true;
		}
	}

	/** runs callable in fork.  Returns on parent, exits on fork. */
	/** @note watch out for objects having __destroy() methods.  The closed fork will call those methods (and close resources in the parent) */
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


	/** will encode to utf8 on failing for bad encoding */
	static function json_encode($x, $options =0, $depth = 512){
		$x = self::deresource($x);
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
	/** return data array of json string, throwing error if failure */
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


	/** turn a value into a non circular value */
	static function decirculate($source, $options=[]){
		#+ set the default circular value handler if not provided {
		if(empty($options['circular_value']) && !array_key_exists('circular_value', $options)){
			$options['circular_value'] = function($v){
				return self::decirculate_circular_value($v);
			};
		}
		#+ }

		#+ set the default object data extraction handler if not provided {
		if(empty($options['object_extracter']) && !array_key_exists('object_extracter', $options)){
			$options['object_extracter'] = function($v){
				return self::decirculate_object_extrator($v);
			};
		}
		#+ }
		if(empty($options['max_depth'])){
			$options['max_depth'] = 10;
		}
		return self::decirculate_do($source, $options['circular_value'], $options['object_extracter'], $options['max_depth']);

	}
	static function decirculate_circular_value($v){
		$v = self::dirculate_flatten($v);
		$v['_circular'] = true;
		return $v;
	}
	/** don't attempt to get deep values, just express the thing in a way that is understandable*/
	static function dirculate_flatten($v){
		if(is_object($v)){
			return ['_class'=>get_class($v), '_reference'=>spl_object_hash($v)];
		}elseif(is_array($v)){
			return ['_keys'=>array_keys($v)];
		}
		return $v;
	}
	static function decirculate_object_extrator($v){
		if(method_exists($v, '__toArray')){
			return (array)$v->__toArray();
		}
		return array_merge(['_class' => get_class($v)], get_object_vars($v));
	}
	/** does the actual decirculation for `decirculate` */
	static function decirculate_do($source, $circular_value_handler, $object_extractor, $max_depth=10, $depth=0, $parents=[]){
		if($depth == $max_depth-1){
			return self::dirculate_flatten($source);
		}
		foreach($parents as $parent){
			if($parent === $source){
				if(is_callable($circular_value_handler)){
					return $circular_value_handler($source);
				}else{
					return $circular_value_handler;
				}
			}
		}
		if(is_object($source)){
			$parents[] = $source;
			return self::decirculate_do($object_extractor($source), $circular_value_handler, $object_extractor, $max_depth, $depth, $parents);
		}elseif(is_array($source)){
			$return = [];
			$parents[] = $source;
			foreach($source as $k=>$v){
				$return[$k] = self::decirculate_do($v, $circular_value_handler, $object_extractor, $max_depth, $depth+1, $parents);
			}
			return $return;
		}else{
			return $source;
		}
	}

	/** remove resource variables by replacing them with a string returned by get_resource_type */
	static function deresource($source){
		if(is_array($source)){
			$return = [];
			foreach($source as $k=>$v){
				$return[$k] = self::deresource($v);
			}
			return $return;
		}else{
			if(is_resource($source)){
				return 'Resource Type: '.get_resource_type($source);
			}else{
				return $source;
			}
		}
	}

	/** remove circular references */
	static function flat_json_encode($v, $json_options=0, $max_depth=512, $decirculate_options=[]){
		return self::json_encode(self::decirculate($v, $decirculate_options), $json_options, $max_depth);
	}
	/** converts some value into something that can beturned into JSON */
	static function to_jsonable($v){
		if(is_scalar($v)){
			return $v;
		}else{
			return json_decode(self::flat_json_encode($v), true);
		}
	}

	/** utf8 encode keys and values of variably deep array */
	static function &utf8_encode(&$x){
		if(!is_array($x)){
			$x = Strings::utf8_encode($x);
		}else{
			$new_x = [];
			foreach($x as $k=>&$v){
				$new_x[Strings::utf8_encode($k)] = self::utf8_encode($v);
			}
			$x = $new_x;
		}
		return $x;
	}

	/** turn a value into a reference */
	static function &reference($v){
		return $v;
	}


	#+	MaterializedPaths {
	/**< @NOTE	this expects collation to something that uses case sensitivity, like `alter table licenses change path path varchar(250) default '' collate latin1_bin;` */
	/** Example
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


	/** create a randomly salted password hash */
	function password_hash($password){
		return password_hash($password, PASSWORD_BCRYPT);

	}
	/** check a password against a randomly salted password hash */
	function password_verify($password, $hash){
		return password_verify($password, $hash);
	}


	/** Ex: cli_parse_args($argv) */
	/**	args
		< options >: {
			default: < the value a flag argument should take.  Defaults to true >
			map: {
				<from> : <to>
			},
			flags: [ < key >, ...] < array of keys that will not have a value, but should be taken as a flag >
			defaults :
				< key > : < default value >
		}

	*/
	/** note, on numeric args
	The args can be mapped like normal keys, with those keys being numeric (ex: "0", "1", "2")
	The numeric key of an argument is the increment of unkeyed arguments.  If an argument is keyed (ex "-b bill"), it does not count towards the increment that serves as the numeric key for unkeyed arguments
	Note that the first argument, "0", is normally the file name
	Numeric key mapping will not overwrite values given by key.  (Ex: if "0" is mapped to "r", and both are passed in, the value passed in under "r" will remain)
	*/
	/** note, on quoted args
	$argv does not distinguished quoted arguments from non quoted arguments.  Consequently, if a value starts with '-', it is always interpretted as a key.
	To avoid this, for any key that receives a value that can start with '-', use the form `--range='-24hr'`
	*/

	/** note, on duplicate keys
	the majority of the time, a repeated key is an error, and not an intended array.  And, if an array is desired, it can be done with another key to be parsed as such.
	Consequently, duplicate keys overwrite each other instead of forming an array
	*/
	/** Notes
	-	there are no outside function dependencies so that this function can be copy and pasted into a single file intended to be run on command line without the need to link this library
	*/
	static function cli_parse_args($args, $options=[]){ # see \Grithin\Tool::cli_parse_args
		if(!is_array($args)){
			throw new \Exception('$args parameter should be an array');
		}
		$options = array_merge(['default'=>true, 'flags'=>[]], $options);
		$params = [];

		# use the options map if present
		$key_get = function($key) use ($options){
			if($options['map'] && $options['map'][$key]){
				return $options['map'][$key];
			}
			return $key;
		};

		# only set a default for keys that don't have previous values
		$param_set_default = function($key, $value=null) use (&$params, $options){
			if(!array_key_exists($key,$params)){
				$params[$key] = $value ? $value : $options['default'];
			}
		};

		$param_set = function($key, $value) use (&$params, $options){
			$params[$key] = $value;
		};

		#+ handle negative number keys {
		if(!empty($options['map'])){
			foreach($options['map'] as $key=>$target){
				if((int)$key < 0){
					$slice = array_slice($args, $key, 1);
					if(count($slice)){
						$params[$target] = $slice[0];
					}
				}
			}
		}
		#+ }

		$numbered_params = 0;
		$current_key = '';
		foreach($args as $arg){
			if($arg[0] == '-'){
				if($arg[1] == '-'){ # case of `--key`
					if(strpos($arg, '=') !== false){ # case of `--key=bob`
						list($key, $value) = explode('=', $arg);
						$param_set($key_get(substr($key, 2)), $value);
						$current_key = false;
					}else{ # case of `--key bob`
						$current_key = $key_get(substr($arg, 2));
						$param_set_default($current_key);
					}
				}else{
					if(strlen($arg) > 2){ # case of `-abc`
						$keys = str_split(substr($arg, 1));
						$current_key = $key_get(array_pop($keys));
						$param_set_default($current_key);
						foreach($keys as $key){
							$param_set_default($key_get($key));
						}
					}else{ # case of `-a`
						$current_key = $key_get(substr($arg, 1));
						$param_set_default($current_key);
					}
				}
				# if key is a non-valued flag, don't seek value in next part
				if(in_array($current_key, $options['flags'])){
					unset($current_key);
				}
			}else{
				if($current_key){
					$param_set($current_key, $arg);
					unset($current_key);
				}else{
					$param_set_default($key_get($numbered_params), $arg);
					$numbered_params++;
				}
			}
		}
		if(!empty($options['defaults'])){
			# taken from \Grithin\Dictionary::empty_default
			$empty_default = function($source, $defaults){
				foreach($defaults as $k=>$v){
					if(!array_key_exists($k, $source) || $source[$k] === '' || $source[$k] === false || $source[$k] === null){
						$source[$k] = $v;
					}
				}
				return $source;
			};
			$params = $empty_default($params, $options['defaults']);
		}
		return $params;
	}
	/** get STDIN (piped input on CLI) */
	static function cli_stdin_get(){
		$streams = [STDIN];
		$null = NULL;
		if(stream_select($streams, $null, $null, 0)){
			return stream_get_contents(STDIN);
		}else{
			return false;
		}
	}
	/** checks whether the current environment is CLI */
	static function is_cli(){
		return (php_sapi_name() === 'cli');
	}
	/** check if the caller is the file that the current process started with */
	static function is_entry_file(){
		if(debug_backtrace()[0]['file'] == realpath($_SERVER["SCRIPT_FILENAME"])){
			return true;
		}
		return false;
	}
}