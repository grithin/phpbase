<?php
/* About
Autoloader trick to load global convenience functions
*/
namespace Grithin{

class GlobalFunctions{
	static $silence = false; # whether to silence output functions
	/** params
	< additional_functions > < functions to include in addition to the defaults >
	*/
	/** examples
	\Grithin\GlobalFunctions::init('pretty');
	\Grithin\GlobalFunctions::init(['pretty', 'json_pretty']);
	*/
	/** used to force inclusion of this file, which then sets global functions */
	static function init($additional_functions=[]){
		if($additional_functions){
			\Grithin\Files::req(__DIR__.'/GlobalFunctionsAdditional.php', ['functions'=>(array)$additional_functions]);
		}
	}
}

}

namespace{

#++ useful debugging output functions {
use \Grithin\Debug;
use \Grithin\Tool;

if(!function_exists('pp')){
	/** pretty print */
	function pp($data){
		if(\Grithin\GlobalFunctions::$silence){
			return;
		}
		$data = func_num_args() == 1 ? $data : func_get_args();
		$caller = Debug::caller();
		Debug::out($data, $caller);
	}
}
if(!function_exists('ppe')){
	/** pretty print and exit */
	function ppe($data=''){
		if(\Grithin\GlobalFunctions::$silence){
			return;
		}
		$data = func_num_args() == 1 ? $data : func_get_args();
		$caller = Debug::caller();
		echo ob_get_clean();
		Debug::quit($data, $caller);
	}
}
if(!function_exists('stderr')){
	function stderr($data){
		return Debug::stderr($data);
	}
}

if(!function_exists('sepp')){
	/** standard error pretty print */
	function sepp($data){
		if(\Grithin\GlobalFunctions::$silence){
			return;
		}
		$output = "\n".Debug::pretty($data, Debug::caller());
		Debug::stderr($output);
	}
}


}
