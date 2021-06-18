<?php
namespace Grithin;

class MissingValue implements \JsonSerializable{
	public function jsonSerialize(){
		return null;
	}
	public function __toString(){
		return '___ Missing Value ___';
	}
	static function remove($container){
		return Arrays::filter_deep($container, [__CLASS__, 'allow']);
	}
	static function allow($k, $v){
		if(is_object($v) && is_a($v, __CLASS__)){
			return false;
		}
		return true;
	}
}
