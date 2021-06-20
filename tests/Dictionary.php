<?php
use \PHPUnit\Framework\TestCase;

use \Grithin\Debug;
use \Grithin\Time;
use \Grithin\Arrays;
use \Grithin\Dictionary;


/**
* @group Dictionary
*/
class DictionaryClassTests extends TestCase{
	use \Bootstrap\Test;

	function __construct(){
		$this->class = \Grithin\Dictionary::class;
	}


	function test_merge_deep(){
		$expect = [
				'one' => 3,
				'two' => 2
		];
		$input = [
				['one'=>1, 'two'=>2],
				['one'=>3, 'two'=>2]
			];
		$this->assert_equal_standard($expect, $input, 'merge_deep', 'test straigt dictionary merge');


		$expect = [3,2];
		$input = [
				[1,2],
				[3]
			];
		$this->assert_equal_standard($expect, $input, 'merge_deep', 'test straigt list merge');


		$expect = [
			'item' => [
				'one' => 3,
				'two' => 2,
				'three' => 3,
				'four' => 4
			]
		];
		$input = [
				['item'=>['one'=>1, 'two'=>2, 'three'=>3]],
				['item'=>['one'=>3, 'two'=>2, 'four'=>4]]
			];
		$this->assert_equal_standard($expect, $input, 'merge_deep', 'test sub dictionary merge');

		$expect = ['item'=>[4,5]];
		$input = [
				['item'=>[1,2,3]],
				['item'=>[4,5]]
			];
		$this->assert_equal_standard($expect, $input, 'merge_deep', 'test sub list merge');

	}

}