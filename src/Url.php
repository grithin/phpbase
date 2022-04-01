<?php

namespace Grithin;

use Grithin\Tool;
use Grithin\Arrays;

class Url{
	/** parse a query string using a more standard less php specific rule (all repeated tokens turn into arrays, not just tokens with "[]") */
	/**
	You can have this function include php field special syntax along with standard parsing.
	@param	string	string that matches form of a url query string
	@param	specialSyntax	whether to parse the string using php rules (where [] marks an array) in addition to "standard" rules
	*/
	static function query_parse($string,$specialSyntax = false){
		$parts = \Grithin\Strings::explode('&',$string);
		$array = array();
		foreach($parts as $part){
			list($key,$value) = explode('=',$part);
			$key = urldecode($key);
			$value = urldecode($value);
			if($specialSyntax && ($matches = self::special_syntax_keys($key))){
				if(Arrays::has($array, $matches)){
					$currentValue = Arrays::get($array, $matches);
					if(is_array($currentValue)){
						$currentValue[] = $value;
					}else{
						$currentValue = array($currentValue,$value);
					}
					Arrays::set($array, $matches, $currentValue);
				}else{
					Arrays::set($array, $matches, $value);
				}
				unset($match,$matches);
			}else{
				if(!empty($array[$key])){
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
	/** About
	Doesn't use php array url syntax.
	`?bob=1&bob=2` instead of `?bob[]=1&bob[]=2`
	*/
	static function query_build($array){
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
	/** get all the keys invovled in a string that represents an array.  Ex: "bob[sue][joe]" yields array('bob','sue','joe') */
	static function special_syntax_keys($string){
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
	/** appends multiple (key=>value)s to a url, replacing any key values that already exist */
	/**
	@param	kvA	array of keys to values array(key1=>value1,key2=>value2)
	@param	url	url to be appended

	@example

	normal use
		Url::appends(['bob'=>'rocks','sue'=>'rocks'],'bobery.com');
			bobery.com?bob=rocks&sue=rocks
	empty string url
		Url::appends(['bob'=>'rocks','sue'=>'rocks'],'');
			?bob=rocks&sue=rocks

	overwriting existing
		Url::appends(['bob'=>'rocks','sue'=>'rocks'],'bobery.com/?bob=sucks&bill=rocks');
			bobery.com/?bill=rocks&bob=rocks&sue=rocks
	*/
	static function appends($kvA,$url=null,$replace=true){
		foreach((array)$kvA as $k=>$v){
			if(is_array($v)){
				foreach($v as $subv){
					$url = self::append($k,$subv,$url,$replace);
				}
			}else{
				$url = self::append($k,$v,$url,$replace);
			}
		}
		return $url;
	}
	/** appends name=value to query string, replacing them if they already exist */
	/**
	@param	name	name of value
	@param	value	value of item
	@param	url	url to be appended
	*/
	static function append($name,$value,$url=null,$replace=true){
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
	static function query_remove_matching_keys($regex,$url=null){
		$url = $url !== null ? $url : urldecode($_SERVER['REQUEST_URI']);
		if(!preg_match('@\@@',$regex)){
			$regex = '@^'.preg_quote($regex,'@').'$@';
		}
		$urlParts = explode('?',$url,2);
		if(!empty($urlParts[1])){
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
	/** builds `parse_url` output into a string */
	static function parse_url_rebuild($url_parts){
		# put keys in order they will be joined
		$url_parts_with_separators = array(
			'scheme'        => null,
			'abempty'       => isset( $url_parts['scheme'] ) ? '://' : (isset($url_parts['host']) ? '//' : null), # host indicates at least "//"  was present
			'user'          => null,
			'authcolon'     => isset( $url_parts['pass'] ) ? ':' : null,
			'pass'          => null,
			'authat'        => isset( $url_parts['user'] ) ? '@' : null,
			'host'          => null,
			'portcolon'     => isset( $url_parts['port'] ) ? ':' : null,
			'port'          => null,
			'path'          => null,
			'param'         => isset( $url_parts['query'] ) ? '?' : null,
			'query'         => null,
			'hash'          => isset( $url_parts['fragment'] ) ? '#' : null,
			'fragment'      => null
		);

		return implode(null, array_merge( $url_parts_with_separators, $url_parts));
	}
	/**resolves relative url paths into absolute url paths */
	static  function resolve_relative($url, $relative=null){
		if($relative){
			$url_relative_parts = array_merge(['path'=>''], parse_url($relative));
			if(isset($url_relative_parts['host'])){ # relative path has domain, use it instead of base domain
				$url_relative_parts['path'] = Files::resolve_relative($url_relative_parts['path'], null, '/');
				return self::parse_url_rebuild($url_relative_parts);
			}
		}

		$url_parts = array_merge(['path'=>''], parse_url($url));
		$url_parts['path'] = Files::resolve_relative($url_parts['path'], null, '/');

		if($relative){
			$target_url = Arrays::pick($url_parts, ['scheme', 'user', 'pass', 'host', 'port']);
			$url_relative_parts['path'] = Files::resolve_relative($url_parts['path'], $url_relative_parts['path'], '/');
			$target_url = array_merge($target_url, $url_relative_parts);
			return self::parse_url_rebuild($target_url);
		}
		return self::parse_url_rebuild($url_parts);
	}
}