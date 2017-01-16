<?
namespace Grithin;

class Number{
	static $alphanumeric_set = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	function from_10_to_base($dividee, $base=62) {
		do{
			$remainder = $dividee % $base;
			$dividee =floor($dividee/$base);
			$res = self::$alphanumeric_set[$remainder].$res;
		}while($dividee);
		return $res;
	}
	function from_base_to_10($multiplee, $base=62) {
		$digits = strlen($multiplee);
		$res = strpos(self::$alphanumeric_set,$multiplee[0]);
		for($i=1; $i<$digits; $i++){
			# this works since it effectively applies `b^d` where b=base, d=digit, to each digit, ending upon b^0.  Ex: the number at the fourth digit undergoes `b^3 * n`
			$res = $base * $res + strpos(self::$alphanumeric_set, $multiplee[$i]);
		}
		return $res;
	}
}