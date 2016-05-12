# EventManager API

This section details the public API of the `EventManager`, `SharedEventManager`,
`EventInterface`, and `ResponseCollection`.

## EventManager

### Constructor

```php
public function __construct(
    SharedEventManagerInterface $sharedEvents = null,
    array $identifiers = []
)
```

The `EventManager` instance accepts a shared event manager instance and
identifiers to use with the shared event manager.

### setEventPrototype()

```php
public function setEventPrototype(EventInterface $event) : void
```

Use this method in order to provide an event prototype. The event prototype is
used with the `trigger()` and `triggerUntil()` methods to create a new event
instance; the prototype is cloned and populated with the event name, target, and
arguments passed to the method.

### getSharedManager()

```php
public function getSharedManager() : null|SharedEventManagerInterface
```

Use this method to retrieve the composed shared event manager instance, if any.

### getIdentifiers()

```php
public function getIdentifiers() : array
```

Use this method to retrieve the current list of identifiers the event manager
defines. Identifiers are used when retrieving listeners from the composed shared
event manager.

### setIdentifiers() and addIdentifiers()

```php
public function setIdentifiers(array $identifiers) : void
public function addIdentifiers(array $identifiers) : void
```

Use these methods to manipulate the list of identifiers the event manager
is interested in. `setIdentifiers()` will overwrite any identifiers previously
set, while `addIdentifiers()` will merge them.

### trigger()

```php
trigger($eventName, $target = null, $argv = []) : ResponseCollection
```

where:

- `$eventName` is a string event name.
- `$target` is the target of the event; usually the object composing the event
  manager instance.
- `$argv` is an array or `ArrayAccess` instance of arguments that provide
  context for the event. Typically these will be the arguments passed to the
  function in which the trigger call occurs.

The target and/or arguments may be omitted, but the event name is required.

When done triggering, the method returns a `ResponseCollection`.

### triggerUntil()

```php
triggerUntil(callable $callback, $eventName, $target = null, $argv = []) : ResponseCollection
```

`triggerUntil()` is a sibling to `trigger()`, and prefixes the argument list
with a single `$callback`.

The `$callback` is invoked after each listener completes, with the result of
that listener. The `$callback` should inspect the result, and determine if it
should result in short-circuiting the event loop. Returning a boolean `true`
value indicates that the criteria has been met and the event loop should end.

As an example:

```php
$events->attach('foo', function ($e) {
    echo "Triggered first\n";
    return true;
});
$events->attach('foo', function ($e) {
    echo "Triggered second\n";
    return false;
});
$events->attach('foo', function ($e) {
    echo "Triggered third\n";
    return true;
});

$events->triggerUntil(function ($result) {
    return (false === $result);
}, 'foo');
```

In the above example, the event loop will short-circuit after the second
listener executes, resulting in the following output:

```text
Triggered first
Triggered second
```

### triggerEvent()

```php
triggerEvent(EventInterfce $event) : ResponseCollection
```

This method is a sibling to `trigger()`, but unlike `trigger()`, it accepts an
`EventInterface` instance as its sole argument. It is up to the caller to ensure
the event is properly populated.

This method behaves identically to `trigger()`, returning a `ResponseCollection`
after all listeners have been triggered.

### triggerEventUntil()

```php
triggerEventUntil(callable $callback, EventInterface $event) : ResponseCollection
```

This method is a sibling to `triggerEvent()` and `triggerUntil()`. Like
`triggerUntil()`, the first argument is a PHP callable to invoke for each
response, and is used to determine whether or not to short-circuit execution.
Like `triggerEvent()`, the next argument is an `EventInterface` instance.

### attach()

```php
attach($eventName, callable $listener, $priority = 1) : callable
```

Use `attach()` to attach a callable listener to a named event. `$priority` can
be used to indicate where in the listener queue the event should be executed.
Priorities **must** be integers. High positive integers indicate higher priority
(will execute first), while low, negative integers indicate lower priority (will
execute last). The default priority is 1, and listeners registered with the same
priority will execute in the order in which they attach to the event manager.

The method returns the listener attached.

### detach()

```php
detach(callable $listener, $eventName = null) : void
```

Use `detach()` to remove a listener. When a named `$eventName` is provided, the
method will detach the listener from that event only (or, if the event does not
exist in the event manager, nothing will occur). If no event is provided, or the
wildcard event is provided, the listener will be detached from all events.

### clearListeners()

```php
clearListeners($eventName) : void
```

Use this method to remove all listeners for a given named event.

### prepareArgs()

```php
prepareArgs(array $args) : ArrayObject
```

Normally when working with an event, if you want to change any arguments in the
event, you would need to do the following:

```php
$args = $e->getParams();

// Manipulate args:
$args['foo'] = 'bar';

// Pass them back in:
$e->setParams($args);
```

If the arguments you provide are an *object*, however, you can manipulate them
directly:

```php
$args = $e->getParams();

// Manipulate args:
$args->foo = 'bar';

// Done!
```

Using an object, however, makes accessing individual parameters difficult:

```php
$foo = $e->getParam('foo'); // How should the event know how to get this?
```

As such, we recommend passing either an array or an `ArrayObject` instance for
event arguments. If you pass the latter, you get the benefit of being able to
mainpulate by reference.

`prepareArgs()` can thus be used to return an `ArrayObject` representation of
your aguments to pass to `trigger()` or `triggerUntil()`:

```php
$events->attach('foo', $this, $events->prepareArgs(compact('bar', 'baz')));
```

## SharedEventManager

### attach()

```php
attach($identifier, $eventName, callable $listener, $priority = 1) : void
```

Attach a listener to a named event triggered by an identified context, where:

- `$identifier` is a string identifier that may be defined by an `EventManager`
  instance; `$identifier` may be the wildcard `*`.
- `$eventName` is a string event name (or the wildcard `*`).
- `$listener` is a PHP callable that will listen for an event.
- `$priority` is the priority to use when attaching the listener.

### detach()

```php
detach(callable $listener, $identifer = null, $eventName = null) : void
```

Detach a listener, optionally from a single identifier, and optionally from a
named event, where:

- `$listener` is the PHP callable listener to detach.
- `$identifier` is a string identifier from which to detach.
- `$eventName` is a string event name from which to detach.

If no or a null `$identifier` is provided, the listener will be detached from all
identified contexts. If no or a null `$eventName` is provided, the listener will be
detached from all named events discovered.

### getListeners()

```php
getListeners(array $identifiers, $eventName = null) : array[]
```

Retrieves all registered listeners for a given identifier and named event; if
the event name is omitted, it returns all listeners for the identifier.

Each value in the array returned is in the form:

```php
[
    'listener' => callable,
    'priority' => int,
]
```

Implementations should return wildcard listeners in this array.

This method is used by the `EventManager` in order to get a set of listeners for
the event being triggered.

### clearListeners()

```php
clearListeners($id, $eventName = null) : bool
```

This event will clear all listeners for a given identifier, or, if specified,
the specific event for the named identifier.

## EventInterface

In most cases, you will use `Zend\EventManager\Event`, but some components will
define custom events. The `EventInterface` thus defines the common methods
across any event implementation.

### getName()

```php
getName() : string
```

Returns the event name.

### getTarget()

```php
getTarget() : null|string|object
```

Returns the event target, if any.

### getParams()

```php
getParams() : array|ArrayAccess
```

Returns the event parameters, if any.

### getParam()

```php
getParam($name, $default = null) : mixed
```

Returns a single named parameter, returning the `$default` if not found.

### setName()

```php
setName($name) : void
```

Sets the event name.

### setTarget()

```php
setTarget($target) : void
```

Sets the event target. `$target` may be a string or object.

### setParams()

```php
setParams($parms) : void
```

Set the event parameters; `$params` should be an array or object implementing
`ArrayAccess`.

### setParam()

```php
setParam($name, $value) : void
```

Set a single named event parameter value.

### stopPropagation()

```php
stopPropagation($flag = true) : void
```

Indicate whether or not event propagation should halt (short-circuit). This
value is what will be returned by `propagationIsStopped()`.

### propagationIsStopped()

```php
propagationIsStopped() : bool
```

Used by the event manager to determine if the event has indicated that the event
loop should short-circuit.

## ResponseCollection

A `ResponseCollection` instance is returned by each of `trigger()`,
`triggerUntil()`, `triggerEvent()`, and `triggerEventUntil()`, and represents
the various results of listener execution.

The `ResponseCollection` is iterable, and iteration will return the various
responses in the order in which they were provided. In addition, it has the API
listed below.

### stopped()

```php
stopped() : bool
```

Use this to determine if something caused the event loop to short-circuit.

### first()

```php
first() : mixed
```

Returns the result from the first listener executed.

### last()

```php
last() : mixed
```

Returns the result from the last listener executed.

### contains()

```php
contains($value) : bool
```

Query the response collection to determine if a specific value was returned by
any listener.
