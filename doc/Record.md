See `tests\Record.php`

# Overview
Intended to be an observable that matches a database record, allowing the handling of a record like an array, keeping track of changes, handling deep data structures, and allowing listeners to react to change events.

Similar to SplSubject, but because SplSubject uses pointless SplObserver, SplSubject is not imlemented

# Events, Observers
Types:
-	EVENT_CHANGE_BEFORE
-	EVENT_CHANGE_AFTER | EVENT_CHANGE
-	EVENT_UPDATE_BEFORE
-	EVENT_UPDATE_AFTER | EVENT_UPDATE
-	EVENT_REFRESH

Convenience functions `before_change`, `after_change`, `before_update`, `after_update` will call the parameter function on the corresponding event with parameters `($this, $details)`, where in `$details` is an array object of the change.

Update and Change events will fire if there were changes, otherwise they won't.  That is, if an assignment results in the same value, it will not fire an event.

The diff parameter presented to event observers is an ArrayObject, and that object is used when applying the diff to the record.  Consequently, mutating the diff within a "before" event observer will affect the resulting record.  If the diff count becomes 0, no change will be applied.

Potential uses of observers:
-	mutate a particular column (timestamp to datetime format)
-	check validity of record state.  Throw an exception if a bad state, or clear the diff
-	create a composite column.  Ex: total_cost = cost1 + cost2
-	logging specific changes

## Technical
Observers are stored in $this->observers SplObjectStorage object.  All observers are called for every event.  The convenience functions, such as `before_change` wrap the provided function with function that filters for specific events.
The $this->observers is a SplObjectStorage so that observers can be removed using function references:
```php
$observer = $record->before_change(function($record, $diff){ echo 'bob'; });
$record->observers->detach($observer);
```

# Record States
There is a `$this->record` and a `$this->stored_record`.  Event handlers will receive the diff between the potential new `$this->record` and the old `$this->record`.  The setter function, however, will receive the diff between the new `$this->record` and the existing `$this->stored_record`.


# Getter/Setter
The getter function is used on construction and upon the use of `refresh`.  given parameter `($this)`.  It should return an array structure representing the record
The setter function is used to update the database/file record, given parameters `($this, $diff)`.  It should return the new stored record array.
