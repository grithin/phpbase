<?
# run with `phpunit Memoized.php`

$_ENV['root_folder'] = realpath(dirname(__FILE__).'/../').'/';
require $_ENV['root_folder'] . '/vendor/autoload.php';

use PHPUnit\Framework\TestCase;

use \Grithin\Debug;
use \Grithin\Time;
use \Grithin\Arrays;
use \Grithin\VariedParameter;
use \Grithin\MissingValue;


\Grithin\GlobalFunctions::init();


class StaticTest{
	use \Grithin\Memoized;
	static function test($x){
		if(self::static_caller_requested_memoized()){
			return self::memoized_bottom($x);
		}else{
			return self::bottom($x);
		}
	}
	static function test2($x){
		if(self::static_caller_requested_memoized()){
			return self::memoized_bottom($x);
		}else{
			return self::memoize_bottom($x);
		}
	}

	static function bottom($x){
		return $x.'1 '.microtime();
	}
}


class InstanceTest{
	use \Grithin\Memoized;
	public function test($x){
		if($this->caller_requested_memoized()){
			return $this->memoized_bottom($x);
		}else{
			return $this->bottom($x);
		}
	}
	public function test2($x){
		if($this->caller_requested_memoized()){
			return $this->memoized_bottom($x);
		}else{
			return $this->memoize_bottom($x);
		}
	}

	public function bottom($x){
		return $x.'1 '.microtime();
	}
}




class MainTests extends TestCase{
	function test_static_methods(){
		$x1 = StaticTest::memoized_test('bobs');
		$x2 = StaticTest::memoized_test('bobs');
		$this->assertEquals($x1, $x2, 'memoized faliure');
		$x3 = StaticTest::memoize_test('bobs');
		$this->assertEquals(false, $x1 == $x3, 'memoize remake faliure');
		$x4 = StaticTest::memoized_test('bobs');
		$this->assertEquals($x3, $x4, 'memoized faliure');
	}
	function test_instance_methods(){
		$test_instance = new InstanceTest;
		$x1 = $test_instance->memoized_test('bobs');
		$x2 = $test_instance->memoized_test('bobs');
		$this->assertEquals($x1, $x2, 'memoized faliure');
		$x3 = $test_instance->memoize_test('bobs');
		$this->assertEquals(false, $x1 == $x3, 'memoize remake faliure');
		$x4 = $test_instance->memoized_test('bobs');
		$this->assertEquals($x3, $x4, 'memoized faliure');

		$x5 = $test_instance->memoized__unset('test', ['bobs']);
		$this->assertEquals(false, $x4 == $x5, 'memoized faliure');

		$test_instance->memoized__set('test', ['bobs'], 'test');
		$x6 = $test_instance->memoized_test('bobs');
		$this->assertEquals('test', $x6, 'memoized faliure');
	}
}