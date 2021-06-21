<?php
/* About
Included by GlobalFunctions.php
*/

use \Grithin\Debug;
use \Grithin\Tool;

# data as a pretty, identifying string
if(!function_exists('pretty') && in_array('pretty', $functions)){
	function pretty($data){
		return Debug::pretty($data);
	}
}
if(!function_exists('json_pretty') && in_array('json_pretty', $functions)){
	function json_pretty($data){
		return Debug::json_pretty($data);
	}
}




# improves (array) conversion to use __toArray method
if(!function_exists('arr') && in_array('arr', $functions)){
	function arr($data){
		return \Grithin\Arrays::convert($data);
	}
}


# html attributes should also be encoded.  This will work for that, along with text node display
if(!function_exists('text') && in_array('text', $functions)){
	# print escaped value into html content.  Should also work with tag attributes
	function text($data){
		if(!Tool::is_scalar($data)){
			return htmlspecialchars(Tool::flat_json_encode($data));
		}else{
			return htmlspecialchars($data);
		}
	}
}
if(!function_exists('text_pretty') && in_array('text_pretty', $functions)){
	# print escaped value into html content.  Intended to be used with "pre"
	function text_pretty($data){
		if(!Tool::is_scalar($data)){
			return htmlspecialchars(\Grithin\Debug::json_pretty($data));
		}else{
			return htmlspecialchars($data);
		}
	}
}

if(!function_exists('encode') && in_array('encode', $functions)){
	function encode($data){
		return Tool::flat_json_encode($data);
	}
}
#++ }
if(!function_exists('file_extension') && in_array('pretty', $functions)){
	function file_extension($file){
		return array_pop(explode('.', $file));;
	}
}
