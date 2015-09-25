# Changed Functionality

The following methods had changes in signatures.

## EventManagerInterface::trigger()

In version 2, the signature of `trigger()` was:

```php
trigger($event, $target = null, $argv = [], $callback = null);
```

The method allowed overloading essentially every argument:

- `$event` could be the event name, array or traversable of event names,  or an
  `EventInterface` instance.
- `$target` could be a callable representing the short-circuit callback, an
  `EventInterface` instance, or a value representing the target of the event.
- `$argv` could be a callable representing the short-circuit callback, an
  `EventInterface` instance, or an array/`ArrayAccess`/object instance
  representing the event arguments.
- `$callback` could be either `null` or a callable.

The amount of overloading leads to:

- 4 x 3 x 3 = 36 permutations of arguments, leading to confusion about how to
  call the method.
- Dozens of lines used to validate and marshal arguments.

In version 3, the method has the following signature:

```php
trigger($event, $target = null, $argv = []);
```

with the following defintions:

- `$event` is a string event name.
- `$target` is a value representing the target of the event.
- `$argv` is an array/`ArrayAccess`/object instance representing the event
  arguments.

In other words, each argument has exactly one possible type.

In order to accommodate other styles of usage, we **added** the following
methods:

```php
triggerUntil(callable $callback, $event, $target = null, $argv = []);
triggerEvent(EventInterface $event);
triggerEventUntil(callable $callback, EventInterface $event);
```

These allow the other primary use cases for `trigger()` in v2, but with discrete
signatures.

Starting in version 2.6.0, you can use these three additional methods, as the
`EventManager` instance defines them starting in that version. We recommend
evaluating your code to see which of the four possible call styles you are
using, and that you adapt your code to use one of the 4 discrete methods.

The following signatures, however, are no longer supported, and will need to be
updated as illustrated:

```php
// Event instance as second argument:
$events->trigger('foo', $event);

// Resolve by setting the event name prior to trigger:
$event->setName('foo');
$events->triggerEvent($event);

// Event instance as third argument:
$events->trigger('foo', $this, $event);

// Resolve by setting the event name and target prior to trigger:
$event->setName('foo');
$event->setTarget($this);
$events->triggerEvent($event);
```

If you are using a callback to shortcircuit, use one of the `*Until()` methods,
passing the callback as the first argument:

```php
// Standard trigger:
$events->trigger('foo', $this, ['bar' => 'baz'], $criteria);

// becomes:
$events->triggerUntil($criteria, 'foo', $this, ['bar' => 'baz']);

// Triggering with an event:
$events->trigger($event, $criteria);

// becomes:
$events->triggerEventUntil($criteria, $event);
```
