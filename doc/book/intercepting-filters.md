# Intercepting Filters

[Intercepting filters](https://en.wikipedia.org/wiki/Interceptor_pattern) are a
design pattern used for providing mechanisms to alter the workflow of an
application. Implementing them provides a way to have a standard public
interface, with the ability to attach arbitrary numbers of filters that will
take the incoming arguments in order to alter the workflow.

zend-eventmanager provides an intercepting filter implementation via
`Zend\EventManager\FilterChain`.

## Preparation

To use the `FilterChain` implementation, you will need to install zend-stdlib,
if you have not already:

```bash
$ composer require zendframework/zend-stdlib
```

## FilterChainInterface

`Zend\EventManager\FilterChain` is a concrete implementation of
`Zend\EventManager\Filter\FilterInterface`, which defines a workflow for
intercepting filters. This includes the following methods:

```php
interface FilterInterface
{
    public function run($context, array $params = []);

    public function attach(callable $callback);
    public function detach(callable $callback);

    public function getFilters();
    public function clearFilters();
    public function getResponses();
}
```

In many ways, it's very similar to the `EventManagerInterface`, but with a few
key differences:

- A filter essentially defines a single event, which obviates the need for
  attaching to multiple events. As such, you pass the target and parameters only
  when "triggering" (`run()`) a filter.
- Instead of passing an `EventInterface` to each attached filter, a
  `FilterInterface` implementation will pass:
  - The `$context`
  - The `$params`
  - A `FilterIterator`, to allow the listener to call on the next filter.

## FilterIterator

When executing `run()`, a `FilterInterface` implementation is expected to
provide the stack of attached filters to each listener. This stack will
typically be a `Zend\EventManager\Filter\FilterIterator` instance.

`FilterIterator` extends `Zend\Stdlib\FastPriorityQueue`, and, as such, is
iterable, and provides the method `next()` for advancing the queue. 

As such, a listener should decide if more processing is necessary, and, if so,
call on `$chain->next()`, passing the same set of arguments.

## Filters

A filter attached to a `FilterChain` instance can be any callable. However,
these callables should expect the following arguments:

```php
function ($context, array $argv, FilterIterator $chain)
```

A filter can therefore act on the provided `$context`, using the provided
arguments.

Part of that execution can also be deciding that other filters should be called.
To do so, it will call `$chain->next()`, providing it the same arguments:

```php
function ($context, array $argv, FilterIterator $chain)
{
    $message = isset($argv['message']) ? $argv['message'] : '';

    $message = str_rot13($message);

    $filtered = $chain->next($context, ['message' => $message], $chain);

    return str_rot13($filtered);
}
```

You can choose to call `$chain->next()` at any point in the filter, allowing you
to:

- pre-process arguments and/or alter the state of the `$context`.
- post-process results and/or alter the state of the `$context` based on the
  results.
- skip processing entirely if criteria is not met (e.g., missing arguments,
  invalid `$context` state).
- short-circuit the chain if no processing is necessary (e.g., a cache hit is
  detected).

## Execution

When executing a filter chain, you will provide the `$context`, which is usually
the object under observation, and arguments, which are typically the arguments
passed to the method triggering the filter chain.

As an example, consider the following filter-enabled class:

```php
use Zend\EventManager\FilterChain;

class ObservedTarget
{
    private $filters = [];

    public function attachFilter($method, callable $listener)
    {
        if (! method_exists($this, $method)) {
            throw new \InvalidArgumentException('Invalid method');
        }
        $this->getFilters($method)->attach($listener);
    }

    public function execute($message)
    {
        return $this->getFilters(__FUNCTION__)
            ->run($this, compact('message'));
    }

    private function getFilters($method)
    {
        if (! isset($this->filters[$method])) {
            $this->filters[$method] = new FilterChain();
        }
        return $this->filters[$method];
    }
}
```

Now, let's create an instance of the class, and attach some filters to it.

```php
$observed = new ObservedTarget();

$observed->attach(function ($context, array $args, FilterIterator $chain) {
    $args['message'] = isset($args['message'])
        ? strtoupper($args['message'])
        : '';

    return $chain->next($context, $args, $chain);
});

$observed->attach(function ($context, array $args, FilterIterator $chain) {
    return (isset($args['message'])
        ? str_rot13($args['message'])
        : '');
});

$observed->attach(function ($context, array $args, FilterIterator $chain) {
    return (isset($args['message'])
        ? strtolower($args['message'])
        : '');
});
```

Finally, we'll call the method, and see what results we get:

```php
$observed->execute('Hello, world!');
```

Since filters are run in the order in which they are attached, the following
will occur:

- The first filter will transform our message into `HELLO, WORLD!`, and then
  call on the next filter.
- The second filter will apply a ROT13 transformation on the string and *return*
  it: `!DLROW ,OLLEH`.

Because the second filter does not call `$chain->next()`, the third filter never
executes.

## Notes

We recommend using the construct `run($this, compact(method argument names)`
when invoking a `FilterChain`. This makes the argument keys predictable inside
filters.

We also recommend putting the default logic for the method invoking the filter
chain in a filter itself, and attaching it at invocation. This allows
intercepting filters to replace the main logic, while still providing a default
path. This might look like:

```php
// Assume that the class contains the `attachFilter()` implementation from above.
class ObservedTarget
{
    private $attached = [];

    public function execute($message)
    {
        if (! isset($this->attached[__FUNCTION__])) {
            $this->attachFilter(__FUNCTION__, $this->getExecuteFilter();
        }

        return $this->getFilters(__FUNCTION__)
            ->run($this, compact('message'));
    }

    private function getExecuteFilter()
    {
        $this->attached['execute'] = true;
        return function ($context, array $args, FilterIterator $chain) {
            return $args['message'];
        };
    }
}
```

Intercepting filters are a powerful way to introduce aspect-oriented programming
paradigms into your code, as well as general-purpose mechanisms for introducing
plugins.
