<?
namespace Grithin;

use \ArrayObject;
use \Exception;

/// Useful array related functions I didn't, at one time, find in php
/*
@NOTE	on parameter order: Following lodash wherein the operatee, the source array, is the first parameter.  To be applied to all new functions with such an operatee
*/
class Arrays{
	# use __toArray if exists on object
	static function from($x){
		if(is_object($x)){
			if(method_exists($x, '__toArray')){ # hopefully PHP adds this at some point
				return $x->__toArray();
			}
		}
		return (array)$x;
	}
	///turns var into an array
	/**
		@note	if it is a string, will attempt to explode it using divider, unless divider is not set
		@return	array
	*/
	static function to_array($var,$divider=',\s*'){
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
	# alias
	static function toArray(){
		$function = 'to_array';
		return call_user_func_array([self, $function], func_get_args());
	}
	/// extract, if present, specified keys
	static function pick($src, $props){
		$props = Arrays::toArray($props);
		$dest = [];
		foreach($props as $prop){
			if(self::is_set($src, $prop)){
				$dest[$prop] = $src[$prop];
			}
		}
		return $dest;
	}
	/// extract specified keys, filling with default if not present
	static function pick_default($src, $props, $default=null){
		$src = self::ensure_keys($src, $props, $default); # First to ensure key order upon pick
		return self::pick($src, $props);
	}
	/// ensure, by adding if necessary, keys are within an array
	static function ensure_keys($src, $props, $fill_on_missing=null){
		$props = Arrays::toArray($props);
		foreach($props as $prop){
			if(!self::is_set($src, $prop)){
				$src[$prop] = $fill_on_missing;
			}
		}
		return $src;
	}
	/// `isset` fails on nulls, however, is faster than `array_key_exists`.  So, combine.
	//@NOTE	upon benchmarking, making this into a function instead of applying `isset` and `array_key_exists` directly adds insignificant overhead
	static function is_set($src, $key){
		if(isset($src[$key]) || array_key_exists($key, $src)){
			return true;
		}
		return false;
	}

	/*
	Copy array, mapping some columns to different columns - only excludes columns on collision
	@NOTE if src contains key collision with map, map will overwrite

	[ old_key => new_key ]

	*/
	/* Example
	$user_input = [
		'first_name'=>'bob',
		'last_name'=>'bobl'
	];
	$map = ['first_name'=>'FirstName'];

	Arrays::map_with($user_input, $map);

	#> 	{"FirstName": "bob",    "last_name": "bobl"}
	*/

	static function map_with($src, $map){
		$result = [];
		foreach($src as $k=>$v){
			if(self::is_set($map, $k)){
				$result[$map[$k]] = $v;
			}elseif(!self::is_set($result, $k)){
				$result[$k] = $v;
			}
		}
		return $result;
	}

	/*
	Map only specified keys, ignoring the rest
	@NOTE if src contains key collision with map, map will overwrite

	[ old_key => new_key ]

	*/
	/* Example
	$user_input = [
		'first_name'=>'bob',
		'last_name'=>'bobl'
	];
	$map = ['first_name'=>'FirstName'];

	Arrays::map_only($user_input, $map);

	#> 	{"FirstName": "bob"}
	*/

	static function map_only($src, $map){
		$result = [];
		foreach($map as $k=>$v){
			if(self::is_set($src, $k)){
				$result[$v] = $src[$k];
			}
		}
		return $result;
	}



	/// like map_with, but does not include non-mapped columns
	/**
		@note	since this is old, it has a different parameter sequence than map_with
		@param	map	array	{<newKey> : <oldKey>, <newKey> : <oldKey>, <straight map>}
		@param	$interpret_numeric_keys	< true | false >< whether to use numeric keys as indication of same key mapping >
	*/
	/* example
	$user_input = [
		'first_name'=>'bob',
		'last_name'=>'bobl'
	];
	$map = ['FirstName'=>'first_name'];
	$x = Arrays::map($map, $user_input);
	#> {"FirstName": "bob"}
	*/
	static function &map($map,$extractee,$interpret_numeric_keys=true,&$extractTo=null){
		if(!is_array($extractTo)){
			$extractTo = array();
		}
		if(!$interpret_numeric_keys){
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
	static function get($collection, $path){
		try{
			$value = Arrays::got($collection, $path);
			return $value;
		}catch(\Exception $e){
			return null;
		}
	}
	/// lodash has
	static function has($collection, $path){
		try{
			Arrays::got($collection, $path);
			return true;
		}catch(\Exception $e){
			return false;
		}
	}

	# lodash get, with exception upon not found
	static function got($collection, $path){
		foreach(explode('.', $path) as $part){
			if(is_object($collection)){
				if(isset($collection->$part)){
					$collection = $collection->$part;
				}elseif(is_callable([$collection, $part])){
					$collection = [$collection, $part]; # turn it into a callable form
				}else{
					throw new \Exception('not found');
				}
			}elseif(is_array($collection)){
				if(self::is_set($collection, $part)){
					$collection = $collection[$part];
				}else{
					throw new \Exception('not found');
				}
			}else{
				throw new \Exception('not found');
			}
		}
		return $collection;
	}

	# see self::get.  Sets at path and returns collection

	/* Example, set existing
	$x = ['bob'=> 'sue', 'bill'=>['moe'=>'fil']];
	$x = Arrays::set('bill.moe', '123', $x);

	$x = (object)['bob'=> 'sue', 'bill'=>['moe'=>'fil']];
	$x = Arrays::set('bill.moe', '123', $x);
	*/

	/* Example, set new
	$x = ['bob'=> 'sue', 'bill'=>['moe'=>'fil']];
	$x = Arrays::set('bill.new1.new2', '123', $x);

	$x = (object)['bob'=> 'sue', 'bill'=>['moe'=>'fil']];
	$x = Arrays::set('moe.mill', '123', $x);
	*/
	static function set_on_reference($path, $value, &$collection=[], $options=[]){
		$reference_to_last =& $collection;
		$parsed = [];
		foreach(explode('.', $path) as $part){
			if(is_object($reference_to_last)){
				if(isset($reference_to_last->$part)){
					$reference_to_last =& $reference_to_last->$part;
				}elseif(is_callable([$reference_to_last, $part])){
					$reference_to_last =& [$reference_to_last, $part](); # turn it into a callable form
				}else{
					$reference_to_last =& $reference_to_last->$part; # attempt to create attribute
				}
			}elseif(is_array($reference_to_last)){
				$reference_to_last =& $reference_to_last[$part]; # will either find or create at key
			}elseif(is_null($reference_to_last)){
				# PHP will turn the null `reference_to_last` into an array, and then create the accessed key for referencing
				$reference_to_last =& $reference_to_last[$part];
			}else{
				if($options['path_error_handler']){
					$options['path_error_handler']($reference_to_last, $value, $parsed, $part, $path, $collection, $options);
				}else{
					throw new \Exception('Can not expand into path at "'.$part.'" with "'.$path.'"');
				}

			}
			$parsed[] = $part;
		}
		if($options['set_handler']){
			$options['set_handler']($reference_to_last, $value, $path, $collection, $options);
		}else{
			$reference_to_last = $value;
		}

		return $collection;
	}
	static function set($path, $value, $collection=[], $options=[]){
		return self::set_on_reference($path, $value, $collection, $options);
	}
	static function set_new_or_expand($path, $value, $collection=[], $options=[]){
		return self::set_new_or_expand_on_reference($path, $value, $collection, $options);
	}
	static function set_new_or_expand_on_reference($path, $value, &$collection=[], $options=[]){
		$options = [
			# turn a non-expandable value into an array
			'path_error_handler' => function(&$reference_to_last, $value){
				$deferenced_value = $reference_to_last;
				$reference_to_last = [$deferenced_value, $value];},
			# turn a collided value into an array
			'set_handler' => function(&$reference_to_last, $value){
				if(is_array($reference_to_last)){
					$reference_to_last[] = $value;
				}elseif(!is_null($reference_to_last)){
					$deferenced_value = $reference_to_last;
					$reference_to_last = [$deferenced_value, $value];
				}else{
					$reference_to_last = $value;
				}
			}
		];
		return self::set_on_reference($path, $value, $collection, $options);
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
	# clear values equivalent to false
	static function clear_false($v){
		return self::remove($v);
	}

	static function ensure_values(&$array, $values){
		foreach($values as $value){
			self::ensure($array, $value);
		}
		return $array;
	}
	static function ensure_value(&$array, $value){
		return self::ensure($array, $value);
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


	# flatten the values of an array into non-array values by select one of the sub array items
	static function flatten_values($array, $fn=null){
		if(!$fn){
			$fn = function($v, $k) use (&$fn){
				if(is_array($v)){
					list($key, $value) = each($v);
					return $fn($value, $key);
				}else{
					return $v;
				}
			};
		}
		foreach($array as $k=>&$v){
			$v = $fn($v, $k);
		} unset($v);
		return $array;
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
		foreach((array)$array as $k=>$v){
			if($keyPrefix){
				$key = $keyPrefix.$separator.$k;
			}else{
				$key = $k;
			}
			if(is_array($v)){
				$sArrays = self::flatten($v,$separator, $key);
				foreach($sArrays as $k2 => $v2){
					$sArray[$k2] = $v2;
				}
			}else{
				$sArray[$key] = $v;
			}
		}
		return (array)$sArray;
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
		return self::is_set($array, $lastKey);
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
			if(!self::is_set($array, $key)){
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
	@param	force	string	determines whether to create parts of depth if they don't exist
	*/
	static function &getElementReference($keys,&$array,$force=false){
		$keys = self::toArray($keys);
		foreach($keys as &$key){
			if(!is_array($array)){
				$array = array();
				$array[$key] = array();
			}elseif(!self::is_set($array, $key)){
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

	# merges objects/arrays.  Ignores scalars.  Later parameters take precedence
	static function merge($x,$y){
		$arrays = func_get_args();
		$result = [];
		foreach($arrays as $array){
			if(is_object($array)){
				$array = self::from($array);
			}
			if(is_array($array)){
				$result = array_merge($result,$array);
			}
		}
		return $result;
	}
	# merges/replaces objects/arrays.  Ignores scalars.  Later parameters take precedence
	/* @note	merge vs replace
		generally, replace acts as expected, replacing matching keys whether numeric or not.  Merge will append on numeric keys, and consequently, not maintain key offsets during such.

		-	different on numeric keys
				array_merge([1,2,3], [5,1]);
					#> [1,2,3,5,1]
				array_replace([1,2,3], [5,1]);
					#> [5,1,3]
		-	same on non-numeric keys
			array_merge(['bob'=>'sue', 'bob1'=>'sue1'], ['bob'=>'sue', 'bob2'=>'sue2'])  = array_replace(['bob'=>'sue', 'bob1'=>'sue1'], ['bob'=>'sue', 'bob2'=>'sue2']);
		-	differend on mixed keys
			array_merge(['bill'=>'moe', 5=>'bob'], ['bill'=>'moe', 5=>'sue']);
				#> {"bill": "moe", "0": "bob", "1": "sue"}
				# here we see the '5' key is removed, and both values stay, but on new keys
			array_replace(['bill'=>'moe', 5=>'bob'], ['bill'=>'moe', 5=>'sue']);
				#> {"bill": "moe", "5": "sue"}
	*/
	static function replace($x, $y){
		$arrays = func_get_args();
		$result = [];
		foreach($arrays as $array){
			if(is_object($array)){
				$array = self::from($array);
			}
			if(is_array($array)){
				$result = array_replace($result,$array);
			}
		}
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
			if($append && self::is_set($array, $key)){
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
	#**deprecated** use key_on_sub_key_to_remaining
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
	#**deprecated** use key_on_sub_key_to_compiled_remaining
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

	static function key_on_sub_key_to_compiled_remaining($arrays, $sub_key='id', $options=[]){
		$options['collision_handler'] = function($sub_key_value, $previous_value, $new_value){
			if(is_array($previous_value) && self::is_numeric($previous_value)){
				$previous_value[] = $new_value;
				return $previous_value;
			}else{
				return [$previous_value, $new_value];
			}
		};
		return self::key_on_sub_key_to_remaining($arrays, $sub_key, $options);
	}

	static function is_numeric($array){
		return self::is_numerically_keyed($array);
	}
	static function is_numerically_keyed($array){
		foreach($array as $k=>$v){
			if(!is_int($k)){
				return false;
			}
		}
		return true;
	}
	# like `key_on_sub_key`, but exclude key value from sub array
	static function key_on_sub_key_to_remaining($arrays, $sub_key='id', $options=[]){
		$arrays = (array)$arrays;
		$new_arrays = [];

		if(!$options['collision_handler']){
			$options['collision_handler'] = function($sub_key_value, $previous_value, $new_value){
				throw new Exception('Keys have collided with '.json_encode(func_get_args()));
			};
		}

		if($options['name']){
			foreach($arrays as $array){
				$sub_key_value = $array[$sub_key];
				$value = $array[$options['name']];
				if(array_key_exists($sub_key_value, $new_arrays)){
					$value = $options['collision_handler']($sub_key_value, $new_arrays[$sub_key_value], $value);
				}
				$new_arrays[$sub_key_value] = $value;
			}
		}else{
			reset($arrays);
			$sub_element_count = count(current($arrays));
			if($sub_element_count == 2){
				foreach($arrays as $array){
					$sub_key_value = $array[$sub_key];
					unset($array[$sub_key]);
					$value = array_pop($array);
					if(array_key_exists($sub_key_value, $new_arrays)){
						$value = $options['collision_handler']($sub_key_value, $new_arrays[$sub_key_value], $value);
					}
					$new_arrays[$sub_key_value] = $value;
				}
			}else{
				foreach($arrays as $array){
					$sub_key_value = $array[$sub_key];
					unset($array[$sub_key]);
					$value = $array;
					if(array_key_exists($sub_key_value, $new_arrays)){
						$value = $options['collision_handler']($sub_key_value, $new_arrays[$sub_key_value], $value);
					}
					$new_arrays[$sub_key_value] = $value;
				}
			}
		}
		return $new_arrays;
	}
	static function key_on_sub_key_to_compiled($arrays, $sub_key='id', $options=[]){
		$options['collision_handler'] = function($sub_key_value, $previous_value, $new_value){
			if(is_array($previous_value) && self::is_numeric($previous_value)){
				$previous_value[] = $new_value;
				return $previous_value;
			}else{
				return [$previous_value, $new_value];
			}
		};
		return self::key_on_sub_key($arrays, $sub_key, $options);
	}
	# take a subkey (ex 'bob' of [1=>['bob'=>'sue', 'bill'=>'moe']]) and make that the primary key
	/* Ex:
			{1:		{	"bob": "sue",	"bill": "moe"}}
	#>		{"sue":	{	"bob": "sue",	"bill": "moe"}}
	*/
	static function key_on_sub_key($arrays, $sub_key='id', $options=[]){
		$new_arrays = [];

		if(!$options['collision_handler']){
			$options['collision_handler'] = function($sub_key_value, $previous_value, $new_value){
				throw new Exception('Keys have collided with '.json_encode(func_get_args()));
			};
		}

		foreach($arrays as $array){
			$sub_key_value = $array[$sub_key];
			$value = $array;
			if(array_key_exists($sub_key_value, $new_arrays)){
				$value = $options['collision_handler']($sub_key_value, $new_arrays[$sub_key_value], $value);
			}
			$new_arrays[$sub_key_value] = $value;
		}
		return $new_arrays;
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

	# same as self::replace, but uses array_replace_recursive
	static function replace_recursive($x,$y){
		$arrays = func_get_args();
		$result = [];
		foreach($arrays as $array){
			if(is_object($array)){
				$array = self::from($array);
			}
			if(is_array($array)){
				$result = array_replace_recursive($result,$array);
			}
		}
		return $result;
	}



	# filter($key,$value){ return true||false; }
	function filter_deep($array, $allow){
		$result = [];
		foreach($array as $k=>$v){
			if($allow($k,$v) !== false){
				if(is_array($v) || ($v instanceof \Iterator)){
					$v = self::filter_deep($v, $allow);
				}
				$result[$k] = $v;
			}
		}
		return $result;
	}
	# false equivalent return removes item.  Any other return changes the value of the item
	static function filter_morph($array, $callback){
		$result = [];
		foreach($array as $k=>$v){
			$value = $callback($v, $k);
			if($value){
				$result[$k] = $v;
			}
		}
		return $result;
	}

	static function iterator_to_array_deep($iterator, $use_keys = true) {
		$array = array();
		foreach ($iterator as $key => $value) {
			if(is_array($value) || ($value instanceof \Iterator)){
				$value = self::iterator_to_array_deep($value, $use_keys);
			}
			if ($use_keys) {
				$array[$key] = $value;
			} else {
				$array[] = $value;
			}
		}
		return $array;
	}
	static function diff($one, $two, $stict=true){
		$set = [];
		foreach($one as $v){
			if(!in_array($v, $two, $strict)){
				$set[] = $v;
			}
		}
		return $set;
	}

	static function intersect($one, $two, $strict=true){
		$set = [];
		foreach($one as $v){
			if(in_array($v, $two, $strict)){
				$set[] = $v;
			}
		}
		return $set;
	}
	/* About.md
	like array_unique, but
	-	array_unique defaults to converting comparison to string.  This uses the `SORT_REGULAR` flag to avoid that, allowing comparison of values that are complex, like subarrays
	-	the return does not preserve the keys, so this will not cause json_encode to create an object instead of an array
	*/
	static function unique($list){
		return array_values(array_unique($list, SORT_REGULAR));
	}
}
