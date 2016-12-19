# Quick Start

Typically, you will compose an `EventManager` instance in a class.

```php
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;

class Foo implements EventManagerAwareInterface
{
    protected $events;

    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers([
            __CLASS__,
            get_called_class(),
        ]);
        $this->events = $events;
        return $this;
    }

    public function getEventManager()
    {
        if (null === $this->events) {
            $this->setEventManager(new EventManager());
        }
        return $this->events;
    }
}
```

The above allows users to access the `EventManager` instance, or reset it with a
new instance; if one does not exist, it will be lazily instantiated on-demand.

The instance property `$events` is a convention for referring to the
EventManager instance.

An `EventManager` is really only interesting if it triggers some events.

Basic triggering via the `trigger()` method takes three arguments:

- The event *name*, which is usually the current function/method name;
- The *target*, which is usually the current object instance;
- Event *arguments*, which are usually the arguments provided to the current function/method.

```php
class Foo
{
    // ... assume events definition from above

    public function bar($baz, $bat = null)
    {
        $params = compact('baz', 'bat');
        $this->getEventManager()->trigger(__FUNCTION__, $this, $params);
    }
}
```

In turn, triggering events is only interesting if something is listening for the
event.

Listeners attach to the `EventManager`, specifying a named event and the
callback to notify. The callback receives an `Event` object, which has accessors
for retrieving the event name, target, and parameters. Let's add a listener, and
trigger the event.

```php
use Zend\Log\Factory as LogFactory;

$log = LogFactory($someConfig);
$foo = new Foo();
$foo->getEventManager()->attach('bar', function ($e) use ($log) {
    $event  = $e->getName();
    $target = get_class($e->getTarget());
    $params = json_encode($e->getParams());

    $log->info(sprintf(
        '%s called on %s, using params %s',
        $event,
        $target,
        $params
    ));
});

// The following method call:
$foo->bar('baz', 'bat');

// Results in the log message reading:
// bar called on Foo, using params {"baz" : "baz", "bat" : "bat"}"
```

Note that the second argument to `attach()` is any valid PHP callable; an
anonymous function is shown in the example in order to keep the example
self-contained.

However, you could also utilize a valid function name, a functor, a string
referencing a static method, or an array callback with a named static method or
instance method. Again, any PHP callable is valid.

Sometimes you may want to specify listeners without yet having an object
instance of the class composing an `EventManager`. Zend Framework enables this
through the concept of a `SharedEventManager`.

Simply put, you can inject individual `EventManager` instances with a well-known
`SharedEventManager`, and the `EventManager` instance will query it for
additional listeners.

Listeners attach to a `SharedEventManager` in roughly the same way they do to
normal event managers; the call to `attach` is identical to the `EventManager`,
but expects an additional parameter at the beginning: a named instance.

Remember the example of composing an `EventManager`, how we passed it an array
containing `__CLASS__` and `get_class($this)`? Those values are then used to
*identify* the event manager instance, and pull listeners registered with one of
those identifiers from the `SharedEventManager`.

As an example, assuming we have a `SharedEventManager` instance that we know has
been injected in our `EventManager` instances (for instance, via dependency
injection), we could change the above example to attach via the shared
collection:

```php
use Zend\Log\Factory as LogFactory;

// Assume $sharedEvents is a Zend\EventManager\SharedEventManager instance

$log = LogFactory($someConfig);
$sharedEvents->attach('Foo', 'bar', function ($e) use ($log) {
    $event  = $e->getName();
    $target = get_class($e->getTarget());
    $params = json_encode($e->getParams());

    $log->info(sprintf(
        '%s called on %s, using params %s',
        $event,
        $target,
        $params
    ));
});

// Later, instantiate Foo:
$foo = new Foo();
$foo->setEventManager(new EventManager($sharedEvents, []));

// And we can still trigger the above event:
$foo->bar('baz', 'bat');
// results in log message:
// bar called on Foo, using params {"baz" : "baz", "bat" : "bat"}"
```

The `EventManager` also provides the ability to detach listeners, short-circuit
execution of an event either from within a listener or by testing return values
of listeners, test and loop through the results returned by listeners,
prioritize listeners, and more. Many of these features are detailed in the
examples.
