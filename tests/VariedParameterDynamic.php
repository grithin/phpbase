<?php
# run with `phpunit VariedParameterDynamic.php`

$_ENV['root_folder'] = realpath(dirname(__FILE__).'/../').'/';
require $_ENV['root_folder'] . '/vendor/autoload.php';

use PHPUnit\Framework\TestCase;

use \Grithin\Debug;
use \Grithin\Time;
use \Grithin\Arrays;
use \Grithin\Traits\VariedParameterDynamic;
use \Grithin\MissingValue;


\Grithin\GlobalFunctions::init();


class TestStatic{
	use \Grithin\Traits\VariedParameterDynamic;
	static function test_get($thing){
		return self::prefixed_item_by_thing('test', $thing);
	}
	static function test_by_id($id){
		return ['id'=>$id, 'name'=>'bob'];
	}
	static function test_by_name($name){
		return ['id'=>123, 'name'=>$name];
	}


	static function get($thing){
		return self::item_by_thing($thing);
	}
	static function by_id($id){
		return ['id'=>$id, 'name'=>'bob', 'nonprefixed'=>true];
	}
	static function by_name($name){
		return ['id'=>123, 'name'=>$name, 'nonprefixed'=>true];
	}
}

class TestInstance{
	use \Grithin\Traits\VariedParameterDynamic;
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
	function test_static_methods(){
		$this->assertEquals(TestStatic::test_get(333), ['id'=>333, 'name'=>'bob'], 'static prefixed get by id wrong');
		$this->assertEquals(TestStatic::test_get('sue'), ['id'=>123, 'name'=>'sue'], 'static prefixed get by name wrong');
		$this->assertEquals(TestStatic::test_get(['id'=>888, 'name'=>'bob']), ['id'=>888, 'name'=>'bob'], 'static prefixed get by object wrong');
		$this->assertEquals(TestStatic::get(333), ['id'=>333, 'name'=>'bob', 'nonprefixed'=>true], 'static get by id wrong');
		$this->assertEquals(TestStatic::get('sue'), ['id'=>123, 'name'=>'sue', 'nonprefixed'=>true], 'static prefixed get by name wrong');
		$this->assertEquals(TestStatic::get(['id'=>888, 'name'=>'bob']), ['id'=>888, 'name'=>'bob'], 'static get by object wrong');
	}
	function test_instance_methods(){
		$test_instance = new TestInstance;
		$this->assertEquals($test_instance->test_get(333), ['id'=>333, 'name'=>'bob'], 'instance prefixed get by id wrong');
		$this->assertEquals($test_instance->test_get('sue'), ['id'=>123, 'name'=>'sue'], 'instance prefixed get by name wrong');
		$this->assertEquals($test_instance->test_get(['id'=>888, 'name'=>'bob']), ['id'=>888, 'name'=>'bob'], 'instance prefixed get by object wrong');
		$this->assertEquals($test_instance->get(333), ['id'=>333, 'name'=>'bob', 'nonprefixed'=>true], 'instance get by id wrong');
		$this->assertEquals($test_instance->get('sue'), ['id'=>123, 'name'=>'sue', 'nonprefixed'=>true], 'instance prefixed get by name wrong');
		$this->assertEquals($test_instance->get(['id'=>888, 'name'=>'bob']), ['id'=>888, 'name'=>'bob'], 'instance get by object wrong');
	}
}