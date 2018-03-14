# PHP Base Tools

Provides php tools used by other grithin/php* projects.


# Tools

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








## Files
The primary functions are file inclusion functions `inc`, `req`, `incOnce`, `reqOnce`.  They allow inclusion tracking, context isolation, variable injection, variable extraction, and global injection.

Example file `bob.php`
```php
<?
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


## Arrays
```php
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