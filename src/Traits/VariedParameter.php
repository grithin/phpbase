<?php
/* About
For convenience, a function might accept an id, a name, or an object used to identify some object.  This standardises handling such variable input.

In cases with `id_from_object`, there is no need to call an outside function, and either the parameter is the id or it is an object with a id attribute
In cases with `id_from_string`, if the string is numeric, it is considered the id, otherwise `id_from_name` is called, which then calls a implementer defined function $table.'_id_by_name';
In the case of `item_by_thing`, if the thing is not a scalar, it is considered the thing desired; otherwise, `item_by_string` is called. which will call either `item_by_id` or  `item_by_name`. and the respective implementer defined functions would be $table.'_by_id' and $table.'_by_name';

Prefix
With some things, like a type class, there is only one object to be concerned about, and so, with `id_by_thing`, there is only one conceptual thing.  However, wiht somehting like a `Users` class that used these trait functions for a user's profile, address, etc, there are multiple things, which are differentiated by the prefix to their function `profile_by_id`, `address_by_id`.


Notes
-	run on multiple: array_map([$this,'item_by_thing'], $things);

@TODO
-	add `ids_by_things` style methods for the other by_* methods
*/
namespace Grithin\Traits;

use \Grithin\Tool;
use \Exception;

trait VariedParameter{
	/*	Notes on PHP failures affecting the design of the `guaranteed` functions
		-	`forward_static_call_array` does not work.
		-	Turning errors into exceptions does not allow for catching method not found error
		-	not way to execute a call_user_function([static,'fn_name'])
	*/
	static function static_call_guaranteed_identity($function, $arg){
		$result = static::$function($arg);
		if($result === false){
			throw new Exception('identity not found '.json_encode(array_slice(func_get_args(),1)));
		}
		return $result;
		#
	}
	public function call_guaranteed_identity($class, $function, $arg){
		$result = $this->$function($arg);
		if($result === false){
			throw new Exception('identity not found '.json_encode(array_slice(func_get_args(),1)));
		}
		return $result;
	}


	#+++++++++++++++     Static Versions     +++++++++++++++ {

	# assuming the thing is either the id or contains it
	static function static_id_from_thing($thing, $id_column='id'){
		if(!Tool::is_scalar($thing)){
			return self::static_id_from_object($thing, $id_column);
		}
		return $thing;
	}
	# assuming the thing is either the id or contains it
	static function static_id_from_thing_or_error($thing, $id_column='id'){
		if(!Tool::is_scalar($thing)){
			return self::static_id_from_object_or_error($thing, $id_column);
		}
		if(!$thing){
			throw new Exception('thing was not id');
		}
		return $thing;
	}
	/*
	Take what could be an id or an array or an object, and turn it into an id
	*/
	static function static_id_from_object($thing, $id_column='id'){
		if(is_array($thing)){
			if(isset($thing[$id_column])){
				return $thing[$id_column];
			}
			return false;
		}
		if(is_object($thing)){
			if(isset($thing->$id_column)){
				return $thing->$id_column;
			}
			return false;
		}
		return false;
	}

	static function static_id_from_object_or_error($thing, $id_column='id'){
		if(is_array($thing)){
			if(array_key_exists($id_column, $thing)){
				return $thing[$id_column];
			}
			throw new Exception('id column not defined');
		}
		if(is_object($thing)){
			if(isset($thing->$id_column)){
				return $thing->$id_column;
			}
			throw new Exception('id column not defined');
		}
		throw new Exception('thing was not object');
	}



	#+++++++++++++++          +++++++++++++++ }


	#+++++++++++++++     Instance Versions     +++++++++++++++ {

	public function id_from_thing($thing, $id_column='id'){
		return self::static_id_from_thing($thing, $id_column);
	}
	public function id_from_thing_or_error($thing, $id_column='id'){
		return self::static_id_from_thing_or_error($thing, $id_column);
	}
	public function id_from_object($thing, $id_column='id'){
		return self::static_id_from_object($thing, $id_column);
	}
	public function id_from_object_or_error($thing, $id_column='id'){
		return self::static_id_from_object_or_error($thing, $id_column);
	}

	#+++++++++++++++          +++++++++++++++ }





	#+++++++++++++++     Non Prefixed Versions     +++++++++++++++ {

	static function static_id_from_string($string){
		if(Tool::isInt($string)){
			return $string;
		}
		$id = self::static_id_by_name($string);
		if($id === false){
			throw new Exception('id not found from '.json_encode(func_get_args()));
		}
		return $id;
	}

	static function static_id_by_name($name){
		$function = 'id_by_name';
		return self::static_call_guaranteed_identity($function, $name);
	}
	/*	param
	options	['id_column':<>, 'table':<>]
	*/
	static function static_id_by_thing($thing, $options=[]){
		$options = array_merge(['id_column'=>'id'], $options);
		if(!Tool::is_scalar($thing)){
			return self::static_id_from_object_or_error($thing, $options['id_column']);
		}
		return self::static_id_from_string($thing);
	}
	public function static_ids_by_things($things, $options=[]){
		$map = function ($x) use ($options){
			return self::static_id_by_thing($x, $options); };
		return array_map($map, $things);
	}

	static function static_item_by_string($string){
		$item = false;
		if(Tool::isInt($string)){
			$item = self::static_item_by_id($string);
		}else{
			$item = self::static_item_by_name($string);
		}
		if($item === false){
			throw new Exception('id not fround from '.json_encode(func_get_args()));
		}
		return $item;
	}

	static function static_item_by_thing($thing){
		if(!Tool::is_scalar($thing)){ # the thing is already an item
			return $thing;
		}
		return self::static_item_by_string($thing);
	}

	static function static_item_by_name($name){
		$function = 'by_name';
		return self::static_call_guaranteed_identity($function, $name);
	}
	static function static_item_by_id($id){
		$function = 'by_id';
		return self::static_call_guaranteed_identity($function, $id);
	}




	# Standard way to resolve variable input of either a id or a name identifier
	# uses `$this->id_by_name`
	public function id_from_string($string){
		if(Tool::isInt($string)){
			return $string;
		}
		$id = $this->id_by_name($string);
		if($id === false){
			throw new Exception('id not found from '.json_encode(func_get_args()));
		}
		return $id;
	}

	public function id_by_name($name){
		$function = 'id_by_name';
		return self::call_guaranteed_identity($this, $function, $name);
	}
	/*	param
	options	['id_column':<>, 'table':<>]
	*/
	public function id_by_thing($thing, $options=[]){
		$options = array_merge(['id_column'=>'id'], $options);
		if(!Tool::is_scalar($thing)){
			return $this->id_from_object_or_error($thing, $options['id_column']);
		}
		return $this->id_from_string($thing);
	}
	public function ids_by_things($things, $options=[]){
		$map = function ($x) use ($options){
			return $this->id_by_thing($x, $options); };
		return array_map($map, $things);
	}


	# uses $this->item_by_id or $this->item_by_name
	public function item_by_string($string){
		$item = false;
		if(Tool::isInt($string)){
			$item = $this->item_by_id($string);
		}else{
			$item = $this->item_by_name($string);
		}
		if($item === false){
			throw new Exception('id not found from '.json_encode(func_get_args()));
		}
		return $item;
	}

	public function item_by_thing($thing){
		if(!Tool::is_scalar($thing)){ # the thing is already an item
			return $thing;
		}
		return $this->item_by_string($thing);
	}
	public function item_by_name($name){
		$function = 'by_name';
		return $this->call_guaranteed_identity($this, $function, $name);
	}
	public function item_by_id($id){
		$function = 'by_id';
		return $this->call_guaranteed_identity($this, $function, $id);
	}

	#+++++++++++++++          +++++++++++++++ }

	#+++++++++++++++     Prefixed Versions     +++++++++++++++ {


	static function static_prefixed_id_from_string($prefix, $string){
		if(Tool::isInt($string)){
			return $string;
		}
		$id = self::static_prefixed_id_by_name($prefix, $string);
		if($id === false){
			throw new Exception('id not found from '.json_encode(func_get_args()));
		}
		return $id;
	}

	static function static_prefixed_id_by_name($prefix, $name){
		$function = $prefix.'_id_by_name';
		return self::static_call_guaranteed_identity($function, $name);
	}
	/*	param
	options	['id_column':<>, 'table':<>]
	*/
	static function static_prefixed_id_by_thing($prefix, $thing, $options=[]){
		$options = array_merge(['id_column'=>'id'], $options);
		if(!Tool::is_scalar($thing)){
			return self::static_prefixed_id_from_object_or_error($thing, $options['id_column']);
		}
		return self::static_prefixed_id_from_string($prefix, $thing);
	}
	static function static_prefixed_ids_by_things($prefix, $things, $options=[]){
		$map = function ($x) use ($prefix, $options){
			return self::static_prefixed_id_by_thing($prefix, $x, $options); };
		return array_map($map, $things);
	}




	static function static_prefixed_item_by_string($prefix, $string){
		$item = false;
		if(Tool::isInt($string)){
			$item = self::static_prefixed_item_by_id($prefix, $string);
		}else{
			$item = self::static_prefixed_item_by_name($prefix, $string);
		}
		if($item === false){
			throw new Exception('id not fround from '.json_encode(func_get_args()));
		}
		return $item;
	}

	static function static_prefixed_item_by_thing($prefix, $thing){
		if(!Tool::is_scalar($thing)){ # the thing is already an item
			return $thing;
		}
		return self::static_prefixed_item_by_string($prefix, $thing);
	}

	static function static_prefixed_item_by_name($prefix, $name){
		$function = $prefix.'_by_name';
		return self::static_call_guaranteed_identity($function, $name);
	}
	static function static_prefixed_item_by_id($prefix, $id){
		$function = $prefix.'_by_id';
		return self::static_call_guaranteed_identity($function, $id);
	}





	# Standard way to resolve variable input of either a id or a name identifier
	# uses `$this->prefixed_id_by_name`
	public function prefixed_id_from_string($prefix, $string){
		if(Tool::isInt($string)){
			return $string;
		}
		$id = $this->prefixed_id_by_name($prefix, $string);
		if($id === false){
			throw new Exception('id not found from '.json_encode(func_get_args()));
		}
		return $id;
	}

	public function prefixed_id_by_name($prefix, $name){
		$function = $prefix.'_id_by_name';
		return $this->call_guaranteed_identity($this, $function, $name);
	}
	/*	param
	options	['id_column':<>, 'table':<>]
	*/
	public function prefixed_id_by_thing($prefix, $thing, $options=[]){
		$options = array_merge(['id_column'=>'id'], $options);
		if(!Tool::is_scalar($thing)){
			return $this->prefixed_id_from_object_or_error($thing, $options['id_column']);
		}
		return $this->prefixed_id_from_string($prefix, $thing);
	}
	public function prefixed_ids_by_things($prefix, $things, $options=[]){
		$map = function ($x) use ($prefix, $options){
			return $this->prefixed_id_by_thing($prefix, $x, $options); };
		return array_map($map, $things);
	}


	# uses $this->prefixed_item_by_id or $this->prefixed_item_by_name
	public function prefixed_item_by_string($prefix, $string){
		$item = false;
		if(Tool::isInt($string)){
			$item = $this->prefixed_item_by_id($prefix, $string);
		}else{
			$item = $this->prefixed_item_by_name($prefix, $string);
		}
		if($item === false){
			throw new Exception('id not found from '.json_encode(func_get_args()));
		}
		return $item;
	}

	public function prefixed_item_by_thing($prefix, $thing){
		if(!Tool::is_scalar($thing)){ # the thing is already an item
			return $thing;
		}
		return $this->prefixed_item_by_string($prefix, $thing);
	}
	public function prefixed_item_by_name($prefix, $name){
		$function = $prefix.'_by_name';
		return $this->call_guaranteed_identity($this, $function, $name);
	}
	public function prefixed_item_by_id($prefix, $id){
		$function = $prefix.'_by_id';
		return $this->call_guaranteed_identity($this, $function, $id);
	}

	#+++++++++++++++          +++++++++++++++ }

}

