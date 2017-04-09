<?
/* About
Autoloader trick to load global convenience functions
*/
namespace Grithin{

class GlobalFunctions{
	static function init(){}
}

}

namespace{

#++ useful debugging output functions {
use \Grithin\Debug;

if(!function_exists('pp')){
	function pp($data){
		$caller = \Grithin\Debug::caller();
		\Grithin\Debug::out($data, $caller);
	}
}
if(!function_exists('ppe')){
	function ppe($data=''){
		$caller = \Grithin\Debug::caller();
		\Grithin\Debug::quit($data, $caller);
	}
}
if(!function_exists('pretty')){
	function pretty($data){
		return \Grithin\Debug::pretty($data);
	}
}
if(!function_exists('text')){
	function text($data){
		return htmlspecialchars($data);
	}
}
if(!function_exists('encode')){
	function encode($data){
		return \Grithin\Tool::flat_json_encode($data);
	}
}
#++ }


}
