<?
namespace Grithin;
use \Grithin\Tool;
use \Grithin\Arrays;
use \Grithin\Time;
use \Grithin\Strings;

/// used for basic debuging
/** For people other than me, things don't always go perfectly.  As such, this class is exclusively for you.  Measure things.  Find new and unexpected features.  Explore the error messages*/
class Debug{
	static $env = ['mode'=>'dev debug info error warning notice','error_detail'=>2,'stack_exclusion'=>[], 'abbreviations'=>[]];
	/**
	@param	env	{
		mode: < string against which log calls with mode is regex matched, Ex: "debug info error" >
		error_detail: <int>
		stack_exclusion:[<regex>,<regex>,...]
		log_file: < log file path >
		err_file: < err file path >
		max_file_size: < max size of log and err files.  ex "3m", "2g" >
		abbreviations:<paths to abbreviate> {name: <regex pattern>, ...}
	}
	*/
	static function configure($env=[]){
		self::$env = Arrays::merge(self::$env, $env);

		if(!self::$env['log_file'] || !self::$env['err_file']){
			throw new \Exception('Must specify both log_file and err_file');
		}
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
	/// benchments on time and memory
	static $benchGroups;
	///Take a bench
	/** Allows you to time things and get memory usage
	@param	name	the name of the bench to be printed out with results.  To get the timing between events, the name should be the same.
	Ex
		Debug::bench();
		sleep(1);
		Debug::bench();
		sleep(1);
		Debug::bench();
		sleep(1);
		Debug::quit(Debug::benchDone());
	*/
	static function bench($name='std'){
		$next = count(self::$benchGroups[$name]);
		self::$benchGroups[$name][$next]['time'] = microtime(true);
		self::$benchGroups[$name][$next]['mem'] = memory_get_usage();
		self::$benchGroups[$name][$next]['mem.max'] = memory_get_peak_usage();
	}
	///calls bench and prints results
	/**
	@return	returns an array with results
	*/
	static function benchDone($groups=null){
		if(!$names){
			$names = array_keys(self::$benchGroups);
		}
		$names = (array)$names;

		//close any open benchs unset static benchGroups
		foreach($names as $name){
			self::bench($name);
			$benchGroups[$name] = self::$benchGroups[$name];
			unset(self::$benchGroups[$name]);
		}
		$out = [];
		foreach($benchGroups as $name=>$bench){
			$out[$name] = ['diff'=>[]];
			$current = current($bench);
			$mem['min'] = $current['mem'];
			$mem['max'] = $current['mem.max'];
			$time = 0;
			while($next = next($bench)){
				$outItem = &$out[$name]['diff'][count($out[$name]['diff'])];
				$time += $outItem['time'] = $next['time'] - $current['time'];
				$outItem['mem'] = $next['mem'] - $current['mem'];
				$outItem['mem.max'] = $next['mem.max'] - $current['mem.max'];
				if($current['mem.max'] > $mem['max']){
					$mem['max'] = $current['mem.max'];
				}
				$current = $next;
			}
			$out[$name]['total']['mem'] = $mem;
			$out[$name]['total']['time'] = $time;
		}
		return $out;
	}
	static $runId;
	///put variable into the log file for review
	/** Sometimes printing out the value of a variable to the screen isn't an option.  As such, this function can be useful.
	@param	var	variable to print out to file
	@param	options	{
		title:<line title>,
		file:<file to log to>,
		mode:<regex mode to match that determines whether to log
	*/
	static function log($var,$options=null){
		$title = $options['title'];
		if($options['mode'] && self::$env['mode']){
			//see if matching mode, else don't log
			if(!preg_match('@'.$options['mode'].'@i',self::$env['mode'])){
				return;
			}
			$title .= '['.$options['mode'].']';
		}
		if(!self::$runId){
			self::$runId = Strings::random(10);	}
		if($options['file']){
			$fh = fopen($options['file'],'a+');
		}else{
			$fh = self::open(self::$env['log_file']);	}
		$title = $title ? ' | '.$title : '';

		$trace = self::getTraceItem();
		$file = self::abbreviateFilePath($trace['file']);
		$line = $trace['line'];
		fwrite($fh,"+=+=+=+ ".(new Time)." | RID ".self::$runId." PID ".getmypid()." | ".$file.':'.$line.$title." +=+=+=+\n".self::toString($var)."\n");
		fclose($fh);
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

		$code = self::getLine($eFile,$eLine);
		$eFile = self::abbreviateFilePath($eFile);
		$eFile = preg_replace('@'.PR.'@','',$eFile);
		$header = "+=+=+=+ ".date("Y-m-d H:i:s")." | $type | ".self::abbreviateFilePath($eFile).":$eLine +=+=+=+\n$eStr\n";

		$err= '';
		if(self::$env['error_detail'] > 0){
			if(!$bTrace){
				$bTrace = debug_backtrace();
			}

			//php has some odd backtracing so need various conditions to remove excessive data
			if($bTrace[0]['file'] == '' && $bTrace[0]['class'] == 'Debug'){
				array_shift($bTrace);
			}

			//remove undesired stack points, and non-named points stemming from
			foreach($bTrace as $k=>&$v){
				$v['shortName'] = self::abbreviateFilePath($v['file']);
				foreach(self::$env['stack_exclusion'] as $exclusionPattern){
					if(!$v['file']){
						$unnamed++;
					}else{
						if($found = preg_match($exclusionPattern,$v['shortName'])){
							array_splice($bTrace,$k - $unnamed, 1 + $unnamed);
						}
						$unnamed = 0;
					}
				}
			}

			foreach($bTrace as $v){
				$err .= "\n".'(-'.$v['line'].'-) '.$v['shortName']."\n";
				$code = self::getLine($v['file'],$v['line']);
				if($v['class']){
					$err .= "\t".'Class: '.$v['class'].$v['type']."\n";
				}
				$err .= "\t".'Function: '.$v['function']."\n";
				if($code){
					$err .= "\t".'Line: '.$code."\n";
				}
				if($v['args'] && self::$env['error_detail'] > 1){
					$err .= "\t".'Arguments: '."\n";
					$args = self::toString($v['args']);
					$err .= substr($args,2,-2)."\n";
					/*
					$err .= preg_replace(
							array("@^array \(\n@","@\n\)$@","@\n@"),
							array("\t\t",'',"\n\t\t"),
							$args)."\n";*/
				}
			}
			if(self::$env['error_detail'] > 2){
				$err.= "\nServer Var:\n:".self::toString($_SERVER);
				$err.= "\nRequest-----\nUri:".$_SERVER['REQUEST_URI']."\nVar:".self::toString($_REQUEST);
				$err.= "\n\nFile includes:\n".self::toString(Files::getIncluded());
			}
			$err.= "\n";
		}
		//identify error
		$errorHash = sha1($err);
		$header = 'Error Id: '.$errorHash."\n".$header;
		$err = $header.$err;

		$fh = self::open(self::$env['err_file']);
		fwrite($fh,$err);

		if(ini_get('display_errors')){
			self::sendout($err);
		}

		exit;
	}
	/// don't use class::__toString method on self::toString
	static $ignoreToString = false;
	///since var_export fails on objects pointing at each other, and var_dump is unreadable
	/**
	@param	objectMaxDepth	objectDepth at which function will no longer parse or show object attributes
	*/
	static function toString($variable,$objectMaxDepth=2,$depth=0,$objectDepth=0){
		if(is_object($variable)){
			if(method_exists($variable,'__toString') && !self::$ignoreToString){
				return (string)$variable;
			}else{
				if($objectDepth < $objectMaxDepth){
					$return = get_class($variable);
					$vars = get_object_vars($variable);
					if($vars){
						$return .= ' '.self::toString($vars,$objectMaxDepth,$depth+1,$objectDepth+1);
					}
					return $return;
				}else{
					return get_class($variable).' !!!Max Depth';
				}
			}
		}elseif(is_array($variable)){
			$prefix = "\n".str_repeat("\t",$depth+1);
			foreach($variable as $k=>$variable){
				$return .= $prefix.var_export($k,1).' : '.self::toString($variable,$objectMaxDepth,$depth+1,$objectDepth);
			}
			return $return ? '['.$return."\n".str_repeat("\t",$depth)."]" : '[]';
		}else{
			return var_export($variable,1);
		}
	}
	static function abbreviateFilePath($path){
		foreach(self::$env['abbreviations'] as $name=>$abbr){
			$path = preg_replace('@'.$abbr.'@',$name.':',$path);
		}
		return $path;

	}
	///print a variable to self::toString and quit
	/** Clears butter, outputs variable without context
	@param	var	any type of var that toString prints
	*/
	static function end($var=null){
		$content=ob_get_clean();
		if($var){
			$content .= "\n".self::toString($var);
		}
		self::sendout($content);
		exit;
	}
	static function getTraceItem(){
		$trace = debug_backtrace();
		array_shift($trace);

		foreach($trace as $part){
			if($part['function'] != 'call_user_func_array' && $part['line']){
				$item = $part;
				break;
			}
		}
		return (array)$item;
	}
	static $out;
	static $usleepOut = 0;///<usleep each out call
	///print a variable with file and line context, along with count
	/**
	@param	var	any type of var that print_r prints
	*/
	static function out(){
		self::$out['i']++;
		$trace = self::getTraceItem();

		$args = func_get_args();
		foreach($args as $var){
			$file = self::abbreviateFilePath($trace['file']);
			self::sendout("[".$file.':'.$trace['line']."] ".self::$out['i'].": ".self::toString($var)."\n");
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
		if(php_sapi_name() === 'cli'){
			echo $output;
		}else{
			echo '<pre>'.$output.'</pre>';
		}
	}
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
}