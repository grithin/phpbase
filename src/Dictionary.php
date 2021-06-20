<?php
namespace Grithin;

/* About
Although PHP does not distinguish, it is sometimes necessary to treat dictionaries ({key:name}) differently than lists ([value1,value2]).
To help distinguish, here, the terms `dictionary` and `list` will be used

Further, class instances can also act like dictionaries.

*/

class Dictionary{

	# determine if parameter is dictionary by checking if it has string keys
	static function is($x) {
		if(is_object($x)){
			$x = Arrays::from($x);
		}
		return count(array_filter(array_keys($x), 'is_string')) > 0;
	}


	# merges two dictionaries.  Plain arrays will be treated like dictionaries (this won't act like array_merge)
	static function merge($x, $y){
		$arrays = func_get_args();
		$result = [];
		foreach($arrays as $array){
			if(is_object($array)){
				$array = self::from($array);
			}
			foreach($array as $k=>$v){
				$result[$k] = $v;
			}
		}
		return $result;
	}
	# deeply merges two dictionaries
	static function merge_deep($x, $y){
		$arrays = func_get_args();
		$result = [];
		foreach($arrays as $array){
			if(is_object($array)){
				$array = self::from($array);
			}
			foreach($array as $k=>$v){
				if((is_object($v) || is_array($v)) && isset($result[$k])){
					if(!self::is($result[$k])){ # this is a list, not a dictionary.  Don't deep merge
						$result[$k] = $v;

					}else{
						$result[$k] = self::merge_deep($result[$k], $v);
					}
				}else{
					$result[$k] = $v;
				}
			}
		}
		return $result;
	}

	# lodash find
	/* params
	< collection >
	< predicate > (
		< closure >
		||
		{< match_key >: < match_value >, ...}
		||
		< string of key to find within objects >
	)
	*/
	static function find($collection, $predicate){
		if(is_object($predicate) && ($predicate instanceof Closure)){
			foreach($collection as $item){
				if($predicate($item)){
					return $item;
				}
			}
		}else{
			if(is_array($predicate)){
				foreach($collection as $item){
					foreach($predicate as $k_match => $v_match){
						if(!array_key_exists($k_match, $item) || $item[$k_match] != $v_match){
							continue 2;
						}
						return $item;
					}
				}
			}else{
				foreach($collection as $item){
					if(array_key_exists($predicate, $item)){
						return $item;
					}
				}
			}
		}
	}
	# find, but return all matches
	static function find_all($collection, $predicate){
		$found = [];
		if(is_object($predicate) && ($predicate instanceof Closure)){
			foreach($collection as $item){
				if($predicate($item)){
					$found[] = $item;
				}
			}
		}else{
			if(is_array($predicate)){
				foreach($collection as $item){
					foreach($predicate as $k_match => $v_match){
						if(!array_key_exists($k_match, $item) || $item[$k_match] != $v_match){
							continue 2;
						}
						$found[] = $item;
					}
				}
			}else{
				foreach($collection as $item){
					if(array_key_exists($predicate, $item)){
						$found[] = $item;
					}
				}
			}
		}
		return $found;
	}
	# Convert a numeric list (array) to a dictionary (key:value) by using some function that returns [key, value]
	/* Ex
	[ {a:b}, {x:y} ]
	=>
	{a:b, x:y}
	*/
	static function list_to_dictionary($list, $fn=null){
		if(!$fn){
			$fn = function($item){
				return [key($item), current($item)];
			};
		}
		$dictionary = [];
		foreach($list as $item){
			list($k,$v) = $fn($item);
			$dictionary[$k] = $v;
		}
		return $dictionary;
	}

	# get ArrayObject representing diff between two arrays/objects, wherein items in $target are different than in $base, but not vice versa (existing $base items may not exist in $target)
	/* Examples
	self(['bob'=>'sue'], ['bob'=>'sue', 'bill'=>'joe']);
	#> {}
	self(['bob'=>'suesss', 'noes'=>'bees'], ['bob'=>'sue', 'bill'=>'joe']);
	#> {"bob": "suesss", "noes": "bees"}
	*/
	/* params
	target: < what the diff will transform to >
	base: < what the diff will transform from >
	options: {object_comparer: < fn that takes (target_value, base_value) and returns a diff >}
	*/
	static function diff($target, $base, $options=[]){
		$aArray1 = Arrays::from($target);
		$aArray2 = Arrays::from($base);


		$aReturn = [];

		$missing_keys = array_diff(array_keys($aArray2), array_keys($aArray1));
		foreach($missing_keys as $key){
			$aReturn[$key] = new MissingValue;
		}

		foreach ($aArray1 as $mKey => $mValue) {
			if (array_key_exists($mKey, $aArray2)) {
				if(is_array($mValue)){
					$aRecursiveDiff = self::diff($mValue, $aArray2[$mKey], $options);
					if(count($aRecursiveDiff)){
						$aReturn[$mKey] = $aRecursiveDiff;
					}
				}elseif(!Tool::is_scalar($mValue)) {
					if(!empty($options['object_comparer'])){
						$diff = $options['object_comparer']($mValue, $aArray2[$mKey]);
						if($diff){
							$aReturn[$mKey] = $diff;
						}
					}else{
						$aRecursiveDiff = self::diff($mValue, $aArray2[$mKey], $options);
						if(count($aRecursiveDiff)){
							$aReturn[$mKey] = $aRecursiveDiff;
						}
					}
				} else {
					if((string)$mValue !== (string)$aArray2[$mKey]){
						$aReturn[$mKey] = $mValue;
					}
				}
			} else {
				$aReturn[$mKey] = $mValue;
			}
		}
		return $aReturn;
	}
	static function diff_comparer_exact($target, $base){
		if($target !== $base){
			return $target;
		}
		return false;
	}
	static function diff_comparer_equals($target, $base){
		if($target != $base){
			return $target;
		}
		return false;
	}
	static function diff_apply($target, $diff){
		$result = Arrays::replace_recursive($target, $diff);

		return MissingValue::remove($result);
	}
	# apply defaults for values that appear empty (false, blank, etc)
	static function empty_default($source, $defaults){
		foreach($defaults as $k=>$v){
			if(!array_key_exists($k, $source) || $source[$k] === '' || $source[$k] === false || $source[$k] === null){
				$source[$k] = $v;
			}
		}
		return $source;
	}


	# turn a string a specially formatted string into a collection.  Format intended to provide minimal syntax description of collections.
	/* about format
		Key value pairs separated by new lines:

		key:value
		key:value

		Empty newlines are not used.
	*/
	# if either key on either side of ':' is blank, the non-blank value will be used for both.  And, ":" can be omitted
	/* ex
		'
			:bob
			sue:
			jill
			normal:normal2
		'
	*/
	# @NOTE lines are trimmed.  Empty lines are ignored

	static function string_to_map(){
		$lines = array_filter(preg_split('@[\s\n]+@', $map_string));
		$array = [];
		$lines = array_filter($lines);
		foreach($lines as $line){
			$parts = explode(':', $line);
			if($parts[0] === ''){
				$parts[0] = $parts[1];
			}elseif($parts[1] === ''){
				$parts[1] = $parts[0];
			}
			$array[$parts[0]] = $parts[1];
		}
		return $array;
	}
}
