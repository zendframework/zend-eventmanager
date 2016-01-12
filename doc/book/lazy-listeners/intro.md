# Lazy Listeners

Lazy listeners are a new feature in version 3.0, provided to reduce the
performance overhead of fetching listeners and/or aggregates from a Dependency
Injection Container until they are actually triggered.

The feature consists of three classes:

- `Zend\EventManager\LazyListener`, which provides basic capabilities for
  wrapping the retrieval of a listener from a container and invoking it.
- `Zend\EventManager\LazyEventListener`, which extends `LazyListener` but adds
  awareness of the event and optionally priority to use when attaching the
  listener. These are primarily used and created by:
- `Zend\EventManager\LazyListenerAggregate`, which can take a list of
  `LazyEventListeners` and/or their definitions, and be used as an aggregate
  listener for attaching the lazy listeners to an event manager.

## Preparation

In order to use the lazy listeners feature, you will need to install
container-interop, if you haven't already:

```bash
$ composer require container-interop/container-interop
```
