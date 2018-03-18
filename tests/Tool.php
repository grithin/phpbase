<?
# run with `phpunit Tool.php`

$_ENV['root_folder'] = realpath(dirname(__FILE__).'/../').'/';
require $_ENV['root_folder'] . '/vendor/autoload.php';

use PHPUnit\Framework\TestCase;

use \Grithin\Debug;
use \Grithin\Time;
use \Grithin\Arrays;
use \Grithin\Tool;


\Grithin\GlobalFunctions::init();



class MainTests extends TestCase{
	function test_cli_parse_args(){
		$args = ['/bob/bob.php', '-t', 'bill', '-abc', 'bob', '--test', 'monkey', '--test2=monkey2'];
		$expected_result = [
			0 => '/bob/bob.php',
			't' => 'bill',
			'a' => true,
			'b' => true,
			'c' => 'bob',
			'test' => 'monkey',
			'test2' => 'monkey2'
		];
		$this->assertEquals($expected_result, Tool::cli_parse_args($args), 'parse cli wrong');
	}

}