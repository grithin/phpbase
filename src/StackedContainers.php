<?
namespace Grithin;

use \Grithin\Singleton;

/* Example: Basic stack

$builder = new \DI\ContainerBuilder();
$builder->writeProxiesToFile(true, 'tmp/proxies');
$builder->useAutowiring(false);

$container = $builder->build();
$container->set('bar', function(){ echo 'fuuuu'; return new StdClass; });
$container->set('bar2', function(){ echo 'fuuuu2'; return new StdClass; });


$stacked = new StackedContainers;
$contained = function($stacked){
	$builder = new \DI\ContainerBuilder();
	$builder->writeProxiesToFile(true, 'tmp/proxies2');
	$builder->useAutowiring(false);

	$container = $builder->build();
	$container->set('bar', function(){ echo 'feeee'; return new StdClass; });

	$stacked->wrap($container, function($stacked){
		$stacked->get('bar');
		$stacked->get('bar2');
	});
	$stacked->get('bar');
	$stacked->get('bar2');
};

$stacked->wrap($container, $contained);

#> feeeefuuuu2fuuuu


*/



/* Example: use within code that does not accept a $stacked parameter

function stacked($name=null){
	if($name === null){
		return \Grithin\StackedContainers::singleton();
	}
	return \Grithin\StackedContainers::singleton()->get($name);
}


$builder = new \DI\ContainerBuilder();
$builder->writeProxiesToFile(true, 'tmp/proxies');
$builder->useAutowiring(false);

$container = $builder->build();
$container->set('bar', function(){ echo 'fuuuu'; return new StdClass; });
$container->set('bar2', function(){ echo 'fuuuu2'; return new StdClass; });

$contained = function(){
	$builder = new \DI\ContainerBuilder();
	$builder->writeProxiesToFile(true, 'tmp/proxies2');
	$builder->useAutowiring(false);

	$container = $builder->build();
	$container->set('bar', function(){ echo 'feeee'; return new StdClass; });

	stacked()->wrap($container, function(){
		stacked('bar');
		stacked('bar2');
	});
	stacked('bar');
	stacked('bar2');
};

stacked()->wrap($container, $contained);

#> feeeefuuuu2fuuuu
*/



class StackedContainers{
	use Singleton;

	public $stack = [];
	function wrap($container, $callable){
		$this->stack[] = $container;
		$callable($this);
		array_pop($this->stack);
	}
	function at($name){
		$current = count($this->stack) - 1;
		for($i = $current; $i >= 0; $i--){
			if($this->stack[$i]->has($name)){
				return $i;
			}
		}
		return false;
	}
	function at_container($name){
		$i = $this->at($name);
		if($i !== false){
			return $this->stack[$i];
		}
		return false;
	}
	function has_current_container(){
		return count($this->stack) > 0;
	}
	function current_container(){
		$current = count($this->stack) - 1;
		if($current < 0){
			throw new \Exception('No container in stack');
		}
		return $this->stack[$current];
	}

	function get($name){
		if($container = $this->at_container($name)){
			return $container->get($name);
		}
		return $this->current_container()->get($name);
	}
	function has($name){
		return $this->at($name) === false ? false : true;
	}
	function make($name){
		if($container = $this->at_container($name)){
			return $container->make($name);
		}
		return $this->current_container()->make($name);
	}
}
