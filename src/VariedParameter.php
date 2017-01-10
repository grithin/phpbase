<?
/* About
For convenience, a function might accept an id, a name, or an object used to identify some object.  This standardises handling such variable input.

In cases with `id_from_object`, there is no need to call an outside function, and either the parameter is the id or it is an object with a id attribute
In cases with `id_from_string`, if the string is numeric, it is considered the id, otherwise `id_from_name` is called, which then calls a implementer defined function $table.'_id_by_name';
In the case of `item_by_thing`, if the thing is not a scalar, it is considered the thing desired; otherwise, `item_by_string` is called. which will call either `item_by_id` or  `item_by_name`. and the respective implementer defined functions would be $table.'_by_id' and $table.'_by_name';

*/
namespace Grithin;

use \Exception;

trait VariedParameter{
	static function guaranteed_call($class, $function, $args){
		if(method_exists($class, $function)){
			$result = call_user_func_array([$class, $function], $args);
			if($result === false){
				throw new Exception('id not found '.json_encode(array_slice(func_get_args(),1)));
			}
			return $result;
		}else{
			throw new Exception('function not found '.json_encode(array_slice(func_get_args(),1)));
		}
	}

	/*
	Take what could be an id or an array or an object, and turn it into an id
	*/
	static function static_id_from_object($thing, $id_column='id'){
		if(is_array($thing)){
			if(isset($thing[$id_column])){
				return $thing[$id_column];
			}
			return false;
		}
		if(is_object($thing)){
			if(isset($thing->$id_column)){
				return $thing->$id_column;
			}
			return false;
		}
		return $thing;
	}
	static function static_id_from_object_or_error($thing, $id_column='id'){
		if(is_array($thing)){
			if(isset($thing[$id_column])){
				return $thing[$id_column];
			}
			throw new Exception('id column not defined');
		}
		if(is_object($thing)){
			if(isset($thing->$id_column)){
				return $thing->$id_column;
			}
			throw new Exception('id column not defined');
		}
		return $thing;
	}
	static function static_id_from_string($table, $string){
		if(Tool::isInt($string)){
			return $string;
		}
		$id = self::static_id_by_name($table, $string);
		if($id === false){
			throw new Exception('id not fround from '.json_encode(func_get_args()));
		}
		return $id;
	}

	static function static_id_by_name($table, $name){
		$function = $table.'_id_by_name';
		if(method_exists(__CLASS__, $function)){
			$result = call_user_func([self, $function], $name);
			if($result === false){
				throw new Exception('id not found '.json_encode(func_get_args()));
			}
			return $result;
		}else{
			throw new Exception('function not found '.json_encode(func_get_args()));
		}
	}
	static function static_id_by_name_from_db($table, $name){
		if(property_exists(__CLASS__, 'db')){
			$id_by_name_get_rules = self::static_id_by_name_get_rules();
			if($id_by_name_get_rules[$table]){
				$column = $id_by_name_get_rules[$table];
			}else{
				$column = 'name'; # default to 'name' column
			}

			return self::$db->as_value('select id from '.$table.' where '.$column.' = ?',[$name]);
		}else{
			throw new Exception('No method for finding id from name');
		}
	}
	# expects `static $id_by_name_get_rules = [];`
	# will use `$id_by_name_rules`, such as `public $id_by_name_rules = [	'user_role'=>'name', ];` if present
	static function static_id_by_name_get_rules(){
		if(property_exists(__CLASS__, 'id_by_name_get_rules')){
			return self::$id_by_name_get_rules;
		}
		return [];
	}

	# convenience function
	static function static_item_by_name_from_db($table, $name){
		if(property_exists(__CLASS__, 'db')){
			$id_by_name_get_rules = self::static_id_by_name_get_rules();
			if($id_by_name_get_rules[$table]){
				$column = $id_by_name_get_rules[$table];
			}else{
				$column = 'name'; # default to 'name' column
			}

			return self::$db->as_row('select id from '.$table.' where '.$column.' = ?',[$name]);
		}else{
			throw new Exception('No method for finding id from name');
		}
	}

	/*	param
	options	['id_column':<>, 'table':<>]
	*/
	static function static_id_by_thing($table, $thing, $options=[]){
		$options = array_merge(['id_column'=>'id'], $options);
		$id = self::static_id_from_object_or_error($thing, $options['id_column']);
		$id = self::static_id_from_string($table, $id);
		return $id;
	}

	static function static_item_by_string($table, $string){
		$item = false;
		if(Tool::isInt($string)){
			$item = self::static_item_by_id($table, $string);
		}else{
			$item = self::static_item_by_name($table, $string);
		}
		if($item === false){
			throw new Exception('id not fround from '.json_encode(func_get_args()));
		}
		return $item;
	}

	static function static_item_by_thing($thing, $table){
		if(!is_scalar($thing)){ # the thing is already an item
			return $thing;
		}
		return self::static_item_by_string($thing, $table);
	}

	static function static_item_by_name($table, $name){
		$function = $table.'_by_name';
		return self::guaranteed_call(__CLASS__, $function, [$name]);
	}
	static function static_item_by_id($table, $id){
		$function = $table.'_by_id';
		return self::guaranteed_call(__CLASS__, $function, [$id]);
	}





	public function id_from_object($thing, $id_column='id'){
		return self::id_from_object($thing, $id_column);
	}
	public function id_from_object_or_error($thing, $id_column='id'){
		return self::static_id_from_object_or_error($thing, $id_column);
	}



	# Standard way to resolve variable input of either a id or a name identifier
	# uses `$this->id_by_name`
	public function id_from_string($table, $string){
		if(Tool::isInt($string)){
			return $string;
		}
		$id = $this->id_by_name($table, $string);
		if($id === false){
			throw new Exception('id not fround from '.json_encode(func_get_args()));
		}
		return $id;
	}

	# will use a instance function of `$this->$table.'_id_by_name'` if available
	# otherwise, will use $this->id_by_name_from_db
	public function id_by_name($table, $name){
		$function = $table.'_id_by_name';
		return self::guaranteed_call($this, $function, [$name]);
	}
	/*	param
	options	['id_column':<>, 'table':<>]
	*/
	public function id_by_thing($table, $thing, $options=[]){
		$options = array_merge(['id_column'=>'id'], $options);
		$id = $this->id_from_object_or_error($thing, $options['id_column']);
		$id = $this->id_from_string($table, $id);
		return $id;
	}


	# uses $this->item_by_id or $this->item_by_name
	public function item_by_string($table, $string){
		$item = false;
		if(Tool::isInt($string)){
			$item = $this->item_by_id($table, $string);
		}else{
			$item = $this->item_by_name($table, $string);
		}
		if($item === false){
			throw new Exception('id not fround from '.json_encode(func_get_args()));
		}
		return $item;
	}

	public function item_by_thing($table, $thing){
		if(!is_scalar($thing)){ # the thing is already an item
			return $thing;
		}
		return $this->item_by_string($table, $thing);
	}
	public function item_by_name($table, $name){
		$function = $table.'_by_name';
		return self::guaranteed_call($this, $function, [$name]);
	}
	public function item_by_id($table, $id){
		$function = $table.'_by_id';
		return self::guaranteed_call($this, $function, [$id]);
	}
}


/* testing, setup
#+ db setup {
$_ENV['database']['default'] = array(
		'user'=>'root',
		'password'=>'',
		'database'=>'test',
		'host'=>'localhost',
		'driver'=>'mysql');


$db = Db::singleton($_ENV['database']['default']);
"""
CREATE TABLE `test` (
  `id` bigint(20) NOT NULL,
  `name` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `test`
--

INSERT INTO `test` (`id`, `name`) VALUES
(1, 'test1'),
(2, 'test2');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `test`
--
ALTER TABLE `test`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);
""";

#+ }
*/


/* testing, basic static
class bob{
	use \Grithin\VariedParameter;
	static $db;
	public function init($db){
		self::$db = $db;
	}
	static function get($thing){
		return self::static_id_by_thing('test', $thing);
	}
	static function test_id_by_name($name){
		return self::test_by_name($name)['id'];
	}
	static function test_by_name($name){
		return self::$db->as_row('select * from test where name = ?', [$name]);
	}
}

bob::init($db);

if(bob::get('test2') != 2){
	pp('fail');
}
if(bob::get('5') != 5){
	pp('fail');
}
if(bob::get(['id'=>4]) != 4){
	pp('fail');
}

*/


/* testing, basic instance
class bob{
	use \Grithin\VariedParameter;
	public function __construct($db){
		$this->db = $db;
	}
	public function get($thing){
		return $this->id_by_thing('test', $thing);
	}
	public function test_id_by_name($name){
		return $this->test_by_name($name)['id'];
	}
	public function test_by_name($name){
		return $this->db->as_row('select * from test where name = ?', [$name]);
	}
}

$bob = new bob($db);

if($bob->get('test2') != 2){
	pp('fail');
}
if($bob->get('5') != 5){
	pp('fail');
}
if($bob->get(['id'=>4]) != 4){
	pp('fail');
}
*/