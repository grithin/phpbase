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




### Logging

You can configure the log and err file paths, or allow them to be automatically determined.  The names will be `log` and `err`, and the folder will be determined upon:
-	if `$_ENV['root_path']`
	-	if	`$_ENV['root_path'].'.log/'`, use
	-	else use `$_ENV['root_path']`
-	else
	-	if `dirname($_SERVER['SCRIPT_NAME'])`, use
	-	else use `dirname($_SERVER['SCRIPT_NAME'])`

Errors are automatically logged.  You can separately log information using:
```php
Debug::log('BOB');
```

By default, this will be JSON pretty printed. You can turn that off with
```php
Debug::configure(['pretty'=>false]);
```

You can also determine what gets logged.  The second parameter is used in a regex max against the configured mode
```php
Debug::configure(['mode'=>'error debug']);

Debug::log('BOB','error');#< will log
Debug::log('BOB1','error|debug');#< will log
Debug::log('BOB2','info'); #< will not log
```




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
