<?php

use PHPUnit\Framework\TestCase;

use \Grithin\Debug;
use \Grithin\Time;
use \Grithin\Arrays;

/**
* @group Arrays
*/
class ArraysClassTest extends TestCase{
	use \Bootstrap\Test;
	function __construct(){
		$this->class = \Grithin\Arrays::class;
	}

	function test_set_new_or_expand(){
		$expect = ['bob'=>['bill'=>['sue'=>[123, 456]]]];
		$collection = ['bob'=>['bill'=>['sue'=>123]]];
		Arrays::set_new_or_expand($collection, 'bob.bill.sue', 456);
		$this->assertEquals($expect, $collection, 'expansion');


		$expect = ['bob'=>['bill'=>['sue'=>123]]];
		$collection = ['bob'=>['bill'=>[]]];
		Arrays::set_new_or_expand($collection, 'bob.bill.sue', 123);
		$this->assertEquals($expect, $collection, 'new');

		$expect = ['bob'=>['bill'=>['sue'=>123]]];
		$collection = ['bob'=>[]];
		Arrays::set_new_or_expand($collection, 'bob.bill.sue', 123);
		$this->assertEquals($expect, $collection, 'deep new');
	}

}