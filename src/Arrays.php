<?
namespace Grithin;

/// Useful array related functions I didn't, at one time, find in php
class Arrays{
	///turns var into an array
	/**
		@note	if it is a string, will attempt to explode it using divider, unless divider is not set
		@return	array
	*/
	static function toArray($var,$divider=',\s*'){
		if(is_string($var)){
			if($divider){
				return (array)preg_split("@$divider@",$var);
			}else{
				return (array)$var;
			}
		}elseif(is_object($var)){
			return (array)get_object_vars($var);
		}
		return (array)$var;
	}
	/// lodash pick
	static function pick($src, $props){
		$props = self::toArray($props);
		$dest = [];
		foreach($props as $prop){
			$dest[$prop] = $src[$prop];
		}
		return $dest;
	}
	/// lodash omit
	static function omit($src, $props){
		$props = self::toArray($props);
		$dest = [];
		foreach($src as $key=>$value){
			if(!in_array($key, $props)){
				$dest[$key] = $value;
			}
		}
		return $dest;
	}

	/// lodash get.  Works with arrays and objects.  Specially handling for part which is a obj.method
	function get($collection, $path){
		foreach(explode('.', $path) as $part){
			if(is_object($collection)){
				if(isset($collection->$part)){
					$collection = $collection->$part;
				}elseif(is_callable([$collection, $part])){
					$collection = [$collection, $part]; # turn it into a callable form
				}else{
					return null;
				}
			}elseif(is_array($collection)){
				if(isset($collection[$part])){
					$collection = $collection[$part];
				}else{
					return null;
				}
			}else{
				throw new \Exception('Part is not a traverseable structure');
			}
		}
		return $collection;
	}

	/// change the name of some keys
	static function remap($src, $remap){
		foreach($remap as $k=>$v){
			$dest[$v] = $src[$k];
			unset($src[$k]);
		}
		return array_merge($src,$dest);
	}

	static function each($src, $fn){

	}


	///removes all instances of value from an array
	/**
	@param	value	the value to be removed
	@param	array	the array to be modified
	*/
	static function remove(&$array,$value = false,$strict = false){
		if(!is_array($array)){
			throw new \Exception('Parameter must be an array');
		}
		$existingKey = array_search($value,$array);
		while($existingKey !== false){
			unset($array[$existingKey]);
			$existingKey = array_search($value,$array,$strict);
		}
		return $array;
	}

	# ensure a value is in array.  If not, append array.
	static function ensure(&$array, $value){
		if(array_search($value, $array) === false){
			$array[] = $value;
		}
		return $array;
	}

	///takes an array of keys to extract out of one array and into another array
	/**
	@param	forceKeys	will cause keys not in extractee to be set to null in the return
	*/
	static function &extract($keys,$extactee,&$extractTo=null,$forceKeys=true){
		if(!is_array($extractTo)){
			$extractTo = array();
		}
		foreach($keys as $key){
			if(array_key_exists($key,$extactee) || $forceKeys){
				$extractTo[$key] = $extactee[$key];
			}
		}
		return $extractTo;
	}
	///takes an array and maps its values to the keys of another array
	/**
		@param	map	array	{<newKey> : <oldKey>, <newKey> : <oldKey>, <straight map>}
		@param	$numberDefault	wherein if the newKey is a number, assume the oldKey is both the oldKey and the newKey

		ex
		$bob = ['sue'=>'a','bill'=>'b'];
		Arrays::map(['test'=>'sue','bill'],$bob,$x=null,true);
			[[test] => a [bill] => b]
	*/
	static function &map($map,$extractee,$straightMap=false,&$extractTo=null){
		if(!is_array($extractTo)){
			$extractTo = array();
		}
		if(!$straightMap){
			foreach($map as $to=>$from){
				$extractTo[$to] = $extractee[$from];
			}
		}else{
			foreach($map as $to=>$from){
				if(is_int($to)){
					$extractTo[$from] = $extractee[$from];
				}else{
					$extractTo[$to] = $extractee[$from];
				}
			}
		}

		return $extractTo;
	}

	///apply a callback to an array, returning the result, with optional arrayed parameters
	/**
	@param	callback	function($k,$v,optional params...)
	*/
	static function apply($array,$callback,$parameters = []){
		$parameters = (array)$parameters;
		$newArray = [];
		foreach($array as $k=>$v){
			$newArray[$k] = call_user_func_array($callback,array_merge([$k,$v],$parameters));
		}
		return $newArray;
	}



	#++ Depth path functions {

	/// takes an array and flattens it to one level using separator to indicate key deepness
	/**
	@param	array	a deep array to flatten
	@param	separator	the string used to indicate in the flat array a level of deepenss between key strings
	@param	keyPrefix used to prefix the key at the current level of deepness
	@return	array
	*/
	static function flatten($array,$separator='_',$keyPrefix=null){
		foreach($array as $k=>$v){
			if($fK){
				$key = $keyPrefix.$separator.$k;
			}else{
				$key = $k;
			}
			if(is_array($v)){
				$sArrays = self::arrayFlatten($v,$key,$separator);
				foreach($sArrays as $k2 => $v2){
					$sArray[$k2] = $v2;
				}
			}else{
				$sArray[$key] = $v;
			}
		}
		return $sArray;
	}

	/// Checks if element of an arbitrarily deep array is set
	/**
	@param	keys array comma separated list of keys to traverse
	@param	array	array	The array which is parsed for the element
	*/
	static function isElement($keys,$array){
		$keys = self::toArray($keys);
		$lastKey = array_pop($keys);
		$array = self::getElement($keys,$array);
		return isset($array[$lastKey]);
	}

	/// Gets an element of an arbitrarily deep array using list of keys for levels
	/**
	@param	keys array comma separated list of keys to traverse
	@param	array	array	The array which is parsed for the element
	@param	force	string	determines whetehr to create parts of depth if they don't exist
	*/
	static function getElement($keys,$array,$force=false){
		$keys = self::toArray($keys);
		foreach($keys as $key){
			if(!isset($array[$key])){
				if(!$force){
					return;
				}
				$array[$key] = array();
			}
			$array = $array[$key];
		}
		return $array;
	}
	/// Same as getElement, but returns reference instead of value
	/**
	@param	keys array comma separated list of keys to traverse
	@param	array	array	The array which is parsed for the element
	@param	force	string	determines whetehr to create parts of depth if they don't exist
	*/
	static function &getElementReference($keys,&$array,$force=false){
		$keys = self::toArray($keys);
		foreach($keys as &$key){
			if(!is_array($array)){
				$array = array();
				$array[$key] = array();
			}elseif(!isset($array[$key])){
				if(!$force){
					return;
				}
				$array[$key] = array();
			}
			$array = &$array[$key];
		}
		return $array;
	}

	/// Updates an arbitrarily deep element in an array using list of keys for levels
	/** Traverses an array based on keys to some depth and then updates that element
	@param	keys array comma separated list of keys to traverse
	@param	array	array	The array which is parsed for the element
	@param	value the new value of the element
	*/
	static function updateElement($keys,&$array,$value){
		$element = &self::getElementReference($keys,$array,true);
		$element = $value;
	}
	/// Same as updateElement, but sets reference instead of value
	/** Traverses an array based on keys to some depth and then updates that element
	@param	keys array comma separated list of keys to traverse
	@param	array	array	The array which is parsed for the element
	@param	reference the new reference of the element
	*/
	static function updateElementReference($keys,&$array,&$reference){
		$element = &self::getElementReference($keys,$array,true);
		$element = &$reference;
	}


	///finds all occurrences of value and replaces them in arbitrarily deep array
	static function replaceAll($value,$replacement,$array,&$found=false){
		foreach($array as &$element){
			if(is_array($element)){
				$element = self::replaceAll($value,$replacement,$element,$found);
			}elseif($element == $value){
				$found = true;
				$element = $replacement;
			}
		}
		unset($element);
		return $array;
	}
	///finds all occurences of value and replaces parents (parent array of the value) in arbitrarily deep array
	/**
	Ex
		$bob = ['sue'=>['jill'=>['dave'=>['bill'=>'bob']]]];
		replaceAllParents('bob','bill',$bob);
		#	['sue'=>['jill'=>['dave'=>'bill']]]
		replaceAllParents('bob','bill',$bob,2);
		#	['sue'=>['jill'=>'bill']];

	*/
	static function replaceAllParents($value,$replacement,$array,$parentDepth=1,&$found=false){
		foreach($array as &$element){
			if(is_array($element)){
				$newValue = self::replaceAllParents($value,$replacement,$element,$parentDepth,$found);
				if(is_int($newValue)){
					if($newValue == 1){
						$element = $replacement;
					}else{
						return $newValue - 1;
					}
				}else{
					$element = $newValue;
				}
			}elseif($element == $value){
				$found = true;
				return (int)$parentDepth;
			}
		}
		unset($element);
		return $array;
	}

	#++ }


	///Takes an arary of arbitrary deepness and turns the keys into tags and values into data
	/**
	@param	array	array to be turned into xml
	@param	depth	internal use
	*/
	static function toXml($array,$depth=0){
		foreach($array as $k=>$v){
			if(is_array($v)){
				$v = arrayToXml($v);
			}
			$ele[] = str_repeat("\t",$depth).'<'.$k.'>'.$v.'</'.$k.'>';
		}
		return implode("\n",$ele);
	}
	///Set the keys equal to the values or vice versa
	/**
	@param array the array to be used
	@param	type	"key" or "value".  Key sets all the values = keys, value sets all the keys = values
	@return array
	*/
	static function homogenise($array,$type='key'){
		if($type == 'key'){
			$array = array_keys($array);
			foreach($array as $v){
				$newA[$v] = $v;
			}
		}else{
			$array = array_values($array);
			foreach($array as $v){
				$newA[$v] = $v;
			}
		}
		return $newA;
	}

	///php collapses numbered or number string indexes on merge, this does not
	static function indexMerge($x,$y){
		if(is_array($x)){
			if(is_array($y)){
				foreach($y as $k=>$v){
					$x[$k] = $v;
				}
				return $x;
			}else{
				return $x;	}
		}else{
			return $y;	}
	}

	///merges if two arrays, else returns the existing array.  $y overwrites $x on matching keys
	static function merge($x,$y){
		$arrays = func_get_args();
		$result = [];
		foreach($arrays as $array){
			if(is_array($array)){
				$result = array_merge($result,$array);	}	}
		return $result;
	}
	///for an incremented key array, find first gap in key numbers, or use end of array
	static function firstAvailableKey($array){
		if(!is_array($array)){
			return 0;
		}
		$key = 0;
		ksort($array);
		foreach($array as $k=>$v){
			if($k != $key){
				return $key;
			}
			$key++;
		}
		return $key;
	}
	/// if no key (false or null), append, otherwise, use the key.  Account for collisions with optional append.
	/**
	@param	key	can be null or key or array.  If null, value added to end of array
	@param	value	value to add to array
	@param	array	array that will be modified
	@param	append	if true, if keyed value already exists, ensure keyed value is array and add new value to array
	*/
	static function addOnKey($key,$value,&$array,$append=false){
		if($key !== null && $key !== false){
			if($append && isset($array[$key])){
				if(is_array($array[$key])){
					$array[$key][] = $value;
				}else{
					$array[$key] = array($array[$key],$value);
				}
			}else{
				$array[$key] = $value;
			}
			return $key;
		}else{
			$array[] = $value;
			return count($array) - 1;
		}
	}
	/// adds to the array and overrides duplicate elements
	/**removes all instances of some value in an array then adds the value according to the key
	@param	value	the value to be removed then added
	@param	array	the array to be modified
	@param	key	the key to be used in the addition of the value to the array; if null, value added to end of array
	*/
	static function addOverride($value,&$array,$key=null){
		self::remove($value);
		self::addOnKey($key,$value,$array);
		return $array;
	}

	///separate certain keys from an array and put them into another, returned array
	static function separate($keys,&$array){
		$separated = array();
		foreach($keys as $key){
			$separated[$key] = $array[$key];
			unset($array[$key]);
		}
		return $separated;
	}
	/// Take some normal list of rows and make it a id-keyed list pointing to either a value or the row remainder
	/**
	Ex
		1
			[
				['id'=>3,'email'=>'bob@bob.com'],
				['id'=>4,'email'=>'bob2@bob.com']	]
			becomes
				[3 => 'bob@bob.com', 4 => 'bob2@bob.com']
		2
			[['id'=>3,'email'=>'bob@bob.com','name'=>'bob'],['id'=>4,'email'=>'bob2@bob.com','name'=>'bill']]
			becomes (note, since remain array has more than one part, key points to an array instead of a value
			[
				3 : [
					'email' : 'bob@bob.com'
					'name' : 'bob'
				]
				4 : [
					'email' : 'bob2@bob.com'
					'name' : 'bill'
				]	]
	@param	array	array used to make the return array
	@param	key	key to use in the sub arrays of input array to be used as the keys of the output array
	@param	name	value to be used in the output array.  If not specified, the value defaults to the rest of the array apart from the key
	@return	key to name mapped array
	*/
	static function subsOnKey($array,$key = 'id',$name=null){
		if(is_array($array)){
			$newArray = array();
			foreach($array as $part){
				$keyValue = $part[$key];
				if($name){
					$newArray[$keyValue] = $part[$name];
				}else{
					unset($part[$key]);
					if(count($part) > 1 ){
						$newArray[$keyValue] = $part;
					}else{
						$newArray[$keyValue] = array_pop($part);
					}
				}
			}
			return $newArray;
		}
		return array();
	}
	/// same as subsOnKey, but combines duplicate keys into arrays; keyed value is always and array
	static function compileSubsOnKey($array,$key = 'id',$name=null){
		if(is_array($array)){
			$newArray = array();
			foreach($array as $part){
				$keyValue = $part[$key];
				if($name){
					$newArray[$keyValue][] = $part[$name];
				}else{
					unset($part[$key]);
					if(count($part) > 1 ){
						$newArray[$keyValue][] = $part;
					}else{
						$newArray[$keyValue][] = array_pop($part);
					}
				}
			}
			return $newArray;
		}
		return array();
	}

	///like the normal implode but ignores empty values
	static function implode($separator,$array){
		Arrays::remove($array);
		return implode($separator,$array);
	}

	///checks if $subset is a sub set of $set starting at $start
	/*

		ex 1: returns true
			$subset = array('sue');
			$set = array('sue','bill');
		ex 2: returns false
			$subset = array('sue','bill','moe');
			$set = array('sue','bill');

	*/
	static function isOrderedSubset($subset,$set,$start=0){
		for($i=0;$i<$start;$i++){
			next($set);
		}
		while($v1 = current($subset)){
			if($v1 != current($set)){
				return false;
			}
			next($subset);
			next($set);
		}
		return true;
	}
	///count how many times a value is in an array
	static function countIn($value,$array,$max=null){
		$count = 0;
		foreach($array as $v){
			if($v == $value){
				$count++;
				if($max && $count == $max){
					return $max;
				}
			}
		}
		return $count;
	}
	/// takes an object and converts it into an array.  Ignores nested objects
	static function convert($variable,$parseObject=true){
		if(is_object($variable)){
			if($parseObject){
				$parts = get_object_vars($variable);
				foreach($parts as $k=>$part){
					$return[$k] = self::convert($part,false);
				}
				return $return;
			}elseif(method_exists($variable,'__toString')){
				return (string)$variable;
			}
		}elseif(is_array($variable)){
			foreach($variable as $k=>$part){
				$return[$k] = self::convert($part,false);
			}
			return $return;
		}else{
			return $variable;
		}
	}
}
