<?
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


	///parse a query string using a more standard less php specific rule (all repeated tokens turn into arrays, not just tokens with "[]")
	/**
	You can have this function include php field special syntax along with standard parsing.
	@param	string	string that matches form of a url query string
	@param	specialSyntax	whether to parse the string using php rules (where [] marks an array) in addition to "standard" rules
	*/
	static function parseQuery($string,$specialSyntax = false){
		$parts = \Grithin\Strings::explode('&',$string);
		$array = array();
		foreach($parts as $part){
			list($key,$value) = explode('=',$part);
			$key = urldecode($key);
			$value = urldecode($value);
			if($specialSyntax && ($matches = self::specialSyntaxKeys($key))){
				if(Arrays::isElement($matches,$array)){
					$currentValue = Arrays::getElement($matches,$array);
					if(is_array($currentValue)){
						$currentValue[] = $value;
					}else{
						$currentValue = array($currentValue,$value);
					}
					Arrays::updateElement($matches,$array,$currentValue);
				}else{
					Arrays::updateElement($matches,$array,$value);
				}
				unset($match,$matches);
			}else{
				if($array[$key]){
					if(is_array($array[$key])){
						$array[$key][] = $value;
					}else{
						$array[$key] = array($array[$key],$value);
					}
				}else{
					$array[$key] = $value;
				}
			}
		}
		return $array;
	}
	static function buildQuery($array){
		$standard = array();
		foreach($array as $k=>$v){
			//exclude standard array handling from php array handling
			if(is_array($v) && !preg_match('@\[.*\]$@',$k)){
				$key = urlencode($k);
				foreach($v as $v2){
					$standard[] = $key.'='.urlencode($v2);
				}
				unset($array[$k]);
			}
		}
		$phpquery = http_build_query($array);
		$standard = implode('&',$standard);
		return Arrays::implode('&',array($phpquery,$standard));
	}
	///get all the keys invovled in a string that represents an array.  Ex: "bob[sue][joe]" yields array('bob','sue','joe')
	static function specialSyntaxKeys($string){
		if(preg_match('@^([^\[]+)((\[[^\]]*\])+)$@',$string,$match)){
			//match[1] = array name, match[2] = all keys

			//get names of all keys
			preg_match_all('@\[([^\]]*)\]@',$match[2],$matches);

			//add array name to beginning of keys list
			array_unshift($matches[1],$match[1]);

			//clear out empty key items
			Arrays::remove($matches[1],'',true);

			return $matches[1];
		}
	}
	///appends multiple (key=>value)s to a url, replacing any key values that already exist
	/**
	@param	kvA	array of keys to values array(key1=>value1,key2=>value2)
	@param	url	url to be appended

	@example

	normal use
		Http::appendsUrl(['bob'=>'rocks','sue'=>'rocks'],'bobery.com');
			bobery.com?bob=rocks&sue=rocks
	empty string url
		Http::appendsUrl(['bob'=>'rocks','sue'=>'rocks'],'');
			?bob=rocks&sue=rocks

	overwriting existing
		Http::appendsUrl(['bob'=>'rocks','sue'=>'rocks'],'bobery.com/?bob=sucks&bill=rocks');
			bobery.com/?bill=rocks&bob=rocks&sue=rocks
	*/
	static function appendsUrl($kvA,$url=null,$replace=true){
		foreach((array)$kvA as $k=>$v){
			if(is_array($v)){
				foreach($v as $subv){
					$url = self::appendUrl($k,$subv,$url,$replace);
				}
			}else{
				$url = self::appendUrl($k,$v,$url,$replace);
			}
		}
		return $url;
	}
	///appends name=value to query string, replacing them if they already exist
	/**
	@param	name	name of value
	@param	value	value of item
	@param	url	url to be appended
	*/
	static function appendUrl($name,$value,$url=null,$replace=true){
		$url = $url !== null ? $url : $_SERVER['REQUEST_URI'];
		$add = urlencode($name).'='.urlencode($value);
		if(preg_match('@\?@',$url)){
			$urlParts = explode('?',$url,2);
			if($replace){
				//remove previous occurrence
				$urlParts[1] = preg_replace('@(^|&)'.preg_quote(urlencode($name)).'=(.*?)(&|$)@','$3',$urlParts[1]);
				if($urlParts[1][0] == '&'){
					$urlParts[1] = substr($urlParts[1],1);
				}
			}
			if($urlParts[1] != '&'){
				return $urlParts[0].'?'.$urlParts[1].'&'.$add;
			}
			return $urlParts[0].'?'.$add;
		}
		return $url.'?'.$add;
	}
	/**
	Removes key value pairs from url where key matches some regex.
	@param	regex	The regex to use for key matching.  If the regex does not contain the '@' for the regex delimiter, it is assumed the input is not a regex and instead just a string to be matched exactly against the key.  IE, '@bob@' will be considered regex while 'bob' will not
	*/
	static function removeFromQuery($regex,$url=null){
		$url = $url !== null ? $url : urldecode($_SERVER['REQUEST_URI']);
		if(!preg_match('@\@@',$regex)){
			$regex = '@^'.preg_quote($regex,'@').'$@';
		}
		$urlParts = explode('?',$url,2);
		if($urlParts[1]){
			$pairs = explode('&',$urlParts[1]);
			$newPairs = array();
			foreach($pairs as $pair){
				$pair = explode('=',$pair,2);
				#if not removed, include
				if(!preg_match($regex,urldecode($pair[0]))){
					$newPairs[] = $pair[0].'='.$pair[1];
				}
			}
			$url = $urlParts[0].'?'.implode('&',$newPairs);
		}
		return $url;
	}
	//resolves relative url paths into absolute url paths
	static  function absoluteUrl($url,$relativeTo=null){
		list($uPath,$query) = explode('?',$url);
		preg_match('@(^.*?://.*?)(/.*$|$)@',$uPath,$match);
		if(!$match){
			//url is relative, use relativeTo as base
			list($rPath) = explode('?',$relativeTo);
			preg_match('@(^.*?://.*?)(/.*$|$)@',$rPath,$match);
			$pathParts = explode('/',$match[2]);

			if($uPath){
				if($uPath[0] == '/'){
					//relative to base of site
					$base = $pathParts[0];
					$pathParts = explode('/',$base.$uPath);
				}else{
					//relative to directory, so clear page part.   ie url = "view.php?m=bob"
					array_pop($pathParts);
					$pathParts = implode('/',$pathParts).'/'.$uPath;
					$pathParts = explode('/',$pathParts);
				}
			}
		}else{
			$pathParts = explode('/',$match[2]);
		}
		$path = \Grithin\Files::absolutePath($pathParts);
		$url = $match[1].$path;
		if($query){
			$url .= '?'.$query;
		}
		return $url;
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
			if($_SERVER['HTTP_X_FORWARDED_FOR']){
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
		}elseif($_SERVER['HTTPS']){
			$protocol = 'https';
		}else{
			$protocol = 'http';
		}
		return $protocol;
	}

	///end script with xml
	static function endXml($content){
		header('Content-type: text/xml; charset=utf-8');
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo $content; exit;
	}
	///end script with json
	static function endJson($content,$encode=true){
		header('Content-type: application/json');
		if($encode){
			echo \Grithin\Tool::json_encode($content);
		}else{
			echo $content;
		}
		exit;
	}

	//it appears the browser parses once, then operating system, leading to the need to double escape the file name.  Use double quotes to encapsulate name
	static function escapeFilename($name){
		return \Grithin\Strings::slashEscape(\Grithin\Strings::slashEscape($name));
	}
	///send an actual file on the system via http protocol
	static function sendFile($path,$saveAs=null,$exit=true){
		//Might potentially remove ".." from path, but it has already been removed by the time the request gets here by server or browser.  Still removing for precaution
		$path = \Grithin\Files::removeRelative($path);
		if(is_file($path)){
			$mime = \Grithin\Files::mime($path);

			header('Content-Type: '.$mime);
			if($saveAs){
				header('Content-Description: File Transfer');
				if(strlen($saveAs) > 1){
					$fileName = $saveAs;
				}else{
					$fileName = array_pop(explode('/',$path));
				}
				header('Content-Disposition: attachment; filename="'.self::escapeFilename($fileName).'"');
			}

			echo file_get_contents($path);
		}else{
			throw new \Exception('Request handler encountered unresolvable file.  Searched at '.$path);
		}
		if($exit){
			exit;
		}
	}
	static function isAjax(){
		# general set by framework (jquery) submitting the ajax
		return (bool)$_SERVER['HTTP_X_REQUESTED_WITH'] || (bool)$_GET['_ajax'];
	}
}
Http::configure();