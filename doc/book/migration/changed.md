# Changed Functionality

The following methods had changes in signatures.

## EventManager::__construct()

In version 2, the signature of `__construct()` was:

```php
__construct($identifiers = null)
```

where `$identifiers` could be a string, array of strings, or `Traversable` of
strings.

Version 3 requires that the shared event manager be injected at instantiation,
instead of via a setter. This also enforces the idea that identifiers have no
semantic meaning without a shared event manager composed. As such, the
constructor now has two arguments, with the first being the shared event
manager:

```php
__construct(SharedEventManagerInterface $sharedEvents, array $identifiers = [])
```

Finally, because we changed the signature of `setIdentifiers()` and
`addIdentifiers()` to only accept arrays (see more below), we changed the
`$identifiers` argument to only allow arrays.

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
and `detach()`, you should change such calls to instead pass the event manager
to the relevant `ListenerAggregateInterface` method, as detailed in the
[removed functionality](removed.md#eventmanagerinterfaceattachaggregate-and-detachaggregate)
documentation. These methods have existed in all released versions, giving
perfect forwards compatibility.

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

## SharedEventManagerInterface::getListeners()

`Zend\EventManager\SharedEventManagerInterface::getListeners()` has changed. The
previous signature was:

```php
getListeners($id, $event = null): false|Zend\Stdlib\PriorityQueue
```

Version 3 has the following signature:

```php
getListeners(array $identifiers, $eventName) : array
```

The changes are:

- The first argument now expects an *array* of identifiers. This is so an event
  manager instance can retrieve shared listeners for all identifiers it defines
  at once.
- The second argument is now *required*. Since the event manager always knows
  the event at the time it calls the method, it makes sense to require the
  argument for all calls. It also reduces complexity in the implementation.
- The method now *always* returns an array. The array will be of the structure
  `[ 'priority' => callable[] ]`.

## SharedEventManagerInterface::attach()

The v2 signature of `attach()` was:

```php
attach($id, $event, $callback, $priority = 1) : CallbackHandler|CallbackHandler[]
```

where:

- `$id` could be a string identifier, or an array or `Traversable` of
  identifiers.
- `$event` was a string event name.
- `$callback` could be either a `callable` listener, or a `CallbackHandler`
  instance.
- `$priority` was an integer.

The v3 signature becomes:

```php
attach($identifier, $eventName, callable $listener, $priority = 1) : void
```

where:

- `$identifier` *must* be a string *only*.
- `$eventName` must be a string name.
- `$listener` must be a callable *only*.
- `$priority` is an integer.

Migration concerns are thus:

- If you are passing arrays of identifiers to which to attach, you must now do
  so in a loop or using a construct such as `array_walk`:

  ```php
  foreach ($identifiers as $id) {
      $sharedEvents->attach($id, $event, $listener);
  }

  array_walk($identifiers, function ($id) use ($listener) {
      $this->sharedEvents->attach($id, 'foo', $listener);
  });
  ```

- If you are passing `CallbackHandler` arguments, pass the callable listener
  instead.

- If you were relying on being returned the `CallbackHandler`, you may now
  simply cache the `$listener` argument.

## SharedEventManagerInterface::detach()

The v2 signature of `detach()` was:

```php
detach($id, CallbackHandler $listener) : bool
```

where:

- `$id` was a string identifier
- `$listener` was a `CallbackHandler` instance
- the method returned a boolean indicating whether or not it removed anything.

The v3 signature becomes:

```php
detach(callable $listener, $identifier = null, $eventName = null) : void
```

where:

- `$listener` is the callable listener you wish to remove
- `$identifier`, if provided, is a specific identifier from which you want to remove the
  `$listener`.
- `$eventName`, if provided, is a specific event on the specified `$id` from
  which to remove the `$listener`
- the method no longer returns a value.

When not specifying an identifier, the method contract indicates it should
remove the listener from any identifier; similarly, in the absence of an event
argument, it should remove the listener from any event on the identifier(s).
This allows for mass removal!

As the signatures differ, you will need to update any code calling `detach()`
after upgrading to v3. At the minimum, you will need to swap the `$identifier` and
`$listener` arguments, and pass the callable listener instead of a
`CallbackHandler` instance. We also recommend auditing your code to determine if
you want to be more or less specific when detaching the listener.

## ListenerAggregateInterface::attach()

`Zend\EventManager\ListenerAggregateInterface::attach()` was updated to add an
optional argument, `$priority = 1`. This codifies how the `EventManager` was
already implemented.

Since PHP allows adding optional arguments to concrete implementations of
abstract methods, you can forward-proof your existing
`ListenerAggregateInterface` implementations by adding the argument.

As an example, if you define your method like this:

```php
public function attach(EventManagerInterface $events)
```

Simply change it to this:

```php
public function attach(EventManagerInterface $events, $priority = 1)
```

You do not need to do anything with the `$priority` argument, though we
recommend passing it as a default value if you are not specifying a priority for
any listeners you attach.

## FilterInterface::attach() and detach()

`Zend\EventManager\Filter\FilterInterface::attach()` and `detach()` have changed
signatures. The originals were:

```php
attach($callback) : CallbackHandler
detach(CallbackHandler $callback) : bool
```

where `$callback` for `attach()` could be a callable or a `CallbackHandler`. The
new signatures are:

```php
attach(callable $callback) : void
detach(callable $filter) : bool
```

Typical usage in v2 was to capture the return value of `attach()` and pass it to
`detach()`, as `attach()` would create a `CallbackHandler` for you to later pass
to `detach()`. Since we can now pass the original callable argument to
`detach()` now, you can cache that value instead.

## FilterIterator

`Zend\EventManager\Filter\FilterIterator` now defines/overrides the `insert()`
method in order to validate the incoming value and ensure it is callable,
raising an exception when it is not. This simplifies logic in `FilterChain`, as
it no longer needs to check if a filter is callable at runtime.

The main migration change at this time is to know that an
`InvalidArgumentException` will now be thrown when adding filters to a filter
chain, vs at runtime.

## ResponseCollection::setStopped()

`Zend\EventManager\ResponseCollection::setStopped()` no longer implements a
fluent interface.
