<?
# run with `phpunit VariedParameter.php`

$_ENV['root_folder'] = realpath(dirname(__FILE__).'/../').'/';
require $_ENV['root_folder'] . '/vendor/autoload.php';

use PHPUnit\Framework\TestCase;

use \Grithin\Debug;
use \Grithin\Time;
use \Grithin\Arrays;
use \Grithin\VariedParameter;
use \Grithin\MissingValue;

\Grithin\GlobalFunctions::init();




class TypeMap1 extends \Grithin\TypeMap{
	static $id_column = 'id';
	static $display_name_column = 'name';
	static $system_name_column = 'name';
	static $types_by_id = [
		1 =>	[
			'id' => 1,
			'name' => 'bob',
			'info'=> 'bob1'
		],
		2 =>	[
			'id' => 2,
			'name' => 'bill',
			'info'=> 'bill1'
		],
	];

	static $type_ids_by_name = [
		'bob' => 1,
		'bill' => 2,
	];
}



class TestStatic{
	use \Grithin\VariedParameter;
	static function test_get($thing){
		return self::static_prefixed_item_by_thing('test', $thing);
	}
	static function test_by_id($id){
		return ['id'=>$id, 'name'=>'bob'];
	}
	static function test_by_name($name){
		return ['id'=>123, 'name'=>$name];
	}


	static function get($thing){
		return self::static_item_by_thing($thing);
	}
	static function by_id($id){
		return ['id'=>$id, 'name'=>'bob', 'nonprefixed'=>true];
	}
	static function by_name($name){
		return ['id'=>123, 'name'=>$name, 'nonprefixed'=>true];
	}
}

class TestInstance{
	use \Grithin\VariedParameter;
	public function test_get($thing){
		return $this->prefixed_item_by_thing('test', $thing);
	}
	public function test_by_id($id){
		return ['id'=>$id, 'name'=>'bob'];
	}
	public function test_by_name($name){
		return ['id'=>123, 'name'=>$name];
	}


	public function get($thing){
		return $this->item_by_thing($thing);
	}
	public function by_id($id){
		return ['id'=>$id, 'name'=>'bob', 'nonprefixed'=>true];
	}
	public function by_name($name){
		return ['id'=>123, 'name'=>$name, 'nonprefixed'=>true];
	}
}



class MainTests extends TestCase{
	function test_type_map(){
		$TypeMap = new TypeMap1;
		$type = $TypeMap::get('bob');
		$this->assertEquals($type, $TypeMap::$types_by_id[1], 'failed to get on name');
		$type = $TypeMap::get($type);
		$this->assertEquals($type, $TypeMap::$types_by_id[1], 'failed to get on object');
		$type = $TypeMap::get(1);
		$this->assertEquals($type, $TypeMap::$types_by_id[1], 'failed to get on id');
	}
}