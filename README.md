# PHP Base Tools

Provides php tools used by other grithin/php* projects.


# Tools

## Arrays
```php
# get arbitrarily deep item using a path (like lodash.get)
$user_input = [
	'name'=> [
		'first'=>'bob',
		'last'=>'bobl',
	]
];

$x = Arrays::get($user_input, 'name.first');
#> 'bob'


#+ flatten array picking one value {
$user_input = [
	'name'=> [
		'first'=>'bob',
		'last'=>'bobl',
	]
];

$x = Arrays::flatten_values($user_input);
ppe($x);
#>  {"name": "bob"}
#+ }



#+ flatten structured array {
$user_input = [
	'name'=> [
		'first'=>'bob',
		'last'=>'bobl',
	]
];

$x = Arrays::flatten($user_input);
ppe($x);
/*
{"name_first": "bob",
    "name_last": "bobl"}
*/
#+ }



#+ pick and ensure
$user_input = [
	'first_name'=>'bob',
	'last_name'=>'bobl',
	'unwanted'=>'blah'
];
$pick = ['first_name', 'last_name', 'middle_name'];

$x = Arrays::pick_default($user_input, $pick, 'N/A');
/*
{"first_name": "bob",
    "last_name": "bobl",
    "middle_name": "N\/A"}
*/
#+ }




#+ rekey and exclude {

$user_input = [
	'first_name'=>'bob',
	'last_name'=>'bobl',
	'unwanted'=>'blah'
];
$map = ['first_name'=>'FirstName', 'last_name'=>'LastName'];

$x = Arrays::map_only($user_input, $map);
ppe($x);
/*
{"FirstName": "bob",
    "LastName": "bobl"}
*/
#+ }

```


## Files
The primary functions are file inclusion functions `inc`, `req`, `incOnce`, `reqOnce`.  They allow inclusion tracking, context isolation, variable injection, variable extraction, and global injection.

Example file `bob.php`
```php
$bill = [$bob]
$bill[] = 'monkey'
return 'blue'
```

Use
```php
Files::inc('bob.php')
#< 'blue'

# Using variable injection and variable extraction
Files::inc('bob.php',['bob'=>'sue'], ['extract'=>['bill']])
#< ['sue', 'monkey']
```




## Bench
Simple benching utility

```php
$bench = new \Grithin\Bench();
for($i=0; $i<10000; $i++){
	mt_rand(0,100000) + mt_rand(0,100000);
}
$bench->mark();
for($i=0; $i<10000; $i++){
	mt_rand(0,100000) + mt_rand(0,100000);
}
$bench->end_out();
```

```
...
   "value": {
        "intervals": [
            {
                "time": 0.0035231113433838,
                "mem.change": 808
            },
            {
                "time": 0.0028860569000244,
                "mem.change": 776
            }
        ],
        "summary": {
            "mem": {
                "start": 403168,
                "end": 404752,
                "diff": 1584
            },
            "time": 0.0064091682434082
        }
    }
...
```


## Memoized

The Memoized trait adds magic methods that interpret the method call, and if the call is prefixed with either `memoize_` or `memoized_`, Memoized will handle memoizing the method.

```php
class Test{
	use \Grithin\Traits\Memoized;

	public function random(){
		return microtime()
	}
}
$Test = new Test;
$x = $Test->memoized_random();
$y = $Test->memoized_random();
$x == $y; # > true

```

If a memoized function needs to be re-memoized, you can prefix it with `memoize_`
```php
# ...
$x = $Test->memoized_random();
$y = $Test->memoize_random();
$x == $y; # > false
```

Memoized will make a key out of the function parameters, so if the function parameters are different, they will generate different memoize cache - and it does this independently of which parameters are used.

```php
# ...
$x = $Test->memoized_random(1);
$y = $Test->memoized_random(2);
$x == $y; # > false
```

Memoized can be used with static methods

```php
class Test{
	use \Grithin\Traits\Memoized;

	static function random(){
		return microtime();
	}
}
$x = Test::memoized_random();
$y = Test::memoized_random();
$x == $y; # > true
```


In some cases, it is desirable to know whether a method is currently being memoized.  Let's say we have a `user_get` function that returns data about a user, and within that function there is another `location_get` function, that returns data about location linked to the user.  Let's say there are two scenarios where we ant output from user_get:
-	code that needs the most up-to-date  `user_get` data
-	code that can expect `user_get` calls to be the same data during its run

Memoized provides a way to check whether the current function is within a memoize chain.  For instance level memoizing `$this->memoizing()`; for static memoizing, the `Memoized::static_memoizing()`.

We could have a `user_get` that looks like

```php
class User{
	use \Grithin\Traits\Memoized;
	public user_get($id){
		$user_data = Db::get('...');
		if($this->memoizing()){ # function has been called with a memoize_ or memoized_ prefix
			$location = Location::memoized_get($user_data['location']);
		}else{
			$location = Location::get($user_data['location']);
		}
	}
}
```


Finally, a function can check if it was directly called to be memoized by using `->caller_requested_memoized()` and `::static_caller_requested_memoized()`.




## Debug
Handling errors and print output


### Set up for error handling
```php
# optionally configure
Debug::configure([
	'log_file'=>__DIR__.'/log/'.date('Ymd').'.log',
	'err_file'=>__DIR__.'/log/'.date('Ymd').'.err',	]);

set_error_handler(['\Grithin\Debug','handleError'], E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE);
set_exception_handler(['\Grithin\Debug','handleException']);
```

### Output

Printing out any variable
```php
$bob = ['bob'];
$sue = ['sue'];
$bob[] = &$sue;
$sue[] = &$bob;
$sue[] = 'moe';

Debug::out($sue);

echo 'bob';
```
Outputs:
```
{
    "file": "\/test.php",
    "line": 37,
    "i": 1,
    "value": []
}bob
```
If run within a web server, it will enclose with `<pre>`

`Debug::quit();` will do `::out()` then `exit`.




