<?
namespace Grithin;

class TypeMap{
	use \Grithin\HasStaticTypes;
	use \Grithin\VariedParameter;
	static $id_column = 'id';
	static $display_name_column = 'name';
	static $system_name_column = 'name';
	static function name($id){
		return self::type_by_id($id)[self::$system_name_column];
	}
	static function display($id){
		return self::type_by_id($id)[self::$display_name_column];
	}
	static function id($name){
		return self::type_id_by_name($name);
	}
	static function get($string){
		return self::static_prefixed_item_by_string('type', $string);
	}
}
