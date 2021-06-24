<?php
namespace Grithin;

use \ArrayObject;
use \Exception;

/// Useful array related functions I didn't, at one time, find in php
/*
@NOTE	on parameter order: Following lodash wherein the operatee, the source array, is the first parameter.  To be applied to all new functions with such an operatee
*/
class Arrays{
	# use __toArray if exists on object
	# @note	if it is a string, will attempt to explode it using divider, unless divider is not set
	static function from($x, $divider=',\s*'){
		if(is_string($x)){
			return self::from_string($x, $divider);
		}elseif(is_object($x)){
			return self::from_object($x);
		}else{
			return (array)$x;
		}
	}
	# alias for `from`
	static function to_array($var, $divider=',\s*'){
		return self::from($var, $divider);
	}
	# alias for `from`.  Provided for expectancy
	static function toArray($var, $divider=',\s*'){
		return self::from($var, $divider);
	}
	static function from_object($x){
		if(method_exists($x, '__toArray')){ # hopefully PHP adds this at some point
			return $x->__toArray();
		}else{
			/* get_object_vars vs (array)
				`(array)$x` will create keys for private and protected attributes, but those keys will have special utf character prefixes to indicate their status.  They are generally not useful as plain name keys
				`get_object_vars($x)` will not get private and protected attributes
			*/
			return get_object_vars($x);
		}
	}
	static function from_string($string, $divider=',\s*'){
		if($divider){
			return (array)preg_split("@$divider@",$string);
		}else{
			return (array)$var;
		}
	}
	# intended to replace `(array)$x` with the added feature of using __toArray if available on object
	static function convert(){
		if(is_object($x) && method_exists($x, '__toArray')){
			return $x->__toArray();
		}
		return (array)$x;
	}

	# Whether the keys are excluslive numeric
	static function is_numeric($array){
		foreach($array as $k=>$v){
			if(!is_int($k)){
				return false;
			}
		}
		return true;
	}




	/// extract, if present, specified keys
	static function pick($src, $props){
		$props = Arrays::from($props);
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
		$props = Arrays::from($props);
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
		$props = self::from($props);
		$dest = [];
		foreach($src as $key=>$value){
			if(!in_array($key, $props)){
				$dest[$key] = $value;
			}
		}
		return $dest;
	}


	/// lodash has
	static function has($collection, $path){
		try{
			self::get($collection, $path, ['make'=>false]);
			return true;
		}catch(\Exception $e){
			return false;
		}
	}

	# lodash get, with exception upon not found
	static function got($collection, $path){
		return self::get($collection, $path, ['make'=>false]);
	}

	/// lodash get.  Works with arrays and objects.  Specially handling for part which is a obj.method
	static function &get(&$collection=[], $path='', $options=[]){
		$defaults = ['make'=>true];
		$options = array_merge($defaults, $options);

		if($path === ''){
			return $collection;
		}

		if(is_string($path)){
			$path_parts = explode('.', $path);
		}elseif(is_numeric($path)){
			$path_parts = [$path];
		}else{
			$path_parts = self::from($path);
		}

		$reference_to_last =& $collection;
		$parsed = [];
		$not_found = function($key) use ($path, $parsed){ throw new \Exception('path not found at "'.$key.'" of "'.$path.'" at "'.$parsed.'"'); };
		foreach($path_parts as $part){
			if(is_array($reference_to_last) || (is_object($reference_to_last) && ($reference_to_last instanceof \ArrayAccess))){
				if($options['make'] || self::is_set($reference_to_last, $part)){
					$reference_to_last =& $reference_to_last[$part]; # will either find or create at key
				}else{
					$not_found($part);
				}
			}elseif(is_object($reference_to_last)){
				if(isset($reference_to_last->$part)){ # property on object exists
					$reference_to_last =& $reference_to_last->$part;
				}elseif(is_callable([$reference_to_last, $part])){ # it's a callable that is not a property
					$reference_to_last = [$reference_to_last, $part]; # can't return a reference to a method, so just return a callable item
				}else{
					if($options['make']){
						$reference_to_last =& $reference_to_last->$part; # attempt to create attribute
					}else{
						$not_found($part);
					}

				}
			}elseif(is_null($reference_to_last)){
				if($options['make']){
					# PHP will turn the null `reference_to_last` into an array, and then create the accessed key for referencing
					$reference_to_last =& $reference_to_last[$part];
				}else{
					$not_found($part);
				}
			}else{
				if(!empty($options['path_error_handler'])){
					$options['path_error_handler']($reference_to_last, $value, $parsed, $part, $path, $collection, $options);
				}else{
					throw new \Exception('Can not expand into path at "'.$part.'" with "'.$path.'"');
				}

			}
			$parsed[] = $part;
		}
		return $reference_to_last;
	}
	# set($path, $value, $collection=[], $options=[]){
	static function &set(&$collection=[], $path='', $value=null, $options=[]){
		$target = &self::get($collection, $path, $options);
		$target = $value;
		return $target;
	}


	# like set, but when a value exists at a path, turn it into and array and append
	static function set_new_or_expand(&$collection=[], $path='', $value=null, $options=[]){
		$requried_options = [
			'make' => 'true',
			# turn a non-expandable value into an array
			'path_error_handler' => function(&$reference_to_last, $value){
				$deferenced_value = $reference_to_last;
				$reference_to_last = [$deferenced_value, $value];},
			# turn a collided value into an array
		];
		$options = array_merge($options, $requried_options);

		$target = &self::get($collection, $path, $options);

		$set = function(&$reference_to_last, $value){
			if(is_array($reference_to_last)){
				$reference_to_last[] = $value;
			}elseif(!is_null($reference_to_last)){
				$deferenced_value = $reference_to_last;
				$reference_to_last = [$deferenced_value, $value];
			}else{
				$reference_to_last = $value;
			}
		};
		return $set($target, $value);
	}

	/// change the name of some keys
	static function remap($src, $remap){
		foreach($remap as $k=>$v){
			$dest[$v] = $src[$k];
			unset($src[$k]);
		}
		return array_merge($src,$dest);
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


	# merges class instances and arrays.  Ignores scalars.  Later parameters take precedence
	# different from array_merge in that it merges class instances as arrays

	/* example

	$bob = new StdClass;
	$bob->bill = 'no';

	Arrays::merge(['bill'=>'yes'], $bob);

	=> ['bill'=>'no']

	*/
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
	# merges/replaces objects/arrays.  Does no resquence numeric keys or make linear sequence appends (like array_merge does)
	/* example
	$x = [3=>3, 4=>4];
	$y = [5=>5, 6=>6];

	$r = :this($x, $y);
	{"3": 3,
    "4": 4,
    "5": 5,
    "6": 6}
	*/
	/* @about, merge vs replace
		generally, replace acts as expected, replacing matching keys whether numeric or not.  Merge will append on numeric keys, even if the arrays have non-numeric keys (array is a dictionary).
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


	///for a sequential array, find first gap in key numbers
	static function first_available_key($array){
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


	# see `key_on_sub_key_to_remaining` and `key_on_sub_key_to_compiled`
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

	# like `key_on_sub_key`, but exclude the key from the value array
	# @caution if a single value remains in the value array, the key will point directly to the value instead of the array.  See examples.

	/* example
	\Grithin\GlobalFunctions::init('pretty');

	$x = [
		['id'=>555, 'name'=>'bob', 'age'=> 1],
		['id'=>777, 'name'=>'bill', 'age'=> 2],
	];
	=> {
		"555": {
        "name": "bob",
        "age": 1},
    	"777": {
        "name": "bill",
        "age": 2}}
	*/
	/* example, if single value remains, use value instead of [value]
	$x = [
		['id'=>555, 'name'=>'bob'],
		['id'=>777, 'name'=>'bill'],
	];

	$r = Arrays::key_on_sub_key_to_remaining($x, 'id');
	=> {"555": "bob", "777": "bill"}
	*/
	/* example, `only` option
	$x = [
		['id'=>555, 'name'=>'bob', 'age'=> 1],
		['id'=>777, 'name'=>'bill', 'age'=> 2],
	];

	$r = Arrays::key_on_sub_key_to_remaining($x, 'id', ['name'=>'only']);
	*/
	/* params
	< options >:
		only: < a key to be used as the only value in the array of values >
	*/
	static function key_on_sub_key_to_remaining($arrays, $sub_key='id', $options=[]){
		$arrays = (array)$arrays;
		$new_arrays = [];

		if(empty($options['collision_handler'])){
			$options['collision_handler'] = function($sub_key_value, $previous_value, $new_value){
				throw new Exception('Keys have collided with '.json_encode(func_get_args()));
			};
		}

		if(!empty($options['only'])){
			foreach($arrays as $array){
				$sub_key_value = $array[$sub_key];
				$value = $array[$options['only']];
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

	// like key_on_sub_key, but compiled colliding rows into an array
	/* example
	$x = [
		['id'=>555, 'name'=>'bob'],
		['id'=>777, 'name'=>'bill'],
		['id'=>777, 'name'=>'moe'],
	];

	$r = Arrays::key_on_sub_key_to_compiled($x, 'id');
	=>	{"555": {
	        "id": 555,
	        "name": "bob"},
	    "777": [
	        {"id": 777,
	            "name": "bill"},
	        {"id": 777,
	            "name": "moe"}]}
	*/
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
	/* example
		$x = [
			['id'=>555, 'name'=>'bob'],
			['id'=>777, 'name'=>'bill'],
		];
		::key_on_sub_key($x, 'id');

		=> [
			'555'=> ['id'=>555, 'name'=>'bob'],
			'777' => ['id'=>777, 'name'=>'bill']
		]
	*/
	/* example
	$x = [
		['id'=>555, 'name'=>'bob'],
		['id'=>777, 'name'=>'bill'],
		['id'=>777, 'name'=>'moe'],
	];

	$r = Arrays::key_on_sub_key($x, 'id');
	=> Exception, key collision
	*/
	/* params
	sub_key:
	options:
		collision_handler: < function(key, existing_value, new_value) => (new value)  >
	*/
	static function key_on_sub_key($arrays, $sub_key='id', $options=[]){
		$new_arrays = [];

		if(empty($options['collision_handler'])){
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
	/// Turns nested objects, at any level of depth, into arrays
	static function convert_deep($variable,$parseObject=true){
		if(is_object($variable)){
			if($parseObject){
				$parts = self::from_object($variable);
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
