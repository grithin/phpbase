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
	# pretty print
	function pp($data){
		$caller = Debug::caller();
		Debug::out($data, $caller);
	}
}
if(!function_exists('ppe')){
	# pretty print and exit
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
if(!function_exists('json_pretty')){
	function json_pretty($data){
		return Debug::json_pretty($data);
	}
}
if(!function_exists('stderr')){
	function stderr($data){
		return Debug::stderr($data);
	}
}

if(!function_exists('sepp')){
	# standard error pretty print
	function sepp($data){
		$output = "\n".Debug::pretty($data, Debug::caller());
		Debug::stderr($output);
	}
}



# improves (array) conversion to use __toArray method
if(!function_exists('arr')){
	function arr($data){
		return \Grithin\Arrays::to_array($data);
	}
}


# html attributes should also be encoded.  This will work for that, along with text node display
if(!function_exists('text')){
	# print escaped value into html content.  Should also work with tag attributes
	function text($data){
		if(!Tool::is_scalar($data)){
			return htmlspecialchars(Tool::flat_json_encode($data));
		}else{
			return htmlspecialchars($data);
		}
	}
}
if(!function_exists('text_pretty')){
	# print escaped value into html content.  Intended to be used with "pre"
	function text_pretty($data){
		if(!Tool::is_scalar($data)){
			return htmlspecialchars(\Grithin\Debug::json_pretty($data));
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
if(!function_exists('file_extension')){ # because functions exist for other parts (`basename`)
	function file_extension($file){
		return array_pop(explode('.', $file));;
	}
}


}
