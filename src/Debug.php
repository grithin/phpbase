<?
namespace Grithin;
use \Grithin\Tool;
use \Grithin\Arrays;
use \Grithin\Time;
use \Grithin\Strings;

/// used for basic debuging
/** For people other than me, things don't always go perfectly.  As such, this class is exclusively for you.  Measure things.  Find new and unexpected features.  Explore the error messages*/
class Debug{
	static $env = ['mode'=>'dev debug info error warning notice','error_detail'=>2,'stack_exclusion'=>[], 'abbreviations'=>[], 'pretty'=>true];
	/**
	@param	env	{
		mode: < string against which log calls with mode is regex matched, Ex: "debug info error" >
		error_detail: <int>
		stack_exclusion:[<regex>,<regex>,...]
		log_file: < log file path >
		err_file: < err file path >
		max_file_size: < max size of log and err files.  ex "3m", "2g" >
		abbreviations:<paths to abbreviate> {name: <regex pattern>, ...}
		pretty: < whether to pretty print json in logs >
	}
	*/
	static function configure($env=[]){
		self::$env = Arrays::merge(self::$env, $env);
	}

	///provided for convenience to place various user debugging related values
	static $x;
	///allows for the decision to throw or trigger error based on the config
	/**
	@param	error	error string
	@param	throw	whether to throw the error (true) or trigger it (false)
	@param	type	either level of the error or the exception class to use
	*/
	static function error($error,$type=null){
		$type = $type ? $type : E_USER_ERROR;
		trigger_error($error, $type);
	}
	///throws variable class exception
	/**
	Useful if you eitherr want a variable-new-class exception, or if you want a non-scalar message

	@note	if message is not a scalar, it is JSON encoded
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

	# Attempts to find where the log folder is.  Will use $_ENV['root_folder'] is available, otherwise will use the folder of the entry script
	static function getLogFolder(){
		$file = self::$env['log_file'];

		if(!$file){
			if($_ENV['root_folder']){
				$root_folder = $_ENV['root_folder'];
			}else{
				$root_folder = dirname($_SERVER['SCRIPT_NAME']).'/';
			}
		}

		if(is_dir($root_folder.'log')){
			return $root_folder.'log/';
		}
		return $root_folder;
	}
	static function getLogFilename(){
		if(self::$env['log_file']){
			return self::$env['log_file'];
		}
		return self::getLogFolder().'log';
	}
	static function getErrFilename(){
		if(self::$env['err_file']){
			return self::$env['err_file'];
		}
		return self::getLogFolder().'err';
	}

	static $runId;
	static function getRunId(){
		if(!self::$runId){
			self::$runId = Strings::random(10);
		}
		return self::$runId;
	}

	///put variable into the log file for review
	/** Sometimes printing out the value of a variable to the screen isn't an option.  As such, this function can be useful.
	@param	var	variable to print out to file
	@param	mode	< regex mode to match that determines whether to log >
	@param	options	{
			file:<file to log to>,
		}

	*/
	static function log($var,$mode='', $options=null){
		$log = [];
		if($mode){
			if(self::$env['mode']){
				//see if matching mode, else don't log
				if(!preg_match('@'.$mode.'@i',self::$env['mode'])){
					return;
				}
			}
			$log['mode'] = $mode;
		}

		$log['rid'] = self::getRunId();
		$log['pid'] = getmypid();
		$log['time'] = date("Y-m-d H:i:s");

		if($options['file']){
			$fh = fopen($options['file'],'a+');
		}else{
			$filename = self::getLogFilename();
			$fh = self::open($filename);	}

		$trace = self::getExternalStack(debug_backtrace())[0];

		$log['file'] = self::abbreviateFilePath($trace['file']);
		$log['line'] = $trace['line'];
		$log['value'] = Tool::to_jsonable($var);

		self::write($fh,$log);
		fclose($fh);
	}
	static function getExternalStack($stack){
		$skip = 0;
		foreach($stack as $item){
			if(!$item['file'] || $item['file'] == __FILE__){
				$skip++;
			}else{
				break;
			}
		}
		return array_slice($stack,$skip);

	}
	///get a line from a file
	/**
	@param	file	file path
	@param	line	line number
	*/
	static function getLine($file,$line){
		if($file){
			$f = file($file);
			$code = substr($f[$line-1],0,-1);
			return preg_replace('@^\s*@','',$code);
		}
	}
	static function handleException($exception){
		self::handleError(E_USER_ERROR,$exception->getMessage(),$exception->getFile(),$exception->getLine(),null,$exception->getTrace(),'EXCEPTION: '.get_class($exception));
	}
	///print a boatload of information to the load so that even your grandma could fix that bug
	/**
	@param	eLevel	error level
	@param	eStr	error string
	@param	eFile	error file
	@param	eLine	error line
	*/
	static function handleError($eLevel,$eStr,$eFile,$eLine,$context=null,$bTrace=null,$type='ERROR'){
		if(ini_get('error_reporting') == 0){# @ Error control operator used
			return;
		}

		$err = [];

		$err['message'] = $eStr;
		$err['file'] = self::abbreviateFilePath($eFile);
		$err['type'] = $type;
		$err['line'] = $eLine;

		$err['rid'] = self::getRunId();
		$err['pid'] = getmypid();
		$err['time'] = date("Y-m-d H:i:s");

		if(self::$env['error_detail'] > 0){
			if(!$bTrace){
				$bTrace = debug_backtrace();
			}

			$bTrace = self::getExternalStack($bTrace);

			//remove undesired stack points
			foreach($bTrace as $k=>&$v){
				$v['shortName'] = self::abbreviateFilePath($v['file']);
				foreach(self::$env['stack_exclusion'] as $exclusionPattern){
					if(!$v['file']){
						$unnamed++; # Skip the no-file stack items leading up to an excluded file
					}else{
						if($found = preg_match($exclusionPattern,$v['shortName'])){
							array_splice($bTrace,$k - $unnamed, 1 + $unnamed);
						}
						$unnamed = 0;
					}
				}
			}

			$err['stack'] = [];
			foreach($bTrace as $v){
				$stackItem = [];
				$stackItem['line'] = $v['line'];
				$stackItem['file'] = $v['shortName'];
				if($v['class']){
					$stackItem['class'] = $v['class'].$v['type'];
				}
				$stackItem['function'] = $v['function'];
				$line_string = self::getLine($v['file'],$v['line']);
				if($line_string){
					$stackItem['line_string'] = $line_string;
				}

				if($v['args'] && self::$env['error_detail'] > 1){
					$stackItem['args'] = Tool::to_jsonable($v['args']);
				}
				$err['stack'][] = $stackItem;
			}

			if(self::$env['error_detail'] > 2){
				$err['server'] = Tool::to_jsonable($_SERVER);
				$err['Files::'] = Files::getIncluded();
			}
		}
		//identify error
		$err['id'] = sha1($err['time'].$err['rid'].$err['message']);


		$filename = self::getErrFilename();
		$fh = self::open($filename);
		self::write($fh, $err);

		if(ini_get('display_errors')){
			self::sendout(json_encode($err, JSON_PRETTY_PRINT));
		}

		exit;
	}

	static $out;
	static $usleepOut = 0;///< usleep each out call
	///print a variable with file and line context, along with count
	/**
	@param	var	any type of var that print_r prints
	*/
	static function out(){
		self::$out['i']++;

		$trace = self::getExternalStack(debug_backtrace())[0];

		$args = func_get_args();
		foreach($args as $var){
			self::sendout(
			[
				'file'=>self::abbreviateFilePath($trace['file']),
				'line'=>$trace['line'],
				'i'=>self::$out['i'],
				'value'=> $var
			]);
		}
		if(self::$usleepOut){
			usleep(self::$usleepOut);
		}
	}
	///exists after using self::out on inputs
	static function quit(){
		$args = func_get_args();
		call_user_func_array(array(self,'out'),$args);
		exit;
	}
	///Encapsulates in <pre> if determined script not being run on console (ie, is being run on web)
	static function sendout($output){
		if(!is_scalar($output)){
			$output = Tool::flat_json_encode($output, JSON_PRETTY_PRINT);
		}
		if(php_sapi_name() === 'cli'){
			echo $output;
		}else{
			echo '<pre>'.$output.'</pre>';
		}
	}
	static function abbreviateFilePath($path){
		foreach(self::$env['abbreviations'] as $name=>$abbr){
			$path = preg_replace('@'.$abbr.'@',$name.':',$path);
		}
		return $path;

	}


	/*
	-	make file if necessary
	-	erase file if over self::$env['max_file_size']
	*/
	static function open($file){
		if(!is_file($file)){
			touch($file);
			chmod($file,0777);
			clearstatcache();
		}
		$mode = 'a+';
		if(self::$env['max_file_size']){
			if(!file_exists($file) || self::$env['max_file_size'] && filesize($file)>Tool::byteSize(self::$env['max_file_size'])){
				$mode = 'w';
			}
		}
		return fopen($file,$mode);
	}
	static function write($fh, $var){
		if(self::$env['pretty']){
			fwrite($fh, json_encode($var, JSON_PRETTY_PRINT)."\n");
		}else{
			fwrite($fh, json_encode($var)."\n");
		}
	}
}