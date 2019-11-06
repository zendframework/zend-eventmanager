# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.3.0 - TBD

### Added

- [#72](https://github.com/zendframework/zend-eventmanager/pull/72) adds support for PHP 7.3.

- [#73](https://github.com/zendframework/zend-eventmanager/pull/73) adds interfaces to allow duck-typing the EventManager as a [PSR-14](https://www.php-fig.org/psr/psr-14/) event
  dispatcher. Full support is not provided yet as this version still supports
  PHP 5.6. The new interfaces include:

  - `Zend\EventManager\EventDispatcherInterface`
  - `Zend\EventManager\ListenerProvider\ListenerProviderInterface`
  - `Zend\EventManager\StoppableEventInterface`

  These interfaces will be removed in version 4.0, in favor of the official
  PSR-14 interfaces.

- [#73](https://github.com/zendframework/zend-eventmanager/pull/73) adds the following interfaces:
  - `Zend\EventManager\EventDispatchingInterface`, for indicating a class
    composes an `EventDispatcherInterface` instance. This interface will replace
    the `Zend\EventManager\EventsCapableInterface` in version 4.0.
  - `Zend\Expressive\ListenerProvider\PrioritizedListenerProviderInterface`,
    which extends the `ListenerProviderInterface`, and adds the method
    `getListenersForEventByPriority($event, $identifiers = [])`. This method
    will return a list of integer priority keys mapping to lists of callable
    listeners.
  - `Zend\Expressive\ListenerProvider\PrioritizedListenerAttachmentInterface`,
    which provides methods for attaching and detaching listeners with optional
    priority values. This interface largely replaces the various related methods
    in the current `EventManagerInterface`, and is for use with listener
    providers.
  - `Zend\Expressive\ListenerProvider\ListenerSubscriberInterface`, for
    indicating that a class can attach multiple listeners to a
    `PrioritizedListenerAttachmentInterface` instance. This largely replaces the
    current `ListenerAggregateInterface` functionality. Users should likely use
    the PSR-14 utility package's `DelegatingProvider` instead, however.

- [#73](https://github.com/zendframework/zend-eventmanager/pull/73) adds the following listener provider classes and utilities:
  - `AbstractListenerSubscriber` and `ListenerSubscriberTrait` can be used to
    provide a generic way to detach subscribers. In most cases,
    `ListenerSubscriberInterface` implementations should define their own logic
    for doing so.
  - `PrioritizedListenerProvider` implements `PrioritizedListenerProviderInterface`
    and `PrioritizedListenerAttachmentInterface` in order to provide the various
    listener attachment and retrieval capabilities in previous versions of the
    `EventManager` class.
  - `PrioritizedIdentifierListenerProvider` implements `PrioritizedListenerProviderInterface`
    and `SharedEventManagerInterface`, and provides all features of the
    `SharedEventManager` class from previous versions of the package.
  - `PrioritizedAggregateListenerProvider` implements `PrioritizedListenerProviderInterface`
    and accepts a list of `PrioritizedListenerProviderInterface` instances and
    optionally a generic `ListenerProviderInterface` instance to its
    constructor. When retrieving listeners, it will loop through the
    `PrioritizedListenerProviderInterface` instance in order, yielding from
    each, and then, if present, yield from the generic
    `ListenerProviderInterface` instance. This approach essentially replaces the
    listener and shared listener aggregation in previous versions of the
    `EventManager`.
  - `LazyListener` combines the functionalities of `Zend\EventManager\LazyListener`
    and `Zend\EventManager\LazyEventListener`. If no event or priority are
    provided to the constructor, than the `getEvent()` and `getPriority()`
    methods will each return `null`. When invoked, the listener will pull the
    specified service from the provided DI container, and then invoke it.
  - `LazyListenerSubscriber` implements `ListenerSubscriberInterface` and
    accepts a list of `LazyListener` instances to its constructor; any
    non-`LazyListener` instances or any that do not define an event will cause
    th constructor to raise an exception. When its `attach()` method is called,
    it attaches the lazy listeners based on the event an priority values it
    pulls from them.

- [#73](https://github.com/zendframework/zend-eventmanager/pull/73) adds the static method `createUsingListenerProvider()` to the `EventManager`
  class. This method takes a `ListenerProviderInterface`, and will then pull
  directly from it when triggering events. If the provider also implements
  `PrioritizedListenerAttachmentInterface`, the various listener attachment
  methods defined in `EventManager` will proxy to it.

- [#73](https://github.com/zendframework/zend-eventmanager/pull/73) adds the static method `createUsingListenerProvider()` to the `EventManager`

### Changed

- [#73](https://github.com/zendframework/zend-eventmanager/pull/73) modifies the `SharedEventManager` class to extend the new
  `Zend\EventManager\ListenerProvider\PrioritizedIdentifierListenerProvider` class.

- [#73](https://github.com/zendframework/zend-eventmanager/pull/73) modifies the `EventManager` class as follows:
  - It now implements each of `ListenerProviderInterface` and
    `PrioritizedListenerAttachmentInterface`.
  - If constructed normally, it will create a `PrioritizedListenerProvider`
    instance, and use that for all listener attachment. If a
    `SharedEventManagerInterface` is provided, it will create a
    `PrioritizedAggregateListenerProvider` using its own
    `PrioritizedListenerProvider` and the shared manager, and use that for
    fetching listeners.
  - Adds a `dispatch()` method as an alternative to the various `trigger*()` methods.

### Deprecated

- [#73](https://github.com/zendframework/zend-eventmanager/pull/73) deprecates the following interfaces and classes:
  - `Zend\EventManager\EventInterface`. Users should start using vanilla PHP
    objects that encapsulate all expected behavior for setting and retrieving
    values and otherwise mutating state, including how and when propagation of the
    event should stop.
  - `Zend\EventManager\EventManagerInterface`; start typehinting against the
    PSR-14 `EventDispatcherInterface` (or, in the meantime, the package-specific
    variant).
  - `Zend\EventManager\EventManagerAwareInterface`
  - `Zend\EventManager\EventManagerAwareTrait`
  - `Zend\EventManager\EventsCapableInterface`; start using `EventDispatchingInterface` instead.
  - `Zend\EventManager\SharedEventManager`; start using listener providers
    instead, attaching to identifiers based on event types.
  - `Zend\EventManager\SharedEventManagerInterface`
  - `Zend\EventManager\SharedEventsCapableInterface`
  - `Zend\EventManager\ListenerAggregateInterface`; use the new `ListenerSubscriberInterface` instead.
  - `Zend\EventManager\ListenerAggregateTrait`; use the new
    `ListenerSubscriberTrait`, or define your own detachment logic.
  - `Zend\EventManager\AbstractListenerAggregate`; use the new
    `AbstractListenerSubscriber`, or define your own detachment logic.
  - `Zend\EventManager\ResponseCollection`; aggregate state in the event itself,
    and have the event determine when propagation needs to stop.
  - `Zend\EventManager\LazyListener`; use `Zend\EventManager\ListenerProvider\LazyListener` instead.
  - `Zend\EventManager\LazyEventListener`; use `Zend\EventManager\ListenerProvider\LazyListener` instead.
  - `Zend\EventManager\LazyListenerAggregate`; use `Zend\EventManager\ListenerProvider\LazyListenerSubscriber` instead.
  - `Zend\EventManager\FilterChain` and the `Filter` subnamespace; these will
    move to a separate package in the future.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.2.2 - TBD

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.2.1 - 2018-04-25

### Added

- [#66](https://github.com/zendframework/zend-eventmanager/pull/66) adds support for PHP 7.2.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.2.0 - 2017-07-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#47](https://github.com/zendframework/zend-eventmanager/pull/47) removes
  support for PHP 5.5 and HHVM.

### Fixed

- Nothing.

## 3.1.0 - 2016-12-19

### Added

- [#26](https://github.com/zendframework/zend-eventmanager/pull/26) publishes
  the documentation to https://zendframework.github.io/zend-eventmanager/

### Changes

- [#17](https://github.com/zendframework/zend-eventmanager/pull/17) makes a
  number of internal changes to how listeners are stored in order to improve
  performance, by as much as 10% in the scenario used in the MVC layer.
  
  Additionally, it optimizes when the target and event arguments are injected
  into an event, eliminating that step entirely when either is unavailable.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.0.1 - 2016-02-18

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#24](https://github.com/zendframework/zend-eventmanager/pull/24) updates the
  zend-stdlib dependency to `^2.7.3 || ^3.0`, allowing either major version.

## 3.0.0 - 2016-01-12

### Added

- [Migration documentation](doc/book/migration/) was added.
- [Automated benchmarks](benchmarks/) were added.
- `EventManager::__construct()` now accepts an optional
  `SharedEventManagerInterface` instance as the first argument, and an optional
  array of identifiers as the second. As identifiers have no meaning without a
  shared manager present, they are secondary to providing the shared manager.
- `EventManagerInterface::trigger()` changes its signature to
  `trigger($eventName, $target = null, $argv = [])`; each argument has exactly
  one possible meaning; the `$eventName` can only be a string event name. The
  fourth `$callback` argument is removed.
- `EventManagerInterface::triggerUntil()` changes its signature to
  `triggerUntil(callable $callback, $eventName, $target = null, $argv = null)`.
  Each argument has exactly one meaning.
- `EventManagerInterface` adds two new methods for triggering provided
  `EventInterface` arguments: `triggerEvent(EventInterface $event)` and
  `triggerEventUntil(callable $callback, EventInterface $event)`.
- `EventManagerInterface::attach()` and `detach()` change their signatures to
  `attach($eventName, callable $listener, $priority = 1)` and `detach(callable
  $listener, $eventName = null)`, respectively. Note that `$eventName` can now
  only be a string event name, not an array or `Traversable`.
- `EventManagerInterface::setIdentifiers()` and `addIdentifiers()` change their
  signatures to each only accept an *array* of identifiers.
- `SharedEventManagerInterface::getListeners()` changes signature to
  `getListeners(array $identifiers, $eventName)` and now guarantees return of an
  array. Note that the second argument is now *required*.
- `SharedEventManagerInterface::attach()` changes signature to
  `attach($identifier, $eventName, callable $listener, $priority = 1)`. The
  `$identifier` and `$eventName` **must** be strings.
- `SharedEventManagerInterface::detach()` changes signature to `detach(callable
  $listener, $identifier = null, $eventName = null)`; `$identifier` and
  `$eventName` **must** be strings if passed.
- `ListenerAggregateInterface::attach()` adds an optional `$priority = 1`
  argument. This was used already in v2, but not dictated by the interface.
- `FilterInterface::attach()` and `detach()` have changed signature to
  `attach(callable $callback)` and `detach(callable $ilter)`, respectively.
- `LazyListener` allows wrapping:
  - fetching a listener service from a container-interop container, and
  - invoking a designated listener method with the provided event.
- `LazyEventListener` extends `LazyListener`, and provides metadata for
  discovering the intended event name and priority at which to attach the lazy
  listener; these are consumed by:
- `LazyListenerAggregate`, which, provided a list of `LazyEventListeners` and/or
  definitions to use to create them, acts as an aggregate for attaching a number
  of such listeners at once.
- [#20](https://github.com/zendframework/zend-eventmanager/pull/20) updates the
  trait `Zend\EventManager\Test\EventListenerIntrospectionTrait` so that the
  implementation will work with the v3 changes; the tests written for v2
  continue to pass, allowing this trait to be used to provide compatibility
  testing between v2 and v3.

### Deprecated

- Nothing.

### Removed

- `GlobalEventManager` and `StaticEventManager` are removed (with prejudice!).
- `ProvidesEvents`, which was previously deprecated, is removed.
- `EventManagerInterface::setSharedManager()` is removed. Shared managers are
  now expected to be injected during instantiation.
- `EventManagerInterface::getEvents()` and `getListeners()` are removed; they
  had now purpose within the implementation.
- `EventManagerInterface::setEventClass()` was renamed to `setEventPrototype()`,
  which now expects an `EventInterface` instance. That instance will be cloned
  whenever a new event is created.
- `EventManagerInterface::attachAggregate()` and `detachAggregate()` are
  removed. Users should use the `attach()` and `detach()` methods of the
  aggregates themselves.
- `SharedEventAggregateAwareInterface` and `SharedListenerAggregateInterface`
  are removed. This was an undocumented and largely unused feature.
- `SharedEventManagerAwareInterface` is removed. A new interface,
  `SharedEventsCapableInterface` defines the `getSharedManager()` method from
  the interface, and `EventManagerInterface` extends that new interface.
- `SharedEventManagerInterface::getEvents()` is removed, as it had no purpose in
  the implementation.
- `ResponseCollection::setStopped()` no longer implements a fluent interface.

### Fixed

- `FilterIterator::insert()` has been modified to raise an exception if the value provided is not a callable.

## 2.6.2 - 2016-01-12

### Added

- [#19](https://github.com/zendframework/zend-eventmanager/pull/19) adds a new
  trait, `Zend\EventManager\Test\EventListenerIntrospectionTrait`, intended for
  composition in unit tests. It provides a number of methods that can be used
  to retrieve listeners with or without associated priority, and the assertion
  `assertListenerAtPriority(callable $listener, $priority, $event, EventManager $events, $message = '')`,
  which can be used for testing that a listener was registered at the specified
  priority with the specified event.

  The features in this patch are intended to facilitate testing against both
  version 2 and version 3 of zend-eventmanager, as it provides a consistent API
  for retrieving lists of events and listeners between the two versions.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.6.0 - 2015-09-29

### Added

- Added `Zend\EventManager\SharedEventsCapableInterface`. This interface will
  largely replace `Zend\EventManager\SharedEventManagerAwareInterface` in
  version 3, and the latter was updated to extend it.
- Added `EventManager::triggerEvent(EventInterface $event)` as a
  forwards-compatibility feature.
- Add `EventManager::triggerEventUntil(callable $callback, EventIterface $event)`
  as a forwards-compatibility feature.
- Adds [Athletic](https://github.com/polyfractal/athletic) benchmarks to aid in
  gauging performanc impact of changes; these are a development change only.

### Deprecated

- Marked `GlobalEventManager` as deprecated; this class will be removed in
  version 3.
- Marked `StaticEventManager` as deprecated; this class will be removed in
  version 3.
- Marked `SharedListenerAggregateInterface` as deprecated; this interface will
  be removed in version 3.
- Marked `SharedEventAggregateAwareInterface` as deprecated; this interface will
  be removed in version 3.
- Marked `SharedEventManagerAwareInterface` as deprecated; this interface will
  be removed in version 3.
- Marked `EventManager::setSharedManager()` as deprecated; this method will be
  removed in version 3.
- Marked `EventManager::unsetSharedManager()` as deprecated; this method will be
  removed in version 3.
- Marked `EventManagerInterface::` and `EventManager::getEvents()` as
  deprecated; this method will be removed in version 3.
- Marked `EventManagerInterface::` and `EventManager::getListeners()` as
  deprecated; this method will be removed in version 3.
- Marked `EventManagerInterface::` and `Eventmanager::setEventClass()` as
  deprecated; this method is renamed to `setEventPrototype(EventInterface $event)`
  in version 3.
- Marked `EventManagerInterface::` and `EventManager::attachAggregate()` as
  deprecated; this method will be removed in version 3.
- Marked `EventManagerInterface::` and `EventManager::detachAggregate()` as
  deprecated; this method will be removed in version 3.
- Marked `SharedEventManagerInterface::` and `SharedEventManager::getEvents()`
  as deprecated; this method will be removed in version 3.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.5.2 - 2015-07-16

### Added

- [#5](https://github.com/zendframework/zend-eventmanager/pull/5) adds a number
  of unit tests to improve test coverage, and thus maintainability and
  stability.

### Deprecated

- Nothing.

### Removed

- [#3](https://github.com/zendframework/zend-eventmanager/pull/3) removes some
  PHP 5.3- and 5.4-isms (such as marking Traits as requiring 5.4, and closing
  over a copy of `$this`) from the test suite.

### Fixed

- [#5](https://github.com/zendframework/zend-eventmanager/pull/5) fixes a bug in
  `FilterIterator` that occurs when attempting to extract from an empty heap.
