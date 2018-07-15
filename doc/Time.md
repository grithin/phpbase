# Timezone
```php
# convert time zone
(new Time('now', 'EDT'))->setZone('PDT');

# get current timezone name
(new Time)->getTimezone()->getName();

# adjust time
(new Time)->modify('+1 hour');

# new instance from adjusted time
$x = (new Time);
(clone $x)->modify('+2 hour');
# or
$x = (new Time);
$x->relative('+2 hour');
```

