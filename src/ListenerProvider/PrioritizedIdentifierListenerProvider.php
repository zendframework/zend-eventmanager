<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace Zend\EventManager\ListenerProvider;

use Zend\EventManager\Exception;
use Zend\EventManager\SharedEventManagerInterface;

/**
 * @deprecated This implementation exists to allow the SharedEventManager
 *     system to coexist with listener providers during the 3.X series of
 *     releases, and will be removed in version 4. Use class and interface
 *     names of events when attaching them to a provider instead, as those
 *     are most equivalent to the identifier system.
 */
class PrioritizedIdentifierListenerProvider implements
    PrioritizedListenerProviderInterface,
    SharedEventManagerInterface
{
    /**
     * Identifiers with event connections
     *
     * @var array<string, array<string, array<int, callable[]>>>
     */
    protected $identifiers = [];

    /**
     * {@inheritDoc}
     * @todo   Use `yield from` once we bump the minimum supported PHP version to 7+.
     * @param  array $identifiers Identifiers from which to match event listeners.
     * @throws Exception\InvalidArgumentException for invalid event types
     * @throws Exception\InvalidArgumentException for invalid identifier types
     */
    public function getListenersForEvent($event, array $identifiers = [])
    {
        // @todo `yield from $this->iterateByPriority(...)`
        foreach ($this->iterateByPriority($this->getListenersForEventByPriority($event, $identifiers)) as $listener) {
            yield $listener;
        }
    }

    /**
     * {@inheritDoc}
     * @throws Exception\InvalidArgumentException for invalid event types
     * @throws Exception\InvalidArgumentException for invalid identifier types
     */
    public function getListenersForEventByPriority($event, array $identifiers = [])
    {
        $this->validateEventForListenerRetrieval($event, __METHOD__);

        $prioritizedListeners = [];
        $identifiers          = $this->normalizeIdentifierList($identifiers);
        $eventList            = $this->getEventList($event);

        foreach ($identifiers as $identifier) {
            if (! is_string($identifier) || empty($identifier)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Identifier names passed to %s must be non-empty',
                    __METHOD__
                ));
            }

            if (! isset($this->identifiers[$identifier])) {
                continue;
            }

            $listenersByIdentifier = $this->identifiers[$identifier];

            foreach ($eventList as $eventName) {
                if (! isset($listenersByIdentifier[$eventName])) {
                    continue;
                }

                foreach ($listenersByIdentifier[$eventName] as $priority => $listOfListeners) {
                    $prioritizedListeners[$priority] = isset($prioritizedListeners[$priority])
                        ? array_merge($prioritizedListeners[$priority], $listOfListeners[0])
                        : $listOfListeners[0];
                }
            }
        }

        return $prioritizedListeners;
    }

    /**
     * {@inheritDoc}
     * @throws Exception\InvalidArgumentException for invalid identifier types
     * @throws Exception\InvalidArgumentException for invalid event types
     */
    public function attach($identifier, $eventName, callable $listener, $priority = 1)
    {
        if (! is_string($identifier) || empty($identifier)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid identifier provided; must be a string; received "%s"',
                gettype($identifier)
            ));
        }

        if (! is_string($eventName) || empty($eventName)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid event provided; must be a non-empty string; received "%s"',
                gettype($eventName)
            ));
        }

        $this->identifiers[$identifier][$eventName][(int) $priority][0][] = $listener;
    }

    /**
     * {@inheritDoc}
     * @param bool $force Internal; allows recursing when detaching wildcard listeners
     * @throws Exception\InvalidArgumentException for invalid identifier types
     * @throws Exception\InvalidArgumentException for invalid event name types
     */
    public function detach(callable $listener, $identifier = null, $eventName = null, $force = false)
    {
        // No identifier or wildcard identifier: loop through all identifiers and detach
        if (null === $identifier || ('*' === $identifier && ! $force)) {
            foreach (array_keys($this->identifiers) as $identifier) {
                $this->detach($listener, $identifier, $eventName, true);
            }
            return;
        }

        if (! is_string($identifier) || empty($identifier)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid identifier provided; must be a string, received %s',
                gettype($identifier)
            ));
        }

        // Do we have any listeners on the provided identifier?
        if (! isset($this->identifiers[$identifier])) {
            return;
        }

        if (null === $eventName || ('*' === $eventName && ! $force)) {
            foreach (array_keys($this->identifiers[$identifier]) as $eventName) {
                $this->detach($listener, $identifier, $eventName, true);
            }
            return;
        }

        if (! is_string($eventName) || empty($eventName)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid event name provided; must be a string, received %s',
                gettype($eventName)
            ));
        }

        if (! isset($this->identifiers[$identifier][$eventName])) {
            return;
        }

        foreach ($this->identifiers[$identifier][$eventName] as $priority => $listOfListeners) {
            foreach ($listOfListeners[0] as $index => $evaluatedListener) {
                if ($evaluatedListener !== $listener) {
                    continue;
                }

                // Found the listener; remove it.
                unset($this->identifiers[$identifier][$eventName][$priority][0][$index]);

                // Is the priority queue empty?
                if (empty($this->identifiers[$identifier][$eventName][$priority][0])) {
                    unset($this->identifiers[$identifier][$eventName][$priority]);
                    break;
                }
            }

            // Is the event queue empty?
            if (empty($this->identifiers[$identifier][$eventName])) {
                unset($this->identifiers[$identifier][$eventName]);
                break;
            }
        }

        // Is the identifier queue now empty? Remove it.
        if (empty($this->identifiers[$identifier])) {
            unset($this->identifiers[$identifier]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getListeners(array $identifiers, $eventName)
    {
        return $this->getListenersForEventByPriority($eventName, $identifiers);
    }

    /**
     * {@inheritDoc}
     */
    public function clearListeners($identifier, $eventName = null)
    {
        if (! isset($this->identifiers[$identifier])) {
            return false;
        }

        if (null === $eventName) {
            unset($this->identifiers[$identifier]);
            return;
        }

        if (! isset($this->identifiers[$identifier][$eventName])) {
            return;
        }

        unset($this->identifiers[$identifier][$eventName]);
    }

    /**
     * @param  mixed $event Event to validate
     * @param  string $method Method name invoking this one
     * @return void
     * @throws Exception\InvalidArgumentException for invalid event types
     */
    private function validateEventForListenerRetrieval($event, $method)
    {
        if (is_object($event)) {
            return;
        }

        if (is_string($event) && '*' !== $event && ! empty($event)) {
            return;
        }

        throw new Exception\InvalidArgumentException(sprintf(
            'Event name passed to %s must be a non-empty, non-wildcard string or an object',
            $method
        ));
    }

    /**
     * Deduplicate identifiers, and ensure wildcard identifier is last.
     *
     * @return string[]
     */
    private function normalizeIdentifierList(array $identifiers)
    {
        $identifiers = array_unique($identifiers);
        if (false !== ($index = array_search('*', $identifiers, true))) {
            unset($identifiers[$index]);
        }
        array_push($identifiers, '*');
        return $identifiers;
    }

    /**
     * @param  string|object $event
     * @return string[]
     */
    private function getEventList($event)
    {
        if (is_string($event)) {
            return [$event, '*'];
        }

        return is_callable([$event, 'getName'])
            ? [$event->getName(), get_class($event), '*']
            : [get_class($event), '*'];
    }

    /**
     * @todo   Use `yield from` once we bump the minimum supported PHP version to 7+.
     * @param  array $prioritizedListeners
     * @return iterable
     */
    private function iterateByPriority($prioritizedListeners)
    {
        krsort($prioritizedListeners);
        foreach ($prioritizedListeners as $listeners) {
            // @todo `yield from $listeners`
            foreach ($listeners as $listener) {
                yield $listener;
            }
        }
    }
}
