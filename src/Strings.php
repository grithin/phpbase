<?
namespace Grithin;

use Grithin\Arrays;

class Strings{
	# code point to UTF-8 string
	static function unicode_char($i) {
		return iconv('UCS-4LE', 'UTF-8', pack('V', $i));
	}

	# UTF-8 string to code point
	static function unicode_ord($s) {
		return unpack('V', iconv('UTF-8', 'UCS-4LE', $s))[1];
	}

	# https://stackoverflow.com/questions/2748956/how-would-you-create-a-string-of-all-utf-8-characters?utm_medium=organic&utm_source=google_rich_qa&utm_campaign=google_rich_qa : (I avoided the surrogate range 0xD800–0xDFFF as they aren't valid to put in UTF-8 themselves; that would be “CESU-8”.)
	static function utf8_chars(){
		static $chars;
		if(!$chars){
			for ($i = 0; $i<0xD800; $i++){
				$chars[] = self::unicode_char($i);
			}
			for ($i = 0xE000; $i<0xFFFF; $i++){
				$chars[] = self::unicode_char($i);
			}
		}
		return $chars;
	}
	static function ascii_chars(){
		static $chars;
		if(!$chars){
			$chars = array_map([self, 'unicode_char'], range(0, 127)); # ascii - unicode same for first 127
		}
		return $chars;
	}
	static function utf8_encode($string){
		if(!mb_detect_encoding($string, 'UTF-8', true)){ # not strictly utf-8, so convert
			$string = iconv('ISO-8859-1', 'UTF-8', $string);
		}
		return $string;
	}
	static function utf8_is($string){
		return mb_detect_encoding($string) == 'UTF-8';
	}


	# deprecated
	static $regexExpandCache;
	# simpler version of regex_expand
	static function regexExpand($regex){
		$delimter = $regex[0];
		if(!self::$regexExpandCache[$regex]){
			self::$regexExpandCache[$regex] = self::regex_expand($regex, null, ['delimited'=>true]);
		}
		return self::$regexExpandCache[$regex];
	}

	# expand a range based regex into the actual characters
	static function regex_expand($regex, $set=null, $options=[]){
		if(!$set){
			if($options['utf8']){
				$set = self::utf8_chars();
			}else{
				$set = self::ascii_chars();
			}

		}
		if(is_array($set)){
			$set = implode($set);
		}
		#$regex = self::utf8_encode($regex);
		if(!$options['delimited']){
			$regex = self::preg_quote_delimiter($regex);
			if(!$options['bound']){
				if($options['invert']){
					$regex = '[^'.$regex.']';
				}else{
					$regex = '['.$regex.']';
				}
			}
			$regex = '/'.$regex.'/';
			if($options['utf8'] || self::utf8_is($set)){
				$regex .= 'u';
			}
		}
		preg_match_all($regex, $set, $matches);
		return implode($matches[0]);
	}
	# expand a regex and invert it upon a set of characters
	static function regex_invert($regex, $set=null, $options=[]){
		$chars = self::regex_expand($regex, $set, $options);
		$options = array_merge($options, ['delimited'=>false, 'bound'=>true]);
		return self::regex_expand('[^'.self::preg_quote($chars).']', $set, $options);
	}
	///generate a random string
	/*
	@note this function is overloaded and can take either two or three params.
	case 1
		@param	1	length
		@param	2	regex pattern
	case 2
		@param	1	min length
		@param	2	max length max
		@param	3	regex pattern
	case 3 (default alphanumeric pattern)
		@param	1	length

	Regex pattern:  Can evaluate to false (which defaults to alphanumeric).  Should be delimeted.  Defaults to '@[a-z0-9]@i'

  ex:
    ::random(12)
    ::random(12,'@[a-z]@')
    ::random(12,24,'@[a-z]@')

	@return	random string matching the regex pattern
	*/
	static function random(){
		$args = func_get_args();
		if(func_num_args() >= 3){
			$length = rand($args[0],$args[1]);
			$match = $args[2];
		}else{
			$length = $args[0];
			//In case this is 3 arg overloaded with $match null for default
			if(!is_int($args[1])){
				$match = $args[1];
			}
		}
		if(!$match){
			$match = '@[a-z0-9]@i';
		}
		$allowedChars = self::regexExpand($match);
		$range = mb_strlen($allowedChars) - 1;
		for($i=0;$i<$length;$i++){
			$string .= $allowedChars[mt_rand(0,$range)];
		}
		return $string;
	}

	///pluralized a word.  Limited abilities.
	/**
	@param	word	word to pluralize
	@return	pluralized form of the word
	*/
	static function pluralize($word){
		if(substr($word,-1) == 'y'){
			return substr($word,0,-1).'ies';
		}
		if(substr($word,-1) == 'h'){
			return $word.'es';
		}
		return $word.'s';
	}
	///capitalize first letter in certain words
	/**
	@param	string	string to capitalize
	@return	a string various words capitalized and some not
	*/
	static function capitalize($string,$split='\t _',$fullCaps=null,$excludes=null){
		$excludes = $excludes ? $excludes : array('to', 'the', 'in', 'at', 'for', 'or', 'and', 'so', 'with', 'if', 'a', 'an', 'of',
			'to', 'on', 'with', 'by', 'from', 'nor', 'not', 'after', 'when', 'while');
		$fullCaps = $fullCaps ? $fullCaps : array('cc');
		$words = preg_split('@['.$split.']+@',$string);
		foreach($words as &$v){
			if(in_array($v,$fullCaps)){
				$v = strtoupper($v);
			}elseif(!in_array($v,$excludes)){
				$v = ucfirst($v);
			}
		}unset($v);
		return implode(' ',$words);
	}
	///turns a camelCased string into a character separated string
	/**
	@note	consecutive upper case is kept upper case
	@param	string	string to morph
	@param	separater	string used to separate
	@return	underscope separated string
	*/
	static function camelToSeparater($string,$separater='_', $except_start=true){
		if($except_start){
			$string = preg_replace_callback('@^[A-Z]@',
				function($matches) use ($separater){return strtolower($matches[0]);},
				$string);
		}

		return preg_replace_callback('@[A-Z]@',
			function($matches) use ($separater){return $separater.strtolower($matches[0]);},
			$string);
	}

	///turns a string into a lower camel cased string
	/**
	@param	string	string to camelCase
	*/
	static function toCamel($string,$upperCamel=false,$separaters=' _-'){
		$separaters = preg_quote($separaters);
		$string = strtolower($string);
		preg_match('@['.$separaters.']*[^'.$separaters.']*@',$string,$match);
		$cString = $upperCamel ? ucfirst($match[0]) : $match[0];//first word
		preg_match_all('@['.$separaters.']+([^'.$separaters.']+)@',$string,$match);
		if($match[1]){
			foreach($match[1] as $word){
				$cString .= ucfirst($word);
			}
		}
		return $cString;
	}
	///take string and return the accronym
	static function acronym($string,$separaterPattern='@[_ \-]+@',$seperater=''){
		$parts = preg_split($separaterPattern,$string);
		foreach($parts as $part){
			$acronym[] = $part[0];
		}
		return implode($seperater,$acronym);
	}

	# normal preg_quote, but specified delimter escaping
	static function preg_quote($string, $delimiter='/'){
		return self::preg_quote_delimiter(preg_quote($string), $delimiter);
	}
	static function preg_quote_delimiter($string, $delimiter='/'){
		return preg_replace('/\\'.$delimiter.'/', '\\\\\0', $string);
	}

	///escapes the delimiter and delimits the regular expression.
	/**If you already have an expression which has been preg_quoted in all necessary parts but without concern for the delimiter
	@string	string to delimit
	@delimiter	delimiter to use.  Don't use a delimiter quoted by preg_quote
	*/
	static function pregDelimit($string,$delimiter='@'){
		return $delimiter.preg_replace('/\\'.$delimiter.'/', '\\\\\0', $string).$delimiter;
	}
	///checks if there is a regular expression error in a string
	/**
	@regex	regular expression including delimiters
	@return	false if no error, else string error
	*/
	static $regexError;
	static function regexError($regex){
		$currentErrorReporting = error_reporting();
		error_reporting($current & ~E_WARNING);

		set_error_handler(array('self','captureRegexError'));

		preg_match($regex,'test');

		error_reporting($currentErrorReporting);
		restore_error_handler();

		if(self::$regexError){
			$return = self::$regexError;
			self::$regexError == null;
			return $return;
		}
	}
	///temporary error catcher used with regexError
	static function captureRegexError($code,$string){
		self::$regexError = $string;
	}
	///quote a preg replace string
	static function pregQuoteReplaceString($str) {
		return preg_replace('/(\$|\\\\)(?=\d)/', '\\\\\1', $str);
	}
	///test matches against subsequent regex
	/**
	@param	subject	text to be searched
	@param	regexes	patterns to be matched.  A "!" first character, before the delimiter, negates the match on all but first pattern
	*/
	static function pregMultiMatchAll($subject,$regexes){
		$first = array_shift($regexes);
		preg_match_all($first,$subject,$matches,PREG_SET_ORDER);
		foreach($matches as $k=>$match){
			foreach($regexes as $regex){
				if(substr($regex,0,1) == '!'){
					if(preg_match(substr($regex,1),$match[0])){
						unset($matches[$k]);
					}
				}else{
					if(!preg_match($regex,$match[0])){
						unset($matches[$k]);
					}
				}
			}
		}
		return $matches;
	}
	static function matchAny($regexes,$subject){
		foreach($regexes as $regex){
			if(preg_match($regex,$subject)){
				return true;
			}
		}
	}
	///translate human readable size into bytes
	static function byteSize($string){
		preg_match('@(^|\s)([0-9]+)\s*([a-z]{1,2})@i',$string,$match);
		$number = $match[2];
		$type = strtolower($match[3]);
		switch($type){
			case 'k':
			case 'kb':
				return $number * 1024;
			break;
			case 'mb':
			case 'm':
				return $number * 1048576;
			break;
			case 'gb':
			case 'g':
				return $number * 1073741824;
			break;
			case 'tb':
			case 't':
				return $number * 1099511627776;
			break;
			case 'pb':
			case 'p':
				return $number * 1125899906842624;
			break;
		}
	}
	///like the normal implode but removes empty values
	static function explode($separator,$string){
		$array = explode($separator,$string);
		Arrays::remove($array);

		return array_values($array);
	}

	///escape various characters with slashes (say, for quoted csv's)
	static function slashEscape($text,$characters='\"'){
		return preg_replace('@['.preg_quote($characters).']@','\\\$0',$text);
	}
	///unescape the escape function
	static function slashUnescape($text,$characters='\"'){
		return preg_replace('@\\\(['.preg_quote($characters).'])@','$1',$text);
	}
}
