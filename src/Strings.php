<?
namespace Grithin;

use Grithin\Arrays;

class Strings{
	static $regexExpandCache = array();
	///expand a regex pattern to a list of characters it matches
	static function regexExpand($regex){
		$ascii = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~'."\t\n";
		if(!self::$regexExpandCache[$regex]){
			preg_match_all($regex,$ascii,$matches);
			self::$regexExpandCache[$regex] = implode('',$matches[0]);
		}
		return self::$regexExpandCache[$regex];
	}
	///generate a random string
	/**
	@note this function is overloaded and can take either two or three params.
	case 1
		@param	1	length
		@param	2	regex pattern
	case 2
		@param	1	min length
		@param	2	max length max
		@param	3	regex pattern

	Regex pattern:  Can evaluate to false (which defaults to alphanumeric).  Should be delimeted.  Defaults to '@[a-z0-9]@i'

  ex:
    ::random(12)
    ::random(12,'[a-z]')
    ::random(12,24,'[a-z]')

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
		$range = strlen($allowedChars) - 1;
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
	static function camelToSeparater($string,$separater='_'){
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
