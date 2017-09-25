Intended to be a observable that matches a database record, allowing the handling of a record like an array, while allowing listeners to react to change events.
-	EVENT_CHANGE_BEFORE
-	EVENT_CHANGE_AFTER | EVENT_CHANGE
-	EVENT_UPDATE_BEFORE
-	EVENT_UPDATE_AFTER | EVENT_UPDATE



Update and Change events will fire if there were changes, otherwise they won't.

Listener can mutate record upon a `update, before` event prior to the setter being called.  Listener can also throw an exception at this point, but the exception is not handled inside this class

See Grithin\phpdb\StandardRecord for example use

Similar to SplSubject, but because SplSubject uses pointless SplObserver, SplSubject is not imlemented

@important:	don't use unset().  It is not detected as a change.  Consequently, if you want to unset a child of a non-scalar, re-set the entire toplevel value. Ex:
	-	$record['json'] = ['bob'=>'sue', 'phil'=>'jones'];
	-	$record['json'] = ['bob'=>'sue']
	instead of
	-	unset($record['json']['phil'])

# Record States
There is a `$this->record` and a `$this->stored_record`.  Event handlers will receive the diff between the potential new `$this->record` and the old `$this->record`.  The setter function, however, will receive the diff between the new `$this->record` and the existing `$this->stored_record`.


# Getter/Setter
The getter function is used on construction and upon the use of `refresh`.  given parameter `($this)`.  It should return an array structure representing the record
The setter function is used to update the database/file record, given parameters `($this, $diff)`.  It should return the new stored record array.


# Events
Convenience functions `before_change`, `after_change`, `before_update`, `after_update` will call the parameter function on the corresponding event with parameters `($this, $details)`, where in `$details` is an array object of the change.

