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

function pp($data){
	$caller = \Grithin\Debug::caller();
	\Grithin\Debug::out($data, $caller);

}

function ppe($data){
	$caller = \Grithin\Debug::caller();
	\Grithin\Debug::quit($data, $caller);
}

#++ }


}