<?
namespace Grithin;

class Collection{
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
					if($options['object_comparer']){
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


	# take a string and turn it into a collection for mapping
	/* ex
		'
			bob:bob2
			bob:bob3
		'
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
