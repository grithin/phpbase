<?php

use PHPUnit\Framework\TestCase;

use \Grithin\Debug;
use \Grithin\Time;
use \Grithin\Arrays;
use \Grithin\Traits\VariedParameter;
use \Grithin\MissingValue;



class TestCombo{
	use \Grithin\Traits\Memoized;
	static function static_test($x){
		return $x.microtime();
	}
	function instance_test($x){
		return $x.microtime();
	}
	static function static_test_sub($x){
		return self::static_sub($x);
	}
	static function static_test_sub_memoize($x){
		return self::memoized_static_sub($x);
	}
	static function static_sub($x){
		return [self::static_memoizing(), self::static_caller_requested_memoized(), self::static_test($x)];
	}
	function instance_test_sub($x){
		return $this->instance_sub($x);
	}
	function instance_test_sub_memoize($x){
		return $this->memoized_instance_sub($x);
	}
	function instance_sub($x){
		return [$this->memoizing(), $this->caller_requested_memoized(), $this->instance_test($x)];
	}
}



/**
* @group Memoized
*/
class MemoizedClassTests extends TestCase{
	function test_combo(){
		$Test = new TestCombo;
		$x = TestCombo::memoized_static_test(1);
		$y = TestCombo::memoized_static_test(1);
		$this->assertEquals($x, $y, 'static memoized');

		$x = TestCombo::memoized_static_test(1);
		$y = TestCombo::memoize_static_test(1);
		$this->assertFalse($x == $y, 'static memoize');

		$x = $Test->memoized_instance_test(1);
		$y = $Test->memoized_instance_test(1);
		$this->assertEquals($x, $y, 'instance memoized');

		$x = $Test->memoized_instance_test(1);
		$y = $Test->memoize_instance_test(1);
		$this->assertFalse($x == $y, 'instance memoize');

		$x = TestCombo::memoized_static_test_sub(1);
		$this->assertTrue($x[0], 'memoizing static call');
		$this->assertFalse($x[1], 'memoizing caller_requested_memoize');
		$y = TestCombo::memoized_static_test_sub(1);
		$this->assertEquals($x, $y, 'instance memoized');

		$x = TestCombo::memoized_static_test_sub_memoize(1);
		$this->assertTrue($x[0], 'memoizing static call 2');
		$this->assertTrue($x[1], 'memoizing caller_requested_memoize 2');
		$y = TestCombo::memoized_static_test_sub_memoize(1);
		$this->assertEquals($x, $y, 'instance memoized');

		$x = TestCombo::static_test_sub(1);
		$this->assertFalse($x[0], 'non-memoizing static call');
		$this->assertFalse($x[1], 'non-memoizing caller_requested_memoize');

		$x = $Test->memoized_instance_test_sub(1);
		$this->assertTrue($x[0], 'memoizing instance call');
		$this->assertFalse($x[1], 'memoizing caller_requested_memoize');
		$y = $Test->memoized_instance_test_sub(1);
		$this->assertEquals($x, $y, 'instance memoized');

		$x = $Test->memoized_instance_test_sub_memoize(1);
		$this->assertTrue($x[0], 'memoizing instance call 2');
		$this->assertTrue($x[1], 'memoizing caller_requested_memoize 2');
		$y = $Test->memoized_instance_test_sub_memoize(1);
		$this->assertEquals($x, $y, 'instance memoized');

		$x = $Test->instance_test_sub(1);
		$this->assertFalse($x[0], 'non-memoizing instance call');
		$this->assertFalse($x[1], 'non-memoizing caller_requested_memoize');
	}
}