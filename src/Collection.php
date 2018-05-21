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
}
