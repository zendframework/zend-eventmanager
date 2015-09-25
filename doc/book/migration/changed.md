# Changed Functionality

The following methods had changes in signatures.

## EventManagerInterface::trigger() and triggerUntil()

In version 2, the signatures of `trigger()` and `triggerUntil()` are:

```php
trigger($event, $target = null, $argv = [], $callback = null);
triggerUntil($event, $target = null, $argv = [], $callback = null);
```

The methods allow overloading essentially every argument:

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

In version 3, we changed the methods to have the following signatures:

```php
trigger($event, $target = null, $argv = []);
triggerUntil(callable $callback, $event, $target = null, $argv = []);
```

with the following defintions:

- `$event` is a string event name.
- `$target` is a value representing the target of the event.
- `$argv` is an array/`ArrayAccess`/object instance representing the event
  arguments.
- `$callback` is a callable to use to introspect listener return values in order
  to determine whether or not to short-circuit.

In other words, each argument has exactly one possible type. `$callback` was
moved to the start of the `triggerUntil()` method as it's *required* for that
usage, and ensures the argument order stays predictable for the remaining
arguments.

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

## EventManagerInterface::attach() and detach()

In version 2, `attach()` and `detach()` had the following signatures:

```php
attach($event, $callback = null, $priority = null);
detach($listener);
```

with the following argument definitions:

- `$event` could be either a string event name, or an instance of
  `ListenerAggregateInterface`.
- `$callback` could be a callable, an instance of `Zend\Stdlib\CallbackHandler`,
  or an integer priority (if `$event` was an aggregate).
- `$priority` could be null or an integer.
- `$listener` could be either a `Zend\Stdlib\CallbackHandler` (as that was how
  listeners were stored internally in that version), or an instance of
  `ListenerAggregateInterface`.

Much like we did for the `trigger*()` methods, we simplified the signatures:

```php
attach($event, callable $listener, $priority = 1);
detach(callable $listener, $event = null);
```

Where:

- `$event` is always a string event name (except when not passed to `detach()`.
- `$listener` is always the `callable` listener.
- `$priority` is always an integer.

`detach()` adds the `$event` argument as the event argument for a couple of
reasons. First, in version 2, the event was composed in the `CallbackHandler`,
which meant it didn't need to be sent separately; since the event managers now
store the listeners directly, you *must* pass the `$event` if you want to detach
from a specific event. This leads to the second reason: by omitting the
argument, you can now remove a listener from *all* events to which it is
attached — a new capability for version 3.

In order to migrate to version 3, you will need to make a few changes to your
application.

First, if you are attaching or detaching aggregate listeners using `attach()`
and `detach()`, you should change them to use `attachAggregate()` and
`detachAggregate()` instead. These methods already exist in version 2, giving
clear forwards compatibility.

Second, if you are manually creating `CallbackHandler` instances to attach to an
event manager, stop doing so, and attach the callable listener itself instead.
This, too, is completely forwards compatible.

If you are passing `CallbackHandler` instances to `detach()`, you will need to
make the following change after updating to version 3:

```php
// This code:
$events->detach($callbackHandler);

// Will become:
$events->detach($callbackHandler->getCallback());
```

In most cases, the callback handler you are storing is likely the result of
calling `attach()` in the first place. Since `attach()` no longer creates a
`CallbackHandler` instance, it instead simply returns the listener back to the
caller. If you were storing this to pass later to `detach()` (such as in a
listener aggregate), you will not need to make any changes when migrating.

## EventManagerInterface::setEventClass() and setEventPrototype()

`setEventClass()` was renamed to `setEventPrototype()` and given a new
signature; see the [setEventClass() removal information](changed.md#eventmanagerinterfaceseteventclass)
for details.

## EventManagerInterface::setIdentifiers() and addIdentifiers()

`EventManagerInterface::setIdentifiers()` and `addIdentifiers()` had a minor
signature change. In version 2, the `$identifiers` argument allowed any of
`string`, `array`, or `Traversable`. In version 3, only arrays are allowed.

Additionally, neither implements a fluent interface any longer; you cannot chain
their calls.
