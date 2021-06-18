<?php
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
			$chars = array_map([__CLASS__, 'unicode_char'], range(0, 127)); # ascii - unicode same for first 127
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


	# https://stackoverflow.com/questions/20025030/convert-all-types-of-smart-quotes-with-php
	# Conform some ISO-8859-1 characters present in a UTF8 encoded string to standard characters
	/* Ex
	$content = iconv('ISO-8859-1', 'UTF-8', $content); # First convert to UTF-8 if the string is not already encoded as such
	$content = Strings::unicode_conform_windows($content);
	*/
	static function unicode_conform($string){
		$chr_map = array(
			// Windows codepage 1252
			"\xC2\x82" => "'", // U+0082⇒U+201A single low-9 quotation mark
			"\xC2\x84" => '"', // U+0084⇒U+201E double low-9 quotation mark
			"\xC2\x8B" => "'", // U+008B⇒U+2039 single left-pointing angle quotation mark
			"\xC2\x91" => "'", // U+0091⇒U+2018 left single quotation mark
			"\xC2\x92" => "'", // U+0092⇒U+2019 right single quotation mark
			"\xC2\x93" => '"', // U+0093⇒U+201C left double quotation mark
			"\xC2\x94" => '"', // U+0094⇒U+201D right double quotation mark
			"\xC2\x9B" => "'", // U+009B⇒U+203A single right-pointing angle quotation mark

			// Regular Unicode     // U+0022 quotation mark (")
			                       // U+0027 apostrophe     (')
			"\xC2\xAB"     => '"', // U+00AB left-pointing double angle quotation mark
			"\xC2\xBB"     => '"', // U+00BB right-pointing double angle quotation mark
			"\xE2\x80\x98" => "'", // U+2018 left single quotation mark
			"\xE2\x80\x99" => "'", // U+2019 right single quotation mark
			"\xE2\x80\x9A" => "'", // U+201A single low-9 quotation mark
			"\xE2\x80\x9B" => "'", // U+201B single high-reversed-9 quotation mark
			"\xE2\x80\x9C" => '"', // U+201C left double quotation mark
			"\xE2\x80\x9D" => '"', // U+201D right double quotation mark
			"\xE2\x80\x9E" => '"', // U+201E double low-9 quotation mark
			"\xE2\x80\x9F" => '"', // U+201F double high-reversed-9 quotation mark
			"\xE2\x80\xB9" => "'", // U+2039 single left-pointing angle quotation mark
			"\xE2\x80\xBA" => "'", // U+203A single right-pointing angle quotation mark
			);
		$chr = array_keys  ($chr_map); // but: for efficiency you should
		$rpl = array_values($chr_map); // pre-calculate these two arrays
		$string = str_replace($chr, $rpl, $string);
		return $string;
	}
	# https://stackoverflow.com/questions/20025030/convert-all-types-of-smart-quotes-with-php
	# Conform some Windows-1252 characters present in a UTF8 encoded string to standard characters
	/* Ex
	$content = iconv('Windows-1252', 'UTF-8', $content); # First convert to UTF-8 if the string is not already encoded as such
	$content = Strings::unicode_conform_windows($content);
	*/
	static function unicode_conform_windows(){
		$normalization_map = array(
			"\xC2\x80" => "\xE2\x82\xAC", // U+20AC Euro sign
			"\xC2\x82" => "\xE2\x80\x9A", // U+201A single low-9 quotation mark
			"\xC2\x83" => "\xC6\x92",     // U+0192 latin small letter f with hook
			"\xC2\x84" => "\xE2\x80\x9E", // U+201E double low-9 quotation mark
			"\xC2\x85" => "\xE2\x80\xA6", // U+2026 horizontal ellipsis
			"\xC2\x86" => "\xE2\x80\xA0", // U+2020 dagger
			"\xC2\x87" => "\xE2\x80\xA1", // U+2021 double dagger
			"\xC2\x88" => "\xCB\x86",     // U+02C6 modifier letter circumflex accent
			"\xC2\x89" => "\xE2\x80\xB0", // U+2030 per mille sign
			"\xC2\x8A" => "\xC5\xA0",     // U+0160 latin capital letter s with caron
			"\xC2\x8B" => "\xE2\x80\xB9", // U+2039 single left-pointing angle quotation mark
			"\xC2\x8C" => "\xC5\x92",     // U+0152 latin capital ligature oe
			"\xC2\x8E" => "\xC5\xBD",     // U+017D latin capital letter z with caron
			"\xC2\x91" => "\xE2\x80\x98", // U+2018 left single quotation mark
			"\xC2\x92" => "\xE2\x80\x99", // U+2019 right single quotation mark
			"\xC2\x93" => "\xE2\x80\x9C", // U+201C left double quotation mark
			"\xC2\x94" => "\xE2\x80\x9D", // U+201D right double quotation mark
			"\xC2\x95" => "\xE2\x80\xA2", // U+2022 bullet
			"\xC2\x96" => "\xE2\x80\x93", // U+2013 en dash
			"\xC2\x97" => "\xE2\x80\x94", // U+2014 em dash
			"\xC2\x98" => "\xCB\x9C",     // U+02DC small tilde
			"\xC2\x99" => "\xE2\x84\xA2", // U+2122 trade mark sign
			"\xC2\x9A" => "\xC5\xA1",     // U+0161 latin small letter s with caron
			"\xC2\x9B" => "\xE2\x80\xBA", // U+203A single right-pointing angle quotation mark
			"\xC2\x9C" => "\xC5\x93",     // U+0153 latin small ligature oe
			"\xC2\x9E" => "\xC5\xBE",     // U+017E latin small letter z with caron
			"\xC2\x9F" => "\xC5\xB8",     // U+0178 latin capital letter y with diaeresis
			);
		$chr = array_keys  ($normalization_map); // but: for efficiency you should
		$rpl = array_values($normalization_map); // pre-calculate these two arrays
		$string = str_replace($chr, $rpl, $string);
		return $string;
	}






	# deprecated
	static $regexExpandCache;
	# simpler version of regex_expand
	static function regexExpand($regex){
		$delimter = $regex[0];
		if(empty(self::$regexExpandCache[$regex])){
			self::$regexExpandCache[$regex] = self::regex_expand($regex, null, ['delimited'=>true]);
		}
		return self::$regexExpandCache[$regex];
	}

	# expand a range based regex into the actual characters
	static function regex_expand($regex, $set=null, $options=[]){
		if(!$set){
			if(!empty($options['utf8'])){
				$set = self::utf8_chars();
			}else{
				$set = self::ascii_chars();
			}

		}
		if(is_array($set)){
			$set = implode($set);
		}
		#$regex = self::utf8_encode($regex);
		if(empty($options['delimited'])){
			$regex = self::preg_quote_delimiter($regex);
			if(empty($options['bound'])){
				if(!empty($options['invert'])){
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
		$match = '';
		$string = '';
		if(func_num_args() >= 3){
			$length = rand($args[0],$args[1]);
			$match = $args[2];
		}else{
			$length = $args[0];
			//In case this is 3 arg overloaded with $match null for default
			if(isset($args[1]) && !is_int($args[1])){
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
		if(!empty($match[1])){
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
	static function match_any($regexes,$subject){
		foreach($regexes as $regex){
			if(preg_match($regex,$subject)){
				return true;
			}
		}
	}
	static function matchAny($regexes,$subject){
		return self::match_any($regexes,$subject);
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
