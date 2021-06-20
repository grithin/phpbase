<?php
namespace Grithin\Traits;
use Grithin\Debug;
use Grithin\Traits\SingletonDefault;
use Grithin\Traits\OverClass;


trait OverClassSingleton{
	use SingletonDefault, OverClass {
		OverClass::__call insteadof SingletonDefault;	}
}