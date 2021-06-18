<?php
namespace Grithin;
use Grithin\Debug;
use Grithin\SingletonDefault;
use Grithin\OverClass;


trait OverClassSingleton{
	use SingletonDefault, OverClass {
		OverClass::__call insteadof SingletonDefault;	}
}