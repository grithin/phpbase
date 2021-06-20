<?php
namespace Bootstrap;


$_ENV['root_folder'] = realpath(dirname(__FILE__).'/../../').'/';
require $_ENV['root_folder'] . '/vendor/autoload.php';

use \Grithin\Debug;

\Grithin\GlobalFunctions::init();

Trait Test{
	public $class;
	public function assert_equal_standard($expect, $input, $method, $message=''){
		$input_as_string = Debug::json_pretty($input);
		$message .= "\tMethod: $method\t\ninput: $input_as_string";
		$output = call_user_func_array([$this->class, $method], $input);
		$this->assertEquals($expect, $output, $message);
	}
}