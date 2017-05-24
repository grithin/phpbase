<?
namespace Grithin;

use \Exception;

/* Notes

On the two types of names:
There are two names a type can have: a displayed name, a system code name.  Whereas the display name is meant for non-programmer consumption, and is subject to change, the system name is for use within the code to make the code clearer than would be the case using type ids, and is not subject to change.  Owing to the ficle nature of display desires, I recommend always separating these two names. (Further, `system_name` is ensured to be unique, whereas the `display_name` is not.)
For this, one can use `system_name` and `display_name`.  And, the use of `name` is for when there is a high likelihood there will be no difference between the two.
The code being prejudiced towards `system_name`, instances of `name` in function names are assumed to relate to `system_name`

Further standards
-	`ordinal` position in appearance
-	`is_hidden`
*/

trait HasStaticTypes{
	/*
	static $id_column = 'id';
	static $display_name_column = 'display_name';
	static $system_name_column = 'system_name';
	static $types_by_id = [];
	static $type_ids_by_name = [];
	*/
	static function type_ids(){
		return array_keys(self::$types_by_id);
	}
	static function types(){
		return self::$types_by_id;
	}
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
		if(array_key_exists($name, self::$type_ids_by_name)){
			return self::$types_by_id[self::$type_ids_by_name[$name]];
		}
		throw new Exception('name not found "'.$name.'"');
	}
	# to be compatible with VariedParameter trait
	static function type_id_by_name($name){
		return self::type_by_name($name)[self::$id_column];
	}
	static function types_by_names($names){
		return array_map([self, 'type_by_name'], $names);
	}
	# get map of ids to display names
	static function id_display_name_map($types=null){
		if(property_exists(__CLASS__, 'display_name_column')){
			$display_name_column = self::$display_name_column;
		}else{
			$display_name_column = self::$system_name_column;
		}
		$map = [];
		if($types === null){
			$types = self::$types_by_id;
		}
		foreach($types as $type){
			$map[$type[self::$id_column]] = $type[$display_name_column];
		}
		return $map;
	}
	# filter and order types
	static function types_ordered_shown($types=null){
		if($types === null){
			$types = self::$types_by_id;
		}
		$filtered_types = [];
		foreach($types as $type){
			if(!$type['is_hidden']){
				$filtered_types[] = $type;
			}
		}
		$ordinal_sort = function($a,$b){
			if($a['ordinal'] < $b['ordinal']){
				return -1;
			}elseif($a['ordinal'] === $b['ordinal']){
				return 0;
			}else{
				return 1;
			}
		};
		uasort($filtered_types, $ordinal_sort);
		return $filtered_types;
	}
	# id display map, but only with filtered and ordered
	# for use with `<select>` and  `$.options_fill`
	static function id_display_name_map_ordered_shown($types=null){
		$filtered_types = self::types_ordered_shown($types);
		return self::id_display_name_map($filtered_types);
	}


	# extract types from db table and set static variables
	static function types_from_database($db, $table, $id_column='id', $system_name_column=null, $display_name_column=null){
		$rows = $db->all($table);

		$type_ids_by_name = [];
		$types_by_id = [];
		foreach($rows as $row){
			$type_ids_by_name[$row[$system_name_column]] = $row[$id_column];
			$types_by_id[$row[$id_column]] = $row;
		}
		return [
			'type_ids_by_name'=>$type_ids_by_name,
			'types_by_id'=>$types_by_id
		];
	}
	static function static_variables_code_get($db, $table, $id_column='id', $system_name_column=null, $display_name_column=null){
		$statics = self::static_variables_get_from_db($db, $table, $id_column, $system_name_column, $display_name_column);
		return
			"\n\t# Generated code.  See \Grithin\HasStaticTypes::static_variables_code_get".
			"\n\t".'static $id_column = '.var_export($statics['id_column'], true).';'.
			"\n\t".'static $display_name_column = '.var_export($statics['display_name_column'], true).';'.
			"\n\t".'static $system_name_column = '.var_export($statics['system_name_column'], true).';'.
			"\n\t".'static $types_by_id = '.var_export($statics['types_by_id'], true).';'.
			"\n\t".'static $type_ids_by_name = '.var_export($statics['type_ids_by_name'], true).';';
	}
	static function static_variables_get_from_db($db, $table, $id_column='id', $system_name_column=null, $display_name_column=null){
		if($system_name_column === null || $display_name_column === null){
			if($system_name_column === null){
				$columns = $db->column_names($table);
				if(in_array('system_name', $columns)){
					$system_name_column = 'system_name';
				}else{
					$system_name_column = 'name';
				}
			}
			if($display_name_column === null){
				if(in_array('display_name', $columns)){
					$display_name_column = 'display_name';
				}else{
					$display_name_column = 'name';
				}
			}
		}
		$types = self::types_from_database($db, $table, $id_column, $system_name_column, $display_name_column);
		return [
			'id_column' => $id_column,
			'display_name_column' => $display_name_column,
			'system_name_column' => $system_name_column,
			'types_by_id' => $types['types_by_id'],
			'type_ids_by_name' => $types['type_ids_by_name']
		];
	}
	static function static_variables_set($statics){
		self::$id_column = $statics['id_column'];
		self::$display_name_column = $statics['display_name_column'];
		self::$system_name_column = $statics['system_name_column'];
		self::$types_by_id = $statics['types_by_id'];
		self::$type_ids_by_name = $statics['type_ids_by_name'];
	}
}

/* Create table and code for testing
create table type_test(
 id int auto_increment,
 ordinal int,
 system_name varchar(50),
 display_name varchar(300),
 is_hidden boolean default 0 not null,
 primary key (id),
 unique key system_name (system_name)
) engine MyISAM charset utf8;

insert into type_test (ordinal, system_name, display_name, is_hidden) values
(5, 'bob1', 'bob one', 0),
(4, 'bob2', 'bob two', 0),
(3, 'bob3', 'bob three', 1),
(2, 'bob4', 'bob four', 0),
(1, 'bob5', 'bob five', 0);

class TypeTest{
	use HasStaticTypes;
}

echo TypeTest::static_variables_code_get(Db::primary(), 'type_test');

*/
