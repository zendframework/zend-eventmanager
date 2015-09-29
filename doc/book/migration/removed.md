# Removed Functionality

The following interfaces, classes, and methods have been removed for version 3.

## GlobalEventManager and StaticEventManager

`Zend\EventManager\GlobalEventManager` and
`Zend\EventManager\StaticEventManager` were removed, and there are no
replacements.  Global static state is generally considered a dangerous practice
due to the side effects it can create, and we felt it was better to remove the
option from the framework entirely.

## ProvidesEvents

The trait `Zend\EventManager\ProvidesEvents` has been deprecated for most of
the 2.0 series; use `Zend\EventManager\EventManagerAwareTrait` instead.

## EventManagerInterface::setSharedManager()

We have removed `EventManagerInterface::setSharedManager()`, and also removed it
from the `EventManager` implementation. The `SharedEventManager` should be
injected during instantiation now.

## EventManagerInterface::getEvents() and getListeners()

We have removed both `EventManagerInterface::getEvents()` and `getListeners()`,
as we did not have a stated use case for the methods. The event manager should
be something that aggregates listeners and triggers events; the details of what
listeners or events are attached is largely irrelevant.

The primary use case for `getListeners()` is often to determine if a listener is
attached before detaching it. Since `detach()` acts as a no-op if the provided
listener is not present, checking for presence first is not necessary.

## EventManagerInterface::setEventClass()

The method `EventManagerInterface::setEventClass()` was removed and replaced
with `EventManagerInterface::setEventPrototype()`, which has the following
signature:

```php
setEventPrototype(EventInterface $event);
```

This was done to prevent errors that occurred when invalid event class names
were provided. Additionally, internally, event managers will clone the
instance any time `trigger()` or `triggerUntil()` are called — which is
typically faster and less resource intensive than instantiating a new instance.

## EventManagerInterface::attachAggregate() and detachAggregate()

The methods `attachAggregate()` and `detachAggregate()` were removed from the
`EventManagerInterface` and concrete `EventManager` implementation. Furthermore,
`attach()` and `detach()` no longer handle aggregates.

The reason they were removed is because they simply proxied to the `attach()`
and `detach()` methods of the `ListenerAggregateInterface`. As such, to
forward-proof your applications, you can alter statements that attach aggregates
to an event manager reading as follows:

```php
$events->attach($aggregate); // or
$events->attachAggregate($aggregate);
```

to:

```php
$aggregate->attach($events);
```

Similarly, for detaching an aggregate, migrate from:

```php
$events->detach($aggregate); // or
$events->detachAggregate($aggregate);
```

to:

```php
$aggregate->detach($events);
```

The above works in all released versions of the component.

## SharedEventAggregateAwareInterface, SharedListenerAggregateInterface

The interfaces `Zend\EventManager\SharedEventAggregateAwareInterface` and
`SharedListenerAggregateInterface` were removed, as the concept of shared
listener aggregates was removed from version 3.

Migration will depend on what you have done in your application: extending
the `SharedEventManager` and/or implementing `SharedEventAggregateAwareInterface`,
or implementing `SharedListenerAggregateInterface`.

### SharedEventAggregateAwareInterface

`Zend\EventManager\SharedEventAggregateAwareInterface` was added mid-way through
the v2 lifecycle to allow adding shared listener aggregates to the
`SharedEventManager`. If you were extending the `SharedEventManager` and
overriding the methods defined in `SharedEventAggregateAwareInterface`, you
should remove them.

If you were implementing `SharedEventAggregateAwareInterface`, the interface no
longer exists, and you should likely remove your implementation.

### SharedListenerAggregateInterface

For those implementing shared listener aggregates, you can continue to use them,
but will need to change how you do so.

To migrate, you have two steps to take: remove the
`SharedListenerAggregateInterface` implementation declaration from your
aggregate class, and swap attachment of the aggregate.

To accomplish the first step, keep the `attachShared()` and `detachShared()`
methods in your class, but remove the `implements
SharedListenerAggregateInterface` from the class declaration. For instance, if
you had the following:

```php
namespace Foo;

use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\SharedListenerAggregateInterface;

class MySharedAggregate implements SharedListenerAggregateInterface
{
    public function attachShared(SharedEventManagerInterface $manager)
    {
        // ...
    }

    public function detachShared(SharedEventManagerInterface $manager)
    {
        // ...
    }
}
```

then modify it to instead read:

```php
namespace Foo;

use Zend\EventManager\SharedEventManagerInterface;

class MySharedAggregate
{
    public function attachShared(SharedEventManagerInterface $manager)
    {
        // ...
    }

    public function detachShared(SharedEventManagerInterface $manager)
    {
        // ...
    }
}
```

For the second step, instead of attaching the aggregate to the shared event
manager, you will pass the shared event manager to your aggregate. For example,
if you had the following in your code:

```php
$sharedEvents->attachAggregate($mySharedAggregate);
```

then you can change it to:

```php
$mySharedAggregate->attachShared($sharedEvents);
```

This has exactly the same effect, and makes your code forward-compatible with
v3.

#### SharedEventManagerAwareInterface

The interface `Zend\EventManager\SharedEventManagerAwareInterface` was removed,
as version 3 now requires tha the `SharedEventManagerInterface` instance be
injected into the `EventManager` instance at instantiation.

A new interface, `Zend\EventManager\SharedEventsCapableInterface`, provides the
`getSharedManager()` method, and `EventManagerInterface` extends it.

To migrate, you have the following options:

- If you are only interested in the `getSharedManager()` method, you can
  implement `SharedEventsCapableInterface` starting with version 2.6.0. If you
  do this, you can also safely remove the `setSharedManager()` method from your
  implementation.
- If you will require injecting the shared manager, use duck typing to determine
  if a class has the `setSharedManager()` method:

  ```php
  if (method_exists($instance, 'setSharedManager')) {
      $instance->setSharedManager($sharedEvents);
  }
  ```

  Alternately, if you control instantiation of the instance, consider injection
  at instantiation, or within the factory used to create your instance.

## SharedEventManagerInterface::getEvents()

The method `SharedEventManagerInterface::getEvents()` was removed. The method
was not consumed by the event manager, and served no real purpose.
