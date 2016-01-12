# Listener Aggregates

*Listener aggregates* exist to facilitate two operations:

- Attaching many listeners at once.
- Attaching stateful listeners.

A listener aggregate is a class implementing
`Zend\EventManager\ListenerAggregateInterface`, which defines two methods:

```php
attach(EventManagerInterface $events, $priority = 1);
detach(EventManagerInterface $events);
```

To attach an aggregate to an event manager, you pass the event manager to the
aggregate's `attach()` method; in that method, you will then attach listeners to
the events you are interested in.

## Implementation

To implement `ListenerAggregateInterface`, you need to define the `attach()` and
`detach()` methods. A typical implementation will look something like this:

```php
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

class Aggregate implements ListenerAggregateInterface
{
    private $listeners = [];

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach('something', [$this, 'onSomething'], $priority);
        $this->listeners[] = $events->attach('else', [$this, 'onElse'], $priority);
        $this->listeners[] = $events->attach('again', [$this, 'onAgain'], $priority);
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $listener) {
            $events->detach($listener);
            unset($this->listeners[$index]);
        }
    }

    public function onSomething(EventInterface $event)
    {
        // handle event
    }

    public function onElse(EventInterface $event)
    {
        // handle event
    }

    public function onAgain(EventInterface $event)
    {
        // handle event
    }
}
```

Because the logic for detaching is essentially the same in all implementations,
we provide two facilities for implementing this:

- `Zend\EventManager\AbstractListenerAggregate` is an abstract class that
  defines the `$listeners` property and the `detach()` method. You may *extend*
  it in order to create an implementation.
- `Zend\EventManager\ListenerAggregateTrait` is a trait that defines the
  `$listeners` property and the `detach()` method. You may *implement*
  `Zend\EventManager\ListenerAggregateInterface` and *use* this trait to
  implement the `detach()` logic.

## Usage

To use an aggregate listener, you need to attach it to the event manager. As
noted in the intro to this section, you do so by passing the event
manager to the aggregate's `attach()` method:

```php
// Assume $events is an EventManager instance, and $aggregate is an instance of
// the Aggregate class defined earlier.
$aggregate->attach($events);
```

## Recommendations

- We recommend using listener aggregates when you have several listeners that are
  related and/or share common dependencies and/or business logic. This helps keep
  the logic in the same location, and helps reduce dependencies.

- We recommend using the verbiage `on<Event Name>`  to name your listener
  methods. This helps hint that they will be triggered *on an event*, and
  semantically ties them to the specific event name.
