<?
namespace Grithin;

class Number{
	static $alphanumeric_set = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

	function from_10_to_base($dividee, $base=62, $set=null) {
		if(!$set){
			$set = self::$alphanumeric_set;
		}
		do{
			$remainder = $dividee % $base;
			$dividee =floor($dividee/$base);
			$res = $set[$remainder].$res;
		}while($dividee);
		return $res;
	}
	function from_base_to_10($multiplee, $base=62, $set=null) {
		if(!$set){
			$set = self::$alphanumeric_set;
		}
		$digits = mb_strlen($multiplee);
		$res = strpos($set,$multiplee[0]);
		for($i=1; $i<$digits; $i++){
			# this works since it effectively applies `b^d` where b=base, d=digit, to each digit, ending upon b^0.  Ex: the number at the fourth digit undergoes `b^3 * n`
			$res = $base * $res + strpos($set, $multiplee[$i]);
		}
		return $res;
	}
}