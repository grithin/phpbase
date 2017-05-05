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
use \Grithin\Tool;

if(!function_exists('pp')){
	function pp($data){
		$caller = Debug::caller();
		Debug::out($data, $caller);
	}
}
if(!function_exists('ppe')){
	function ppe($data=''){
		$caller = Debug::caller();
		Debug::quit($data, $caller);
	}
}
if(!function_exists('pretty')){
	function pretty($data){
		return Debug::pretty($data);
	}
}
# html attributes should also be encoded.  This will work for that, along with text node display
if(!function_exists('text')){
	function text($data){
		if(!Tool::is_scalar($data)){
			return htmlspecialchars(Tool::flat_json_encode($data));
		}else{
			return htmlspecialchars($data);
		}
	}
}
if(!function_exists('encode')){
	function encode($data){
		return Tool::flat_json_encode($data);
	}
}
#++ }


}
