<?
namespace Grithin;

/* About
Exception intended to accept an array as error details
*/

class ComplexException extends \Exception{
	public $details = [];
	public function __construct($message = null, $code = 0, \Exception $previous = null){
		if(!is_scalar($message)){
			$this->details = (array)$message;
		}else{
			$this->details = ['message'=>$message];
		}

		parent::__construct(\Grithin\Tool::flat_json_encode(array_merge(['message'=>''], $this->details /*for ordering purposes*/) ), $code, $previous);
	}
	public function getDetails(){
		return $this->details;
	}
}