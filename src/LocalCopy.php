<?
namespace Grithin;

use \Exception;

/* about
For handling collecting objects, which may be pull-requested multiple times with different unique identifiers.  Whereas, with memoize, a `get_by_name` plus a `get_by_id` on the same object would yield two db calls, this would potentially just yield one
*/

trait LocalCopy{
	public $local_copies = [];
	public function local_set($type, $record, $options=[]){
		$options = array_merge(['id_column'=>'id'], $options);
		$this->local_set_with_id($type, $record, $record[$options['id_column']]);
	}
	public function local_set_with_id($type, $record, $id){
		$this->local_copies[$type][$id] = $record;
	}
	public function local_get_by_id($type, $id){
		return $this->local_copies[$type][$id];
	}



	public function local_get_or_set($type, $thing, $getter){
		$got = $this->local_get($type, $thing);
		if($got === null){
			$got = $getter($thing);
			$this->local_set($type, $got);
		}
		return $got;
	}
	public function local_get_or_set_by_id($type, $id, $getter){
		$got = $this->local_get_by_id($type, $id);
		if($got === null){
			$got = $getter($id);
			$this->local_set_with_id($type, $got, $id);
		}
		return $got;
	}

	/*
	Reduces input array to scalar value.
	The callback for local_get_or_set accepts ['name'=>'xyz'], but the getter may require the name as a scalar.  This solves that
	*/
	/* Example
	return $this->local_get_or_set('user_role', ['name'=>$name], $this->local_pipe_first([$this, 'user_role_by_name__fresh']) );
	*/
	public function local_pipe_first($callback){
		return function($thing) use ($callback){
			if(is_array($thing)){
				$thing = array_shift($thing);
			}
			return $callback($thing);
		};
	}


	public function local_get($type, $thing){
		if(is_array($thing)){
			return $this->local_get_by_map($type, $thing);
		}else{
			return $this->local_get_by_string($type, $thing);
		}
	}
	public function local_get_by_string($type, $string){
		if(Tool::isInt($string)){
			return $this->local_get_by_id($type, $string);
		}else{
			return $this->local_get_by_map($type, ['name'=>$string]);
		}
	}
	public function local_get_by_map($type, $map){
		foreach((array)$this->local_copies[$type] as $copy){
			foreach($map as $k=>$v){
				if($copy[$k] != $v){
					continue 2;
				}
			}
			return $copy;
		}
	}
}