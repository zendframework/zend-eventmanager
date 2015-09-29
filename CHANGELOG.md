# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

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
