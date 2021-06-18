<?php
namespace Grithin;
/*
For Class Instance for which it is expected there is only one, and so, it can exist as a keyed value in an array
*/
/* About.md
# Inter Code Dependencies - 201904
A database interface class, `DBI`, connecting to a single database, might be used as a global, without passing it into functions that use it.  The problem occurs in modularization and testing.  For instance, in testing, it may be desired that some section of code uses a different database, requiring another `DBI` apart from the main one.

Normally, this can be resolved by passing in the `DBI`.  But, there are scenarios in which this is impractical:
1.	rarely used environmental variables, like a `Logger` class.  Does it make sense to pass in a `Logger` instance to a class that only exceptionally uses it?  This highlights that some things, like inherent language functions, and globally available, and don't need to be passed in.  Can user defined functions be ubiquitous enough to merit the same unpassed use?
2.	in the selection of code from the `Route` class, there is the execution of some `control` file.  But, prior to this there is the loading of a `DBI`.  Does it make sense to pass the `DBI` through the `Route` class so that is available to the `control` file?  The `Route` class, other than passing on the `DBI`, has no interest in the `DBI`.  And, adding in the requirement for passing on these used instances like `DBI` does not provide an advantage over having `DBI` globally available to `control`.

But, let's say we want to test some class `X` that rarely uses `Logger`, and for that test, we don't want to have the `Logger` log to the main log.  This can be accomplished by having layered globals, and encapsulating the `X` test within a testing global layer that redefines `Logger` to log to some test log.
And, if a test of the Route to control system were desired, this same sort of layered globals could be used.

A potential problem might occur when some code within a layered global section calls another function that requires use of an upper layer global.  Apart from the rarity of this situation, it can be resolved by passing in the global, like `DBI`, with such functons that require an out of layer sequence global.

Going even further, for resources that are sufficiently between unexpectable user defined functions and expectable language defined functions, it is useful to accept the resource as an optional parameter (like the `DBI`), but also, in case that parameter is not presented, to fall back on whatever the layered global for it is.


## Compatible Resources
Code may accept some resource, which isn't necessarily some specific class instance, so long as that resource matches some interface.  While theoretically it might be useful to access layered global reources based on a request for some global matching an interface criteria, it is impractical for some reasons:
1.	named globals within a framework are expectable - there is no need to so a search for a global matching some `Db` interface when the global is known to exist with the reference name `Db`
2.	there may be multiple matching interfaces.  For instance, with `DBI`, multiple DBIs may have been instantiated, and requiring based on interface alone might result in picking the wrong one.
3.	interfaces might match, but the actual resource content is unexpected.  This is a result of the limitation of interface definition - which isn't a problem in the normal case, wherein the interface is checked upon a passed argument, wherein divergence from ambiguity is unlikely

Consequently, I'm content to use simple standard keys for layer global resources.

*/


class GlobalInstance{
	static $instance_default_construction = [];
	static $instances = [];
	static $instances_stack = [];
	static function set($name, $instance){
		if(self::$instances[$name]){
			if(self::$instances_stack[$name]){
				self::$instances_stack[$name] = [self::$instances[$name]];
			}else{
				self::$instances_stack[$name][] = self::$instances[$name];
			}
		}
		self::$instances[$name] = $instance;
	}
	static function get($name){
		if(!self::$instances[$name]){
			self::set($name, self::construct_from_name($name));
		}
		return self::$instances[$name];
	}
	# pop the current instance, replacing it with the last item in the `$instances_stack[$name]` stack, and return the previous current
	static function pop($name){
		$previous_current = self::$instances[$name];
		if(self::$instances_stack[$name]){
			self::$instances[$name] = array_pop(self::$instances_stack[$name]);
		}
		return $previous_current;
	}
	/* about.md
	get the first instance of set name
	-	if it exists in the stack, return first stack item
	-	if it doesn't exist in the stack, but exists in the instances, return instance
	-	if it doesn't exist, try to create it
	*/
	static function first($name){
		if(self::$instances_stack[$name]){
			return self::$instances_stack[$name][0];
		}else{
			return self::get($name);
		}
	}
	/* About
	if class is a conforming input to ReflectionClass, use ReflectionClass
	otherwise, just call as though it is some function reference call_user_func_array will accept
	*/
	/* params
	< class > < string, object, or callable >
	< params > < params to pass to callable >
	*/
	static function construct($class, $params){
		if(is_string($class) || is_object($class)){
			$reflection_class = new \ReflectionClass($class);
			return $reflection_class->newInstanceArgs($params);
		}else{
			call_user_func_array($class, $params);
		}

	}
	static function construct_from_name($name){
		if(self::$instance_default_construction[$name]){
			$construct = self::$instance_default_construction[$name];
			return self::construct($construct['class'], $construct['parameters']);
		}else{
			throw new \Exception('Missing construction default for "'.$name.'" of global instances');
		}

	}
	# set a default construction set up for some key to be used if a `get` is called without an existing key
	# see `construct`
	static function set_default($name, $class, $construction_parameters){
		self::$instance_default_construction[$name] = ['class'=>$class, 'parameters'=>$construction_parameters];
	}
}