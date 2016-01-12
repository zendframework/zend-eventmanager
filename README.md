# zend-eventmanager

[![Build Status](https://secure.travis-ci.org/zendframework/zend-eventmanager.svg?branch=master)](https://secure.travis-ci.org/zendframework/zend-eventmanager)
[![Coverage Status](https://coveralls.io/repos/zendframework/zend-eventmanager/badge.svg?branch=master)](https://coveralls.io/r/zendframework/zend-eventmanager?branch=master)

The `Zend\EventManager` is a component designed for the following use cases:

- Implementing simple subject/observer patterns.
- Implementing Aspect-Oriented designs.
- Implementing event-driven architectures.

The basic architecture allows you to attach and detach listeners to named events,
both on a per-instance basis as well as via shared collections; trigger events;
and interrupt execution of listeners.

- File issues at https://github.com/zendframework/zend-eventmanager/issues
- Documentation is at http://zend-eventmanager.rtfd.org

For migration from version 2 to version 3, please [read the migration
documentation](http://zend-eventmanager.readthedocs.org/en/latest/migration/intro/).

## Benchmarks

We provide scripts for benchmarking zend-eventmanager using the
[Athletic](https://github.com/polyfractal/athletic) framework; these can be
found in the `benchmarks/` directory.

To execute the benchmarks you can run the following command:

```bash
$ vendor/bin/athletic -p benchmarks
```
