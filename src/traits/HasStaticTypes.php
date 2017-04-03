<?
namespace Grithin;

use \Exception;

trait HasStaticTypes{
	# static $types_by_id = [];
	# static $types_by_name = [];

	static function type_by_id($id){
		if(array_key_exists($id, self::$types_by_id)){
			return self::$types_by_id[$id];
		}
		throw new Exception('id not found "'.$id.'"');
	}
	static function types_by_ids($ids){
		return array_map([self, 'type_by_id'], $ids);
	}
	static function type_by_name($name){
		if(array_key_exists($name, self::$types_by_name)){
			return self::$types_by_name[$name];
		}
		throw new Exception('name not found "'.$name.'"');
	}
	# to be compatible with VariedParameter trait
	static function type_id_by_name($name){
		return self::type_by_name($name);
	}
	static function types_by_names($names){
		return array_map([self, 'type_by_name'], $names);
	}
	# extract types from db table and set static variables
	static function types_from_database($db, $table, $id_column='id', $name_column='name'){
		$rows = $db->all($table, [$id_column, $name_column]);
		$types_by_id = [];
		$types_by_name = [];
		foreach($rows as $row){
			$types_by_name[$row[$name_column]] = $row[$id_column];
			$types_by_id[$row[$id_column]] = $row[$name_column];
		}
		self::$types_by_name = $types_by_name;
		self::$types_by_id = $types_by_id;
		return ['types_by_name'=>$types_by_name, 'types_by_id'=>$types_by_id];
	}
}