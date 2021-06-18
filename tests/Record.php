<?php
# run with `phpunit Record.php`

$_ENV['root_folder'] = realpath(dirname(__FILE__).'/../').'/';
require $_ENV['root_folder'] . '/vendor/autoload.php';

use PHPUnit\Framework\TestCase;

use \Grithin\Debug;
use \Grithin\Time;
use \Grithin\Arrays;
use \Grithin\Record;
use \Grithin\MissingValue;


\Grithin\GlobalFunctions::init();

global $accumulation;
$accumulation = [];
function make_accumulator($accumulate){
	return function() use ($accumulate) {
		global $accumulation;
		$accumulation[] = $accumulate;
	};
}
function args_accumulator(){
	$args = func_get_args();
	global $accumulation;
	$accumulation[] = $args;
}
function accumulate_array_changes($this, $changes){
	global $accumulation;
	$accumulation[] = (array)$changes;
}

class MainTests extends TestCase{
	function __construct(){
		$this->underlying = [];
		$this->events = [];
	}
	function getter($record){
		return $this->underlying;
	}
	function setter($record, $diff){
		$this->underlying = $record->record;
		$this->setter_sequence[] = (array)$diff;
		return $this->underlying;
	}
	function reset(){
		$this->underlying = [];
		$this->reset_sequences();
	}
	function reset_sequences(){
		$this->setter_sequence = [];
		global $accumulation;
		$accumulation = [];
	}
	function assertEqualsCopy($x, $y, $error_message='not equal'){
		if($x != $y){
			pp('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!error');
			ppe([$x,$y]);
			throw new Exception($error_message);
		}
	}

	function test_events_sequence(){
		$this->reset_sequences();
		$record = new Record(null, [$this,'getter'], [$this,'setter']);
		$record->before_change(make_accumulator('bc'));
		$record->after_change(make_accumulator('ac'));
		$record->before_update(make_accumulator('bu'));
		$record->after_update(make_accumulator('au'));

		$record['sue'] = 'bill';

		global $accumulation;
		$this->assertEquals($accumulation, ['bc', 'ac'], 'change sequence incorrect');

		$record->apply();
		$this->assertEquals($accumulation, ['bc', 'ac', 'bu', 'au'], 'update sequence incorrect');
	}

	function test_events_no_change(){
		$this->reset_sequences();
		$record = new Record(null, [$this,'getter'], [$this,'setter']);
		$record['sue'] = 'bill';
		$record->before_change(make_accumulator('bc'));
		$record->after_change(make_accumulator('ac'));
		$record['sue'] = 'bill';


		global $accumulation;
		$record->apply();
		$this->assertEquals($accumulation, [], 'no change sequence incorrect');
	}
	function test_events_removal(){
		$this->reset_sequences();
		$record = new Record(null, [$this,'getter'], [$this,'setter']);
		$fn = $record->before_change(make_accumulator('bc'));
		$record['sue'] = 'bill1';
		$record->observers->detach($fn);
		$record['sue'] = 'bill2';
		global $accumulation;
		$this->assertEquals($accumulation, ['bc'], 'event removal failed');
	}

	function test_events_parameters(){
		$record = new Record(null, [$this,'getter'], [$this,'setter']);
		$record->before_change('accumulate_array_changes');
		$record->after_change('accumulate_array_changes');
		$record->before_update('accumulate_array_changes');
		$record->after_update('accumulate_array_changes');

		# test local record change works
		$record['sue'] = 'bill';
		$changes = ['sue'=>'bill'];

		global $accumulation;
		$this->assertEquals($accumulation, [$changes, $changes], 'changes on events incorrect');

		# test stored record change works
		$record->apply();
		$this->assertEquals($accumulation, [$changes, $changes, $changes, $changes], 'changes on events incorrect');

		$this->reset_sequences();
		# test that unset works
		unset($record['sue']);

		$this->assertEquals($accumulation[0], ['sue'=> new MissingValue], 'unset on Record failed to diff');
	}

	function test_replace(){
		$this->reset_sequences();
		$record = new Record(null, [$this,'getter'], [$this,'setter']);
		$record->before_change('accumulate_array_changes');
		$record['sue'] = 'bill';
		global $accumulation;
		# test replace with no stored record
		$record->replace(['moe'=>'dan']);
		$this->assertEquals($accumulation[1], (['sue'=>new MissingValue, 'moe'=>'dan']), 'update 1 evenut diff incorrect');
		$this->assertEquals($this->setter_sequence[0], (['moe'=>'dan']), 'update 1 setter diff incorrect');
		$this->reset_sequences();
		# test replace with stored record
		$record->replace(['joe'=>'susan']);
		$this->assertEquals($accumulation[0], (['moe'=>new MissingValue, 'joe'=>'susan']), 'update 2 incorrect');
		$this->assertEquals($this->setter_sequence[0], ( (['moe'=>new MissingValue, 'joe'=>'susan'])), 'update 1 setter diff incorrect');
	}
	function test_subrecord(){
		$this->reset_sequences();
		$record = new Record(null, [$this,'getter'], [$this,'setter']);
		$record['bob'] = ['monkeys'=>'bill', 'sl'=>'ss'];
		$record->before_change('accumulate_array_changes');
		global $accumulation;

		# test unset works on subrecord
		unset($record['bob']['sl']);
		$this->assertEquals($accumulation[0], ( (['bob'=>['sl'=>new MissingValue]])), 'unset 1 failed');
		# test appropriate stored record update
		$record->apply();
		$this->assertEquals($this->setter_sequence[0], (['bob'=>['monkeys'=>'bill']]), 'update 1 setter diff incorrect');

		# test unset for stored record update
		$this->reset_sequences();
		unset($record['bob']['monkeys']);
		$record->apply();
		$this->assertEquals($this->setter_sequence[0], (['bob'=>['monkeys'=>new MissingValue]]), 'stored record unset update incorrect');

		# test normal set update
		$this->reset_sequences();
		$record['bob']['phil'] = '111';
		$this->assertEquals($accumulation[0], ( ['bob'=>['phil'=>'111']]), 'normal set change event incorrect');
		$record->apply();
		$this->assertEquals($this->setter_sequence[0], (['bob'=>['phil'=>'111']]), 'normal set setter diff incorrect');

		# test subrecord replace
		$this->reset_sequences();
		$record['bob'] = ['123'];
		$this->assertEquals($accumulation[0], ( ['bob'=>['phil'=>new MissingValue, 0=>'123']]), 'replace change event diff incorrect');
		$record->apply();
		$this->assertEquals($this->setter_sequence[0], (['bob'=>['phil'=>new MissingValue, 0=>'123']]), 'replace setter diff incorrect');

		# test appending
		$this->reset_sequences();
		$record['bob'][] = '456';
		$this->assertEquals($accumulation[0], ( ['bob'=>[1=>'456']]), 'replace change event diff incorrect');
		$record->apply();
		$this->assertEquals($this->setter_sequence[0], (['bob'=>[1=>'456']]), 'update 1 setter diff incorrect');
		$this->assertEquals($record->record, (['bob'=>['123', '456']]), 'record does not match expected');
	}


}