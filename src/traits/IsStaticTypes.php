<?php
namespace Grithin;

use \Exception;


trait IsStaticTypes{
	use HasStaticTypes;
	use VariedParameterDynamic;

	public function id_by_name($name){
		$that = $this ?: get_called_class();
		return [$that, 'call_guaranteed_identity']('type_id_by_name', $name);
	}
	public function by_object($object){
		$that = $this ?: get_called_class();
		return [$that, 'call_guaranteed_identity']('item_by_object', $object);
	}
	public function by_thing($thing){
		$that = $this ?: get_called_class();
		return [$that, 'call_guaranteed_identity']('item_by_thing', $thing);
	}
	public function by_name($name){
		$that = $this ?: get_called_class();
		return [$that, 'call_guaranteed_identity']('type_by_name', $name);
	}
	public function by_id($id){
		$that = $this ?: get_called_class();
		return [$that, 'call_guaranteed_identity']('type_by_id', $id);
	}

}
