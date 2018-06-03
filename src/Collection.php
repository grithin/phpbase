<?
namespace Grithin;

class Collection{
	# lodash find
	static function find($collection, $predicate){
		if(is_object($predicate) && ($predicate instanceof Closure)){
			foreach($collection as $item){
				if($predicate($item)){
					return $item;
				}
			}
		}else{
			foreach($collection as $item){
				foreach($predicate as $k_match => $v_match){
					if(!array_key_exists($k_match, $item) || $item[$k_match] != $v_match){
						continue 2;
					}
					return $item;
				}
			}
		}
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
}
