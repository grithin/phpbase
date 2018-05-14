<?
namespace Grithin;

trait SelfCall{
	# if static, prefer `static` instead of `self`
	static function self_static($this, $method, $params){
		if($this){
			return call_user_func_array([$this, $method], $params);
		}else{
			return call_user_func_array(['static', $method], $params);
		}
	}
	# if static, prefer `self` instead of `static`
	static function self_self($this, $method, $params){
		$params = array_slice(func_get_args(), 2);
		if($this){
			return call_user_func_array([$this, $method], $params);
		}else{
			return call_user_func_array(['self', $method], $params);
		}
	}
}