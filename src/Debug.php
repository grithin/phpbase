<?
namespace Grithin;
use \Grithin\Tool;
use \Grithin\Arrays;
use \Grithin\Time;
use \Grithin\Strings;

/// used for basic debuging
/** For people other than me, things don't always go perfectly.  As such, this class is exclusively for you.  Measure things.  Find new and unexpected features.  Explore the error messages*/
class Debug{
	///throws variable class exception
	/**
	Useful if you eitherr want a variable-new-class exception, or if you want a non-scalar message


	@note	if message is not a scalar, it is JSON encoded

	@note	on performance, eval is approximately twice as long as directly throwing a pre-defined exception class.
	*/
	static function toss($message=null,$type='',$code=0,$previous=null){
		if($type){
			if(!class_exists($type,false)){
				eval('class '.$type.' extends Exception{}');
			}
		}else{
			$type='Exception';
		}
		if(!is_scalar($message)){
			$message = json_encode($message);
		}
		throw new $type($message,$code,$previous);
	}

	# in multithreading, the fork will have a separate process id.  It can be helpful to know if two separate process ids are within the same run id in logs
	static function getRunId(){
		if(empty($GLOBALS['_run_id'])){
			$GLOBALS['_run_id'] = Strings::random(10);
		}
		return $GLOBALS['_run_id'];
	}

	///get a line from a file
	/**
	@param	file	file path
	@param	line	line number
	*/
	static function getLine($file,$line){
		if($file){
			if(!is_file($file)){
				# handle eval'd code specified as file
				# ex: /media/bob/test.php(11) : eval()'d code
				if(strstr($file, 'eval()') !== false){
					$eval_file_string = $file;
					$file_part = implode(':', array_slice(explode(':', $file),0,-1));
					preg_match('@^(.*)\(([0-9]+)\)\s$@', $file_part, $match);
					$file = $match[1];
					$line = $match[2];
					if(!is_file($file)){
						throw new \Exception('Could not parse eval for get line "'.$eval_file_string.'"');
					}
				}else{
					throw new \Exception('Could not get line from file "'.$file.'"');
				}

			}
			$f = file($file);
			$code = substr($f[$line-1],0,-1);
			return preg_replace('@^\s*@','',$code);
		}
	}


	static function conform_backtrace_item($v){
		$stackItem = [];

		if(isset($v['file'])){
			$stackItem['file'] = preg_replace('@^'.preg_quote($_SERVER['DOCUMENT_ROOT']).'@', '', $v['file']);
		}else{
			$stackItem['file'] = null;
		}
		$stackItem['line'] = isset($v['line']) ? $v['line'] : null;
		if(!empty($v['class'])){
			$stackItem['class'] = $v['class'].$v['type'];
		}

		$stackItem['function'] = isset($v['function']) ? $v['function'] : null;

		if($stackItem['file'] && $stackItem['line']){
			$line_string = self::getLine($v['file'],$v['line']);
			if($line_string){
				$stackItem['line_string'] = $line_string;
			}
		}


		if(isset($v['args'])){
			$stackItem['args'] = Tool::to_jsonable($v['args']);
		}
		return $stackItem;
	}
	static function conform_backtrace($backtrace){
		$conformed = [];
		foreach($backtrace as $v){
			$conformed[] = self::conform_backtrace_item($v);
		}
		return $conformed;
	}
	static function context(){
		$context = [
			'rid'=>$log['rid'] = self::getRunId(),
			'pid'=>getmypid(),
			'time'=>self::time(), # date functions will return .000000 as microtime, so can't rely on them
			'$_SERVER'=> Tool::to_jsonable($_SERVER),
			'$_POST' => Tool::to_jsonable($_POST),
			'$_GET' => Tool::to_jsonable($_GET),
		];
		return $context;
	}
	static function time(){
		return date(sprintf('Y-m-d\TH:i:s%sP', substr(microtime(), 1, 8)));
	}

	static function caller(){
		return self::conform_backtrace_item(debug_backtrace(null,2)[1]);
	}
	static function backtrace(){
		return array_slice(self::conform_backtrace(debug_backtrace()), 1);
	}

	static function error_level($level_code){
			switch($level_code)
				{
				case E_ERROR: // 1 //
					return 'E_ERROR';
				case E_WARNING: // 2 //
					return 'E_WARNING';
				case E_PARSE: // 4 //
					return 'E_PARSE';
				case E_NOTICE: // 8 //
					return 'E_NOTICE';
				case E_CORE_ERROR: // 16 //
					return 'E_CORE_ERROR';
				case E_CORE_WARNING: // 32 //
					return 'E_CORE_WARNING';
				case E_CORE_ERROR: // 64 //
					return 'E_COMPILE_ERROR';
				case E_CORE_WARNING: // 128 //
					return 'E_COMPILE_WARNING';
				case E_USER_ERROR: // 256 //
					return 'E_USER_ERROR';
				case E_USER_WARNING: // 512 //
					return 'E_USER_WARNING';
				case E_USER_NOTICE: // 1024 //
					return 'E_USER_NOTICE';
				case E_STRICT: // 2048 //
					return 'E_STRICT';
				case E_RECOVERABLE_ERROR: // 4096 //
					return 'E_RECOVERABLE_ERROR';
				case E_DEPRECATED: // 8192 //
					return 'E_DEPRECATED';
				case E_USER_DEPRECATED: // 16384 //
					return 'E_USER_DEPRECATED';
				}
			return $level_code;
		}

	/*	params
	< excrption >
	< additional > < array of additional stacks from chained exceptions (exceptions called within exception handling) >
	*/
	static function conform_exception($exception, $options=[]){
		$options['additional'] = isset($options['additional']) ? (array)$options['additional'] : [];
		if($previous = $exception->getPrevious()){
			$options['additional']['previous'] = self::conform_error(E_USER_ERROR,$previous->getMessage(),$previous->getFile(),$previous->getLine(),$previous->getTrace(), ['context'=>false]);
		}
		return self::conform_error(E_USER_ERROR,$exception->getMessage(),$exception->getFile(),$exception->getLine(),$exception->getTrace(), $options);
	}

	///print a boatload of information to the load so that even your grandma could fix that bug
	/**
	@param	eLevel	error level
	@param	eStr	error string
	@param	eFile	error file
	@param	eLine	error line
	*/
	static function conform_error($eLevel,$eStr,$eFile,$eLine,$backtrace=null, $options=[]){
		$options = array_merge(['context'=>true], $options);

		if($backtrace === null){
			$backtrace = self::backtrace();
		}else{
			$backtrace = self::conform_backtrace($backtrace);
		}

		$error = [
			'level' => self::error_level($eLevel),
			'message' => $eStr,
			'file' => $eFile,
			'line' => $eLine,


		];
		if($eFile && $eLine){
			$error['line_string'] = self::getLine($eFile, $eLine);
		}
		$error['backtrace'] = $backtrace;
		if(isset($options['additional'])){
			$error['additional'] = $options['additional'];
		}
		if(!empty($options['context'])){
			$error['context'] = self::context();
		}

		return $error;
	}


	static function quit($data, $caller=false){
		if(!$caller){
			$caller = self::caller();
		}
		self::out($data, $caller);
		echo "\n";
		exit; # don't really use die, since it will consider parameter exit code
	}
	static function out($data, $caller=false){
		if(!$caller){
			$caller = self::caller();
		}
		$output = "\n".self::pretty($data, $caller);
		$is_cli = php_sapi_name() === 'cli';
		if(!$is_cli){
			echo '<pre>'.$output.'</pre>';
		}else{
			echo $output;
		}
	}
	static $pretty_increment = 0;
	# data as a pretty, identifying string
	static function pretty($data, $caller=false){
		if(!$caller){
			$caller = self::caller();
		}
		$string = '['.$caller['file'].':'.$caller['line'].'](#'.self::$pretty_increment.') : ';
		if(!Tool::is_scalar($data)){
			$string .= self::json_pretty($data);
		}else{
			$string .= $data;
		}
		self::$pretty_increment++;
		return $string;
	}
	static function json_pretty($data){
		$json = Tool::flat_json_encode($data, JSON_PRETTY_PRINT);
		return self::json_format_pretty($json);
	}
	# spacing adjustments on standard prettied json (non-breaking)
	static function json_format_pretty($json_string){
		$start_bracket = '[\[\{]+';
		$end_bracket = '[\]\}]+';
		$start_line = '(?<=\n|^)';
		$end_line = '(?=\n|$)';
		# condense start brackets
		$json_string = preg_replace('@'.$start_line.'(\s*'.$start_bracket.')\n\s*@','$1', $json_string);
		# condense end brackets
		return preg_replace('@\n\s*('.$end_bracket.',?)'.$end_line.'@','$1', $json_string);
	}

	static function current_exception_handler(){
		$current = set_exception_handler(function(){});
		restore_exception_handler();
		return $current;
	}
	static function current_error_handler(){
		$current = set_error_handler(function(){});
		restore_error_handler();
		return $current;
	}
	static function stderr($data){
		return fwrite(STDERR, $data);
	}
	static function backtrace_clear_debug($conformed_backtrace){
		$extract = Arrays::pick($conformed_backtrace, ['message', 'context']);
		unset($conformed_backtrace['message'], $conformed_backtrace['context']);

		$backtrace = $conformed_backtrace['backtrace'];
		unset($conformed_backtrace['backtrace']);
		array_unshift($backtrace, $conformed_backtrace);

		foreach($backtrace as $k=>$item){
			if(preg_match('@phpbase/src/Debug\.php$|phpbase/src/traits/testCall\.php$@',$item['file'])){
				continue;
			}
			if($item['function'] == '__methodExists'){
				continue;
			}
			if(empty($item['file'])){
				continue;
			}
			$start = $k;
			break;
		}
		$backtrace = array_slice($backtrace, $start);

		$first = array_merge(['message'=>$extract['message']], (array)array_shift($backtrace));
		$first['backtrace'] = $backtrace;
		$first['context'] = $extract['context'];
		return $first;
	}
	static function backtrace_clear_framework($conformed_backtrace){
		$conformed_backtrace = self::backtrace_clear_debug($conformed_backtrace);
		$extract = Arrays::pick($conformed_backtrace, ['message', 'context']);
		unset($conformed_backtrace['message'], $conformed_backtrace['context']);

		$backtrace = $conformed_backtrace['backtrace'];
		unset($conformed_backtrace['backtrace']);
		array_unshift($backtrace, $conformed_backtrace);

		foreach($backtrace as $k=>$item){
			if(preg_match('@phpbase/src/|phpdb/src/@',$item['file'])){
				continue;
			}
			if(empty($item['file'])){
				continue;
			}
			$start = $k;
			break;
		}
		$backtrace = array_slice($backtrace, $start);

		$first = array_merge(['message'=>$extract['message']], (array)array_shift($backtrace));
		$first['backtrace'] = $backtrace;
		$first['context'] = $extract['context'];
		return $first;
	}
}