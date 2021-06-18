<?php
namespace Grithin;

/* About
Exception intended to accept an array as error details
*/


class ExceptionMissingMethod extends ComplexException{
	public $details = [];
	public function __construct($name = null, $code = 0, \Exception $previous = null){
		parent::__construct('Missing method "'.$name.'" of '.debug_backtrace(null,2)[1]['class'], $code, $previous);
	}
}
