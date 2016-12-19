# LazyListener

`Zend\EventManager\LazyListener` provides a callable wrapper around fetching a
listener from a container and invoking it.

## Usage

To create a `LazyListener` instance, you must pass to its constructor:

- a *definition* of the listener; this is an array defining:
    - a `listener` key, with the name of the listener service to pull from the container.
    - a `method` key, with the name of the method to invoke on the listener instance.
- a *container*; this is a [container-interop](https://github.com/container-interop/container-interop),
  such as provided by
  [zend-servicemanager](https://github.com/zendframework/zend-servicemanager),
  [Aura.Di](https://github.com/auraphp/Aura.Di), etc.
- optionally an `$env` array; this is a set of options or other configuration to
  use when creating the listener instance. Since not all containers support
  passing additional options at creation, we recommend omitting the `$env`
  argument when creating portable applications.

As an example, let's assume:

- We have a listener registered in our container with the service name
  `My\Application\Listener`.
- The specific listener method is `onDispatch`.
- I have a container-interop instance in the variable `$container` and an event
  manager in the variable `$events`.

I might then create and attach my lazy listener as follows:

```php
use My\Application\Listener;
use Zend\EventManager\LazyListener;

$events->attach('foo', new LazyListener([
    'listener' => Listener::class,
    'method'   => 'onDispatch',
], $container));
```

`LazyListener` implements the method `__invoke()`, allowing you to attach it
directly as a callable listener!

Internally, it will do essentially the following:

```php
$listener = $container->get($this->listener);
$method   = $this->method;
return $listener->{$method}($event);
```
