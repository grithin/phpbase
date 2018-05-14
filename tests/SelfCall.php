<?
# run with `phpunit SelfCall.php`

$_ENV['root_folder'] = realpath(dirname(__FILE__).'/../').'/';
require $_ENV['root_folder'] . '/vendor/autoload.php';

use PHPUnit\Framework\TestCase;

use \Grithin\Debug;
use \Grithin\Time;
use \Grithin\Arrays;
use \Grithin\Tool;


\Grithin\GlobalFunctions::init();


class ParentClass{
	use \Grithin\SelfCall;
	function prefer_self_method(){
		return self::self_self($this, 'expected_return');
	}
	function prefer_static_method(){
		return self::self_static($this, 'expected_return');
	}

	function expected_return(){
		if($this){
			return __CLASS__.' this';
		}else{
			return __CLASS__.' static';
		}
	}
}
class ChildClass extends ParentClass{
	function expected_return(){
		if($this){
			return __CLASS__.' this';
		}else{
			return __CLASS__.' static';
		}
	}
}



class MainTests extends TestCase{
	function test_self_static(){
		$test = new ChildClass;
		$this->assertEquals('ChildClass this', $test->prefer_static_method(), 'self_static failed on `this`');
		$this->assertEquals('ChildClass static', ChildClass::prefer_static_method(), 'self_static failed on `self`');
	}
	function test_parent_static(){
		$test = new ChildClass;
		$this->assertEquals('ChildClass this', $test->prefer_self_method(), 'self_static failed on `this`');
		$this->assertEquals('ParentClass static', ChildClass::prefer_self_method(), 'self_static failed on `self`');
	}
}