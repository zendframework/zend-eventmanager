# The EventManager: Overview

zend-eventmanager is a component designed for the following use cases:

- Implementing simple subject/observer patterns.
- Implementing Aspect-Oriented designs.
- Implementing event-driven architectures.

The basic architecture allows you to attach and detach listeners to named
events, both on a per-instance basis as well as via shared collections; trigger
events; and interrupt execution of listeners.
