<?php
namespace Grithin;
use Grithin\Tool;
use Grithin\Arrays;
class Http{
	static $env;
	/**
	@param	env	{
		mode:<name>
		'debug.detail':<int>
		'debug.stackExclusions':[<regex>,<regex>,...]
		projectName:
		'log.file':
		'log.folder':
		'log.size':<size in bytes>
		'abbreviations':<paths to abbreviate> {name:match}
	}
	*/
	static function configure($env=[]){
		$env = Arrays::merge(['loadBalancerIps'=>[]], $_ENV, $env);
		self::$env = $env;
	}



/*#from Dylan at WeDefy dot com
	// 301 Moved Permanently
header("Location: /foo.php",TRUE,301);

// 302 Found
header("Location: /foo.php",TRUE,302);
header("Location: /foo.php");

// 303 See Other
header("Location: /foo.php",TRUE,303);

// 307 Temporary Redirect
header("Location: /foo.php",TRUE,307);
?>

The HTTP status code changes the way browsers and robots handle redirects, so if you are using header(Location:) it's a good idea to set the status code at the same time.  Browsers typically re-request a 307 page every time, cache a 302 page for the session, and cache a 301 page for longer, or even indefinitely.  Search engines typically transfer "page rank" to the new location for 301 redirects, but not for 302, 303 or 307. If the status code is not specified, header('Location:') defaults to 302.
	*/
	///relocate browser
	/**
	@param	location	location to relocate to
	@param	type	type of relocation; head for header relocation, js for javascript relocation
	@param	code	the http status code.  Note, generally this function is used after a post request is parsed, so 303 is the default
	*/
	static function redirect($location=null,$type='head',$code=null){
		if($type == 'head'){
			if(!$location){
				$location = $_SERVER['REQUEST_URI'];
			}
			$code = $code ? $code : 303;
			header('Location: '.$location,true,$code);
		}elseif($type=='js'){
			echo '<script type="text/javascript">';
			if(Tool::isInt($location)){
				if($location==0){
					$location = $_SERVER['REQUEST_URI'];
					echo 'window.location = '.$_SERVER['REQUEST_URI'].';';
				}else{
					echo 'javascript:history.go('.$location.');';
				}
			}else{
				echo 'document.location="'.$location.'";';
			}
			echo '</script>';
		}
		exit;
	}
	# like redirect, but forces http
	static function redirectHttp($location){
		Http::redirect("http://$_SERVER[HTTP_HOST]".$location);
	}
	# like redirect, but forces https
	static function redirectHttps($location){
		Http::redirect("https://$_SERVER[HTTP_HOST]".$location);
	}

	static $ip;
	///Get the ip at a given point in either HTTP_X_FORWARDED_FOR or just REMOTE_ADDR
	/**
	$config['loadBalancerIps'] is removed from 	HTTP_X_FORWARDED_FOR, after which slicePoint applies
	*/
	static function ip($slicePoint=-1){
		if(!self::$ip){
			if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
				#get first ip (should be client's ip)
				#X-Forwarded-For: clientIPAddress, previousLoadBalancerIPAddress-1, previousLoadBalancerIPAddress-2
				$ips = preg_split('@\s*,\s*@',$_SERVER['HTTP_X_FORWARDED_FOR']);
				if(class_exists('Config',false) && self::$env['loadBalancerIps']){
					$ips = array_diff($ips,self::$env['loadBalancerIps']);
				}

				self::$ip = array_pop(array_slice($ips,$slicePoint,1));
				//make sure ip conforms (since this is a header variable that can be manipulated)
				if(!preg_match('@[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}@',self::$ip)){
					self::$ip = $_SERVER['REMOTE_ADDR'];
				}
			}else{
				self::$ip = $_SERVER['REMOTE_ADDR'];
			}
		}
		return self::$ip;
	}
	static function protocol(){
		if(self::$env['loadBalancerIps'] && $_SERVER['HTTP_X_FORWARDED_PROTO']){
			  $protocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
		}elseif(!empty($_SERVER['HTTPS'])){
			$protocol = 'https';
		}else{
			$protocol = 'http';
		}
		return $protocol;
	}

	static function respond_with_xml($content=''){
		header('Content-type: text/xml; charset=utf-8');
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo $content;
	}
	# end script with xml
	static function end_with_xml($content=''){
		self::respond_with_xml($content);
		exit;
	}
	static function respond_with_json($content,$encode=true){
		header('Content-type: application/json');
		if($encode){
			echo \Grithin\Tool::json_encode($content);
		}else{
			echo $content;
		}
	}
	# end script with json
	static function end_with_json($content,$encode=true){
		self::respond_with_json($content,$encode);
		exit;
	}

	//it appears the browser parses once, then operating system, leading to the need to double escape the file name.  Use double quotes to encapsulate name
	static function filename_escape($name){
		return \Grithin\Strings::slashEscape(\Grithin\Strings::slashEscape($name));
	}
	///send an actual file on the system via http protocol
	/*	params
	saveAs	< `true` to indicate use path filename , otherwise, the actual name desired >
	*/
	static function file_send($path,$saveAs=null,$exit=true){
		//Might potentially remove ".." from path, but it has already been removed by the time the request gets here by server or browser.  Still removing for precaution
		$path = \Grithin\Files::removeRelative($path);
		if(is_file($path)){
			$mime = \Grithin\Files::mime($path);
			header('Content-Type: '.$mime);

			if($saveAs){
				if(strlen($saveAs) <= 1){
					$saveAs = array_pop(explode('/',$path));
				}
				self::set_filename_header($saveAs);
			}

			readfile($path);

			if($exit){
				exit;
			}
		}else{
			throw new \Exception('Request handler encountered unresolvable file.  Searched at '.$path);
		}
	}
	static function set_filename_header($save_as){
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename="'.self::filename_escape($save_as).'"');
	}

	static function send_file_content($content, $mime, $save_as=null, $exit=true){
		header('Content-Type: '.$mime);
		if($save_as){
			self::set_filename_header($save_as);
		}
		echo $content;
		if($exit){
			exit;
		}
	}
	# Use standard HTTP Header indicator or _GET['_ajax'] to determine if request is ajax based
	static function isAjax(){
		# general set by framework (jquery) submitting the ajax
		return (bool)$_SERVER['HTTP_X_REQUESTED_WITH'] || (bool)$_GET['_ajax'];
	}
	static function is_api_call(){
		if(!empty($_REQUEST['_api'])){
			return true;
		}
		# can reasonably expect that a request for json is an API call
		if(preg_match('@application/json@', $_SERVER['HTTP_ACCEPT'])){
			return true;
		}
	}

	# Respond to an http request, but don't end the script
	static function respond_with($response){
		ignore_user_abort(true); # Set whether a client disconnect should abort script execution
		set_time_limit(0); # allow script to run forever

		ob_start();
		echo $response;
		header('Connection: close');
		header("Content-Encoding: none"); # if compressed, content size will be smaller, and requester will continue waiting
		header('Content-Length: '.ob_get_length());
		ob_end_flush(); # flush inner layer
		ob_flush(); # flush all layers
		flush(); # push to browser
	}
	static function header_cache_time($time){
		$time = new \Grithin\Time($time);
		header("Expires: ".$time->format('D, d M Y H:i:s'));
		header("Pragma: cache");
		header("Cache-Control: max-age=".(-$time->age('seconds')));
	}
	/*
	PHP will organize $_FILES like (uploading input.file.test.bob and input.file.test.bill)
		{"input": {
		  "name": {
				"file": {
					 "test": {
						  "bob": "test1.csv",
						  "bill": "test2.csv"}}},
		  "type": {
				"file": {
					 "test": {
						  "bob": "text\/csv",
						  "bill": "text\/csv"}}},
		  "tmp_name": {
				"file": {
					 "test": {
						  "bob": "\/tmp\/phphmtApT",
						  "bill": "\/tmp\/phpdDMI1r"}}},
		  "error": {
				"file": {
					 "test": {
						  "bob": 0,
						  "bill": 0}}},
		  "size": {
				"file": {
					 "test": {
						  "bob": 5478,
						  "bill": 2892}}}}}
	wherein the first key is not categorized under one of (name, type, tmp_name, error, size), but the remaining keys are.

	This function moves the categories into the keys, instead of the other way around:
	{"input": {
	  "file": {
			"test": {
				 "bob": {
					  "name": "test1.csv",
					  "type": "text\/csv",
					  "tmp_name": "\/tmp\/phpwz2pRx",
					  "error": 0,
					  "size": 5478},
				 "bill": {
					  "name": "test2.csv",
					  "type": "text\/csv",
					  "tmp_name": "\/tmp\/phpAoe0UK",
					  "error": 0,
					  "size": 2892}}}}}
	*/
	function files_variable_organize($files=null){
		$files = $files ? $files : $_FILES;
		$traverse = function($v, $prefix_path = '') use (&$traverse){
			$paths_values = [];
			if(is_array($v)){
				foreach($v as $k=>$v2){
					if($prefix_path){
						$new_prefix = $prefix_path.'.'.$k;
					}else{
						$new_prefix = $k;
					}
					$paths_values = array_merge($paths_values, $traverse($v2, $new_prefix));
				}
			}else{
				$paths_values[] = [$prefix_path, $v];
			}
			return $paths_values;
		};

		$organized_files = [];
		foreach($files as $top_key=>$type_level){
			if(is_array($type_level['name'])){
				# Note: types = ['error','name','size','tmp_name','type'];
				foreach($type_level as $type=>$hierarchy){
					$paths_values = $traverse($hierarchy, $top_key);

					foreach($paths_values as $path_value){
						$organized_files = \Grithin\Arrays::set($path_value[0].'.'.$type, $path_value[1], $organized_files);
					}
				}
			}else{
				$organized_files[$top_key] = $type_level;
			}
		}
		return $organized_files;
	}
}
Http::configure();