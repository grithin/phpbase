<?
namespace Grithin;

/* Use Example

class PaymentType extends \Grithin\TypeMap{
	static $id_column = 'id';
	static $display_name_column = 'name';
	static $system_name_column = 'name';
	static $types_by_id = array (
  1 =>
  array (
    'id' => 1,
    'name' => 'credit card',
  ),
);
	static $type_ids_by_name = array (
  'credit card' => 1,
);
	static function class($string){
		$class_name = self::class_name($string);
		return new $class_name;
	}
	static function class_name($string){
		$platform = self::static_prefixed_item_by_string('type', $string);
		return $platform['class'];
	}
}
*/

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
	static function get($thing){
		return self::static_prefixed_item_by_thing('type', $thing);
	}
}
