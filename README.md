# PHP Base Tools

Provides php tools used by other grithin/php* projects.


# Tools

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