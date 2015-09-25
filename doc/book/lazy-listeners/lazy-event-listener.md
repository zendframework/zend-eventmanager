# LazyEventListener

`Zend\EventManager\LazyEventListener` extends from
[LazyListener](lazy-listener.md), but **requires** supplying the event name to
which to attach, and optionally the priority, in the definition supplied at
construction. This allows it to be a standalone instance that a listener
aggregate can then query and use to attach to an event manager instance.

## Usage

As noted in the introduction, the `LazyEventListener` is aware of two additional
keys in the definition supplied at instantiation:

- *event* is the name of the event to which the lazy listener should attach.
- *priority* can optionally be provided to indicate the priority at which the
  lazy listener should attach.

As an example, let's assume:

- We have a listener registered in our container with the service name
  `My\Application\Listener`, and
- we want to use the method `onDispatch` when listening; further,
- we want to attach it to the event `dispatch`,
- at priority 100.

Additionally, we'll assume that we have a container-interop instance in the
variable `$container` and an event manager in the variable `$events`.

You could create the lazy event listener as follows:

```php
use My\Application\Listener;
use Zend\EventManager\LazyEventListener;

$listener = new LazyEventListener([
    'listener' => Listener::class,
    'method'   => 'onDispatch',
    'event'    => 'dispatch',
    'priority' => 100,
], $container);
```

## Methods

`LazyEventListener` exposes two methods:

- `getEvent()` returns the event name used.
- `getPriority($default = 1)` returns either the priority passed at
  instantiation, or, if none was provided, the default passed when invoking the
  method.

## Aggregates

The `LazyEventListener` features are primarily geared towards registering lazy
listeners in aggregates. To that end, you will rarely instantiate or interact
with them directly; instead, you'll leave that to the
[LazyListenerAggregate](lazy-listener-aggregate.md).
