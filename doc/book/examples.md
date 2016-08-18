# Examples

## Modifying Arguments

Occasionally it can be useful to allow listeners to modify the arguments they
receive so that later listeners or the calling method will receive those changed
values.

As an example, you might want to pre-filter a date that you know will arrive as
a string and convert it to a `DateTime` argument.

To do this, you can pass your arguments to `prepareArgs()`, and pass this new
object when triggering an event. You will then pull that value back into your
method.

```php
use DateTime;

class ValueObject
{
    // assume a composed event manager

    function inject(array $values)
    {
        $argv = compact('values');
        $argv = $this->getEventManager()->prepareArgs($argv);
        $this->getEventManager()->trigger(__FUNCTION__, $this, $argv);
        $date = isset($argv['values']['date'])
            ? $argv['values']['date']
            : new DateTime('now');

        // ...
    }
}

$v = new ValueObject();

$v->getEventManager()->attach('inject', function($e) {
    $values = $e->getParam('values');
    if (! $values) {
        return;
    }

    $values['date'] = isset($values['date'])
        ? new DateTime($values['date'])
        : new DateTime('now');

    $e->setParam('values', $values);
});

$v->inject([
    'date' => '2011-08-10 15:30:29',
]);
```

## Short Circuiting

One common use case for events is to trigger listeners until either one
indicates no further processing should be done, or until a return value meets
specific criteria.

As an example, a request listener might be able to return a response object, and
would signal to the target to stop event propagation.

```php
$listener = function($e) {
    // do some work

    // Stop propagation and return a response
    $e->stopPropagation(true);
    return $response;
};
```

Alternately, the request handler could halt execution at the first listener that
returns a response.

```php
class Foo implements DispatchableInterface
{
    // assume composed event manager

    public function dispatch(Request $request, Response $response = null)
    {
        $argv = compact('request', 'response');
        $results = $this->getEventManager()->triggerUntil(function($v) {
            return ($v instanceof Response);
        }, __FUNCTION__, $this, $argv);
    }
}
```

Typically, you may want to return the value that stopped execution, or use it
some way. All `trigger*()` methods return a `ResponseCollection` instance; call
its `stopped()` method to test if execution was stopped, and the `last()` method
to retrieve the return value from the last executed listener:

```php
class Foo implements DispatchableInterface
{
    // assume composed event manager

    public function dispatch(Request $request, Response $response = null)
    {
        $argv = compact('request', 'response');
        $results = $this->getEventManager()->triggerUntil(function($v) {
            return ($v instanceof Response);
        }, __FUNCTION__, $this, $argv);

        // Test if execution was halted, and return last result:
        if ($results->stopped()) {
            return $results->last();
        }

        // continue...
    }
}
```

## Assigning Priority to Listeners

One use case for the `EventManager` is for implementing caching systems. As
such, you often want to check the cache early, and save to it late.

The third argument to `attach()` is a priority value. The higher this number,
the earlier that listener will execute; the lower it is, the later it executes.
The value defaults to 1, and values will trigger in the order registered within
a given priority.

To implement a caching system, our method will need to trigger an event at
method start as well as at method end. At method start, we want an event that
will trigger early; at method end, an event should trigger late.

Here is the class in which we want caching:

```php
class SomeValueObject
{
    // assume it composes an event manager

    public function get($id)
    {
        $params = compact('id');
        $results = $this->getEventManager()->trigger('get.pre', $this, $params);

        // If an event stopped propagation, return the value
        if ($results->stopped()) {
            return $results->last();
        }

        // do some work...

        $params['__RESULT__'] = $someComputedContent;
        $this->getEventManager()->trigger('get.post', $this, $params);
    }
}
```

Now, let's create a `ListenerAggregateInterface` implementation that can handle
caching for us:

```php
use Zend\Cache\Cache;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\EventManager\EventInterface;

class CacheListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach('get.pre', [$this, 'load'], 100);
        $this->listeners[] = $events->attach('get.post', [$this, 'save'], -100);
    }

    public function load(EventInterface $e)
    {
        $id = get_class($e->getTarget()) . '-' . json_encode($e->getParams());
        if (false !== ($content = $this->cache->load($id))) {
            $e->stopPropagation(true);
            return $content;
        }
    }

    public function save(EventInterface $e)
    {
        $params  = $e->getParams();
        $content = $params['__RESULT__'];
        unset($params['__RESULT__']);

        $id = get_class($e->getTarget()) . '-' . json_encode($params);
        $this->cache->save($content, $id);
    }
}
```

We can then attach the aggregate to an event manager instance.

```php
$value         = new SomeValueObject();
$cacheListener = new CacheListener($cache);
$cacheListener->attach($value->getEventManager());
```

Now, as we call `get()`, if we have a cached entry, it will be returned
immediately; if not, a computed entry will be cached when we complete the
method.
