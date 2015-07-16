# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

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
