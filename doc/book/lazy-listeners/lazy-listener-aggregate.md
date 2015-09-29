# LazyListenerAggregate

`Zend\EventManager\LazyListenerAggregate` exists to facilitate attaching a
number of listeners as lazy listeners.

## Usage

Similar to a [LazyListener](lazy-listener.md) or
[LazyEventListener](lazy-event-listener.md), the `LazyListenerAggregate` accepts
a definition (or, rather, set of definitions) a container-interop instance, and
optionall an `$env` array to its constructor.

Unlike either, however, the definition provided is an array of definitions to
use to create `LazyEventListener` instances; you may also intersperse actual
`LazyEventListener` instances if desired.

As an example, let's assume we have two listeners,
`My\Application\RouteListener` and `My\Application\DispatchListener`; the first
will use its `onRoute()` method to listen to the `route` event at priority 100,
the second its `onDispatch()` method to listen to the `dispatch` event at
priority -100.

```php
use My\Application\DispatchListener;
use My\Application\RouteListener;
use Zend\EventManager\LazyListenerAggregate;

$definitions = [
    [
        'listener' => RouteListener::class,
        'method'   => 'onRoute',
        'event'    => 'route',
        'priority' => 100,
    ],
    [
        'listener' => DispatchListener::class,
        'method'   => 'onDispatch',
        'event'    => 'dispatch',
        'priority' => -100,
    ],
];

$aggregate = new LazyListenerAggregate(
    $definitions,
    $container
);
$aggregate->attach($events);
```

Internally, the `LazyListenerAggregate` will create `LazyEventListener`
instances, and during its `attach()` phase use them to attach to the event
manager using the event and priority they compose.

Below is a functionally identical example, mixing in a concrete
`LazyEventListener` instance for one listener:

```php
use My\Application\DispatchListener;
use My\Application\RouteListener;
use Zend\EventManager\LazyEventListener;
use Zend\EventManager\LazyListenerAggregate;

$dispatchListener = new LazyEventListener([
    'listener' => DispatchListener::class,
    'method'   => 'onDispatch',
    'event'    => 'dispatch',
    'priority' => -100,
], $container);

$definitions = [
    [
        'listener' => RouteListener::class,
        'method'   => 'onRoute',
        'event'    => 'route',
        'priority' => 100,
    ],
    $dispatchListener,
];

$aggregate = new LazyListenerAggregate(
    $definitions,
    $container
);
$aggregate->attach($events);
```

## Recommendations

We recommend using `LazyListenerAggregate` when you have listeners you will be
pulling from a Dependency Injection Container, but which may not execute on
every request; this will help minimize the number of objects pulled from the
DIC. As pulling instances from a DIC is often an expensive operation, this can
be a healthy performance optimization.
