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

	function test_get(){
		$object = new StdClass;
		$object->found = 123;
		$object->array = ['bob'=>'bob'];
		$arrayObject = new ArrayObject;
		$arrayObject['found'] = 123;
		$arrayObject['array'] = ['bob'=>'bob'];

		$anon_class = new class('Anon'){
			function test(){}
		};

		$array = [
			'object' => $object,
			'arrayObject' => $arrayObject,
			'array' => [
				'test' => 'test'
			],
			'key' => 'key',
			'class'=> $anon_class
		];



		$value = Arrays::get($array, 'object.found');
		$this->assertEquals($array['object']->found, $value, 'object property');

		$value = Arrays::get($array, 'object.array.bob');
		$this->assertEquals($array['object']->array['bob'], $value, 'object -> array property');

		$value = Arrays::get($array, 'object.array.bill');
		$this->assertEquals(null, $value, 'object -> array -> unfound key to new');

		$value = &Arrays::get($array, 'object.array.bill');
		$value = 'bill';
		$value2 = Arrays::get($array, 'object.array.bill');
		$this->assertEquals($value, $value2, 'reference');

		try{
			$value = Arrays::get($array, 'object.array.sue', ['make'=>false]);
			$this->assertEquals(true, false, 'test make=false exception on object -> array -> unfound key');
		}catch(\Exception $e){}

		$value = Arrays::get($array, 'arrayObject.array.bob');
		$this->assertEquals($array['arrayObject']['array']['bob'], $value, 'ArrayObject -> array -> key');

		$value = Arrays::get($array, 'arrayObject.bob');
		$this->assertEquals($array['arrayObject']['bob'], $value, 'ArrayObject -> unfound key');

		$value = &Arrays::get($array, 'arrayObject.bob');
		$value = 'bob';
		$this->assertEquals($array['arrayObject']['bob'], $value, 'ArrayObject -> unfound key reference set');

		try{
			$value = Arrays::get($array, 'arrayObject.bill', ['make'=>false]);
			$this->assertEquals(true, false, 'test make=false exception on arrayObject -> unfound key');
		}catch(\Exception $e){}

		$value = &Arrays::get($array, 'class.test');
		$this->assertEquals([$anon_class, 'test'], $value, 'testing getting class method');
	}

	function test_set(){
		$object = new StdClass;
		$object->found = 123;
		$object->array = ['bob'=>'bob'];
		$arrayObject = new ArrayObject;
		$arrayObject['found'] = 123;
		$arrayObject['array'] = ['bob'=>'bob'];

		$array = [
			'object' => $object,
			'arrayObject' => $arrayObject,
			'array' => [
				'test' => 'test'
			],
			'key' => 'key'
		];

		$value = 'bob123';
		Arrays::set($array, 'arrayObject.bob', $value);
		$this->assertEquals($array['arrayObject']['bob'], $value, 'ArrayObject -> unfound key reference set');
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
