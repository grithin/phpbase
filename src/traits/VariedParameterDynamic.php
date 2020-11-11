<?
/* About
See VariedParameter.php

To allow either instance or static calls, `$that = $this ?: get_called_class();` is used.  `__call` and `__callStatic` are not used so as not to require a `$that` parameter to the various functions

*/
namespace Grithin;

use \Exception;

trait VariedParameterDynamic{
	public function call_guaranteed_identity($function, $arg){
		$that = $this ?: get_called_class();
		$result = [$that, $function]($arg);
		if($result === false){
			throw new Exception('identity not found '.json_encode(array_slice(func_get_args(),1)));
		}
		return $result;
	}


	# assuming the thing is either the id or contains it
	function id_from_thing($thing, $id_column='id'){
		$that = $this ?: get_called_class();
		if(!Tool::is_scalar($thing)){
			return [$that, 'id_from_object']($thing, $id_column);
		}
		return [$that, 'id_from_string']($thing);
	}
	# assuming the thing is either the id or contains it
	function id_from_thing_or_error($thing, $id_column='id'){
		$that = $this ?: get_called_class();
		if(!Tool::is_scalar($thing)){
			return [$that, 'id_from_object_or_error']($thing, $id_column);
		}
		$id = [$that, 'id_from_string']($thing);
		if(!$id){
			throw new Exception('thing was not id');
		}
		return $id;
	}
	/*
	Take what could be an id or an array or an object, and turn it into an id
	*/
	function id_from_object($thing, $id_column='id'){
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

	function id_from_object_or_error($thing, $id_column='id'){
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





	#+++++++++++++++     Non Prefixed Versions     +++++++++++++++ {

	# Standard way to resolve variable input of either a id or a name identifier
	# uses `[$that, 'id_by_name`']
	public function id_from_string($string){
		$that = $this ?: get_called_class();
		if(Tool::isInt($string)){
			return $string;
		}
		$id = [$that, 'id_by_name']($string);
		if($id === false){
			throw new Exception('id not found from '.json_encode(func_get_args()));
		}
		return $id;
	}

	public function id_by_name($name){
		$that = $this ?: get_called_class();
		return [$that, 'call_guaranteed_identity']('id_by_name', $name);
	}
	/*	param
	options	['id_column':<>, 'table':<>]
	*/
	public function id_by_thing($thing, $options=[]){
		$that = $this ?: get_called_class();
		$options = array_merge(['id_column'=>'id'], $options);
		if(!Tool::is_scalar($thing)){
			return [$that, 'id_from_object_or_error']($thing, $options['id_column']);
		}
		return [$that, 'id_from_string']($thing);
	}
	public function ids_by_things($things, $options=[]){
		$that = $this ?: get_called_class();
		$map = function ($x) use ($options, $that){
			return [$that, 'id_by_thing']($x, $options); };
		return array_map($map, $things);
	}


	# uses [$that, 'item_by_id or $this->item_by_name']
	public function item_by_string($string){
		$that = $this ?: get_called_class();
		$item = false;
		if(Tool::isInt($string)){
			$item = [$that, 'item_by_id']($string);
		}else{
			$item = [$that, 'item_by_name']($string);
		}
		if($item === false){
			throw new Exception('id not found from '.json_encode(func_get_args()));
		}
		return $item;
	}

	public function item_by_thing($thing){
		$that = $this ?: get_called_class();
		if(!Tool::is_scalar($thing)){ # the thing is already an item
			return $thing;
		}
		return [$that, 'item_by_string']($thing);
	}
	public function item_by_name($name){
		$that = $this ?: get_called_class();
		$function = 'by_name';
		return [$that, 'call_guaranteed_identity']($function, $name);
	}
	public function item_by_id($id){
		$that = $this ?: get_called_class();
		$function = 'by_id';
		return [$that, 'call_guaranteed_identity']($function, $id);
	}

	#+++++++++++++++          +++++++++++++++ }

	#+++++++++++++++     Prefixed Versions     +++++++++++++++ {


	# Standard way to resolve variable input of either a id or a name identifier
	# uses `[$that, 'prefixed_id_by_name`']
	public function prefixed_id_from_string($prefix, $string){
		$that = $this ?: get_called_class();
		if(Tool::isInt($string)){
			return $string;
		}
		$id = [$that, 'prefixed_id_by_name']($prefix, $string);
		if($id === false){
			throw new Exception('id not found from '.json_encode(func_get_args()));
		}
		return $id;
	}

	public function prefixed_id_by_name($prefix, $name){
		$that = $this ?: get_called_class();
		$function = $prefix.'_id_by_name';
		return [$that, 'call_guaranteed_identity']($function, $name);
	}
	/*	param
	options	['id_column':<>, 'table':<>]
	*/
	public function prefixed_id_by_thing($prefix, $thing, $options=[]){
		$that = $this ?: get_called_class();
		$options = array_merge(['id_column'=>'id'], $options);
		if(!Tool::is_scalar($thing)){
			return [$that, 'prefixed_id_from_object_or_error']($thing, $options['id_column']);
		}
		return [$that, 'prefixed_id_from_string']($prefix, $thing);
	}
	public function prefixed_ids_by_things($prefix, $things, $options=[]){
		$that = $this ?: get_called_class();
		$map = function ($x) use ($prefix, $options, $that){
			return [$that, 'prefixed_id_by_thing']($prefix, $x, $options); };
		return array_map($map, $things);
	}


	# uses [$that, 'prefixed_item_by_id or $this->prefixed_item_by_name']
	public function prefixed_item_by_string($prefix, $string){
		$that = $this ?: get_called_class();
		$item = false;
		if(Tool::isInt($string)){
			$item = [$that, 'prefixed_item_by_id']($prefix, $string);
		}else{
			$item = [$that, 'prefixed_item_by_name']($prefix, $string);
		}
		if($item === false){
			throw new Exception('id not found from '.json_encode(func_get_args()));
		}
		return $item;
	}

	public function prefixed_item_by_thing($prefix, $thing){
		$that = $this ?: get_called_class();
		if(!Tool::is_scalar($thing)){ # the thing is already an item
			return $thing;
		}
		return [$that, 'prefixed_item_by_string']($prefix, $thing);
	}
	public function prefixed_item_by_name($prefix, $name){
		$that = $this ?: get_called_class();
		$function = $prefix.'_by_name';
		return [$that, 'call_guaranteed_identity']($function, $name);
	}
	public function prefixed_item_by_id($prefix, $id){
		$that = $this ?: get_called_class();
		$function = $prefix.'_by_id';
		return [$that, 'call_guaranteed_identity']($function, $id);
	}

	#+++++++++++++++          +++++++++++++++ }

}

