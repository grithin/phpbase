<?php
namespace Grithin\Traits;

use \Exception;

/* Notes

On the two types of names:
There are two names a type can have: a displayed name, a system code name.  Whereas the display name is meant for non-programmer consumption, and is subject to change, the system name is for use within the code to make the code clearer than would be the case using type ids, and is not subject to change.  Owing to the ficle nature of display desires, I recommend always separating these two names. (Further, `system_name` is ensured to be unique, whereas the `display_name` is not.)
For this, one can use `system_name` and `display_name`.  And, the use of `name` is for when there is a high likelihood there will be no difference between the two.
The code being prejudiced towards `system_name`, instances of `name` in function names are assumed to relate to `system_name`

Further standards
-	`ordinal` position in appearance
-	`is_hidden`


Thoughts on system_name:
-	system name might be used for a number of things within the code, including
	-	css class
	-	variable names
	-	class or function names
	-	array keys
	The restrictions for these names might incline one towards the use of  restrictive system names.  However, a conforming function can be used, `css_class(system_name)`.  There is another aspect of ensuring simplicity in the naming, so that there is no confusion in the code about spacing or capitalization.  And, primarily for the purpose of keeping things simple and thus reducing errors, my system_name convention is:
	-	all lower case
	-	no spaces, use underscores
	-	no special characters
*/

trait HasStaticTypes{
	/*
	static $id_column = 'id';
	static $display_name_column = 'display_name';
	static $system_name_column = 'system_name';
	static $types_by_id = [];
	static $type_ids_by_name = [];
	*/
	# access to variable, intended to be overwritten for instance to get from Db
	static function types_by_id(){
		return static::$types_by_id;
	}
	# access to variable, intended to be overwritten for instance to get from Db
	static function type_ids_by_name(){
		return static::$type_ids_by_name;
	}
	static function type_ids($names=[]){
		if($names){
			$return = [];
			foreach($names as $v){
				$return[] = static::type_id_by_name($v);
			}
			return $return;
		}else{
			return array_keys(static::types_by_id());
		}
	}
	static function types(){
		return static::types_by_id();
	}
	static function type_by_id($id){
		if(array_key_exists($id, static::types_by_id())){
			return static::types_by_id()[$id];
		}
		throw new Exception('id not found "'.$id.'"');
	}
	static function types_by_ids($ids){
		$return = [];
		foreach($ids as $v){
			$return[] = static::type_by_id($v);
		}
		return $return;
	}
	static function type_by_name($name){
		if(array_key_exists($name, static::type_ids_by_name())){
			return static::types_by_id()[static::type_ids_by_name()[$name]];
		}
		throw new Exception('name not found "'.$name.'"');
	}
	static function type_ids_by_names($names){
		$return = [];
		foreach($names as $v){
			$return[] = static::type_id_by_name($v);
		}
		return $return;
	}
	# to be compatible with VariedParameter trait
	static function type_id_by_name($name){
		return static::type_by_name($name)[static::$id_column];
	}
	# system name by id
	static function type_name_by_id($id){
		return static::type_by_id($id)[static::$system_name_column];
	}
	# display name by id
	static function type_display_by_id($id){
		return static::type_by_id($id)[static::$dislpay_name_column];
	}

	static function types_by_names($names){
		$return = [];
		foreach($names as $v){
			$return[] = static::type_by_name($v);
		}
		return $return;
	}
	# get map of ids to display names
	static function id_display_name_map($types=null){
		if(property_exists(__CLASS__, 'display_name_column')){
			$display_name_column = static::$display_name_column;
		}else{
			$display_name_column = static::$system_name_column;
		}
		$map = [];
		if($types === null){
			$types = static::types_by_id();
		}
		foreach($types as $type){
			$map[$type[static::$id_column]] = $type[$display_name_column];
		}
		return $map;
	}
	# filter and order types
	static function types_ordered_shown($types=null){
		if($types === null){
			$types = static::types_by_id();
		}
		$filtered_types = [];
		foreach($types as $type){
			if(empty($type['is_hidden'])){
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
		$filtered_types = static::types_ordered_shown($types);
		return static::id_display_name_map($filtered_types);
	}


	# extract types from db table and set static variables
	static function types_from_database($db, $table, $options=[]){
		if(empty($options['columns'])){
			$options['columns'] = [];
			$table_info = $db->table_info($table);

			# prefer fixed names
			foreach(['system_name', 'name'] as $name){
				if(!empty($table_info['columns'][$name])){
					$options['columns']['system_name'] = $name;
					break;
				}
			}
			foreach(['display_name', 'name'] as $name){
				if(!empty($table_info['columns'][$name])){
					$options['columns']['display_name'] = $name;
					break;
				}
			}

			# try to find columns
			foreach($table_info['columns'] as $column_name=>$column){
				if($column['key'] == 'primary' && empty($options['columns']['id'])){
					$options['columns']['id'] = $column_name;
				}elseif($column['key'] == 'UNI' && preg_match('@name@', $column_name) && $options['columns']['system_name']){
					$options['columns']['system_name'] = $column_name;
				}
			}
		}

		# apply defaults
		$options['columns']['id'] = $options['columns']['id'] ? $options['columns']['id'] : 'id';
		$options['columns']['system_name'] = $options['columns']['system_name'] ? $options['columns']['system_name'] : 'name';
		$options['columns']['display_name'] = $options['columns']['display_name'] ? $options['columns']['display_name'] : $options['columns']['system_name'];

		$rows = $db->all($table);

		$type_ids_by_name = [];
		$types_by_id = [];
		$return = self::static_variables_from_rows($options['columns'], $rows);
		$return['id_column'] = $options['columns']['id'];
		$return['display_name_column'] = $options['columns']['display_name'];
		$return['system_name_column'] = $options['columns']['system_name'];
		return $return;
	}
	static function static_variables_from_rows($columns, $rows){
		$vars = ['type_ids_by_name'=>[], 'types_by_id'=>[]];
		foreach($rows as $row){
			$vars['type_ids_by_name'][$row[$columns['system_name']]] = $row[$columns['id']];
			$vars['types_by_id'][$row[$columns['id']]] = $row;
		}
		return $vars;
	}
	static function static_variables_code_get_from_db($db, $table, $options=[]){
		return static::static_variables_code_get(static::types_from_database($db, $table, $options));
	}
	/*
	@param	map	{<id>:<name>,... }
	*/
	/* Ex
	this([1=>'success', 2=>'pending', 3=>'fail']);
	*/
	static function types_from_map($map){
		$type_ids_by_name = [];
		$types_by_id = [];
		foreach($map as $id=>$name){
			$type_ids_by_name[$name] = $id;
			$types_by_id[$id] = ['id'=>$id, 'name'=>$name];
		}
		return [
			'id_column' => 'id',
			'display_name_column' => 'name',
			'system_name_column' => 'name',
			'type_ids_by_name'=>$type_ids_by_name,
			'types_by_id'=>$types_by_id
		];
	}
	static function static_variables_code_get_from_map($map){
		return static::static_variables_code_get(static::types_from_map($map));
	}
	static function static_variables_code_get($statics){
		return
			"\n\t# Generated code.  See \Grithin\Traits\HasStaticTypes::static_variables_code_get".
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
		$types = static::types_from_database($db, $table, $id_column, $system_name_column, $display_name_column);
		return [
			'id_column' => $id_column,
			'display_name_column' => $display_name_column,
			'system_name_column' => $system_name_column,
			'types_by_id' => $types['types_by_id'],
			'type_ids_by_name' => $types['type_ids_by_name']
		];
	}
	static function static_variables_set($statics){
		static::$id_column = $statics['id_column'];
		static::$display_name_column = $statics['display_name_column'];
		static::$system_name_column = $statics['system_name_column'];
		static::$types_by_id = $statics['types_by_id'];
		static::$type_ids_by_name = $statics['type_ids_by_name'];
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
