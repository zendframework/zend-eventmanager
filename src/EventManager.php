<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace Zend\EventManager;

use ArrayObject;

/**
 * Event manager: notification system
 *
 * Use the EventManager when you want to create a per-instance notification
 * system for your objects.
 */
class EventManager implements EventManagerInterface
{
    /**
     * Subscribed events and their listeners
     *
     * @var array[]
     */
    protected $events = [];

    /**
     * @var EventInterface Prototype to use when creating an event at trigger().
     */
    protected $eventPrototype;

    /**
     * Identifiers, used to pull shared signals from SharedEventManagerInterface instance
     *
     * @var array
     */
    protected $identifiers = [];

    /**
     * Shared event manager
     *
     * @var null|SharedEventManagerInterface
     */
    protected $sharedManager = null;

    /**
     * Constructor
     *
     * Allows optionally specifying identifier(s) to use to pull signals from a
     * SharedEventManagerInterface.
     *
     * @param SharedEventManagerInterface $sharedEventManager
     * @param array $identifiers
     */
    public function __construct(SharedEventManagerInterface $sharedEventManager = null, array $identifiers = [])
    {
        if ($sharedEventManager) {
            $this->sharedManager = $sharedEventManager;
            $this->setIdentifiers($identifiers);
        }

        $this->eventPrototype = new Event();
    }

    /**
     * @inheritDoc
     */
    public function setEventPrototype(EventInterface $prototype)
    {
        $this->eventPrototype = $prototype;
    }

    /**
     * Retrieve the shared event manager, if composed.
     *
     * @return null|SharedEventManagerInterface $sharedEventManager
     */
    public function getSharedManager()
    {
        return $this->sharedManager;
    }

    /**
     * @inheritDoc
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    /**
     * @inheritDoc
     */
    public function setIdentifiers(array $identifiers)
    {
        $this->identifiers = array_unique($identifiers);
    }

    /**
     * @inheritDoc
     */
    public function addIdentifiers(array $identifiers)
    {
        $this->identifiers = array_unique(array_merge(
            $this->identifiers,
            $identifiers
        ));
    }

    /**
     * @inheritDoc
     */
    public function trigger($eventName, $target = null, $argv = [])
    {
        $event = clone $this->eventPrototype;
        $event->setName($eventName);
        $event->setTarget($target);
        $event->setParams($argv);

        return $this->triggerListeners($event);
    }

    /**
     * @inheritDoc
     */
    public function triggerUntil(callable $callback, $eventName, $target = null, $argv = [])
    {
        $event = clone $this->eventPrototype;
        $event->setName($eventName);
        $event->setTarget($target);
        $event->setParams($argv);

        return $this->triggerListeners($event, $callback);
    }

    /**
     * @inheritDoc
     */
    public function triggerEvent(EventInterface $event)
    {
        return $this->triggerListeners($event);
    }

    /**
     * @inheritDoc
     */
    public function triggerEventUntil(callable $callback, EventInterface $event)
    {
        return $this->triggerListeners($event, $callback);
    }

    /**
     * @inheritDoc
     */
    public function attach($eventName, callable $listener, $priority = 1)
    {
        if (! is_string($eventName)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string for the event; received %s',
                __METHOD__,
                (is_object($eventName) ? get_class($eventName) : gettype($eventName))
            ));
        }

        $this->events[$eventName][((int) $priority) . '.0'][] = $listener;

        return $listener;
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException for invalid event types.
     */
    public function detach(callable $listener, $eventName = null, $force = false)
    {

        // If event is wildcard, we need to iterate through each listeners
        if (null === $eventName || ('*' === $eventName && ! $force)) {
            foreach (array_keys($this->events) as $eventName) {
                $this->detach($listener, $eventName, true);
            }
            return;
        }

        if (! is_string($eventName)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string for the event; received %s',
                __METHOD__,
                (is_object($eventName) ? get_class($eventName) : gettype($eventName))
            ));
        }

        if (! isset($this->events[$eventName])) {
            return;
        }

        foreach ($this->events[$eventName] as $priority => $listeners) {
            foreach ($listeners as $index => $evaluatedListener) {
                if ($evaluatedListener !== $listener) {
                    continue;
                }

                // Found the listener; remove it.
                unset($this->events[$eventName][$priority][$index]);

                // If the queue for the given priority is empty, remove it.
                if (empty($this->events[$eventName][$priority])) {
                    unset($this->events[$eventName][$priority]);
                    break;
                }
            }

            // If the queue for the given event is empty, remove it.
            if (empty($this->events[$eventName])) {
                unset($this->events[$eventName]);
                break;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function clearListeners($event)
    {
        if (isset($this->events[$event])) {
            unset($this->events[$event]);
        }
    }

    /**
     * Prepare arguments
     *
     * Use this method if you want to be able to modify arguments from within a
     * listener. It returns an ArrayObject of the arguments, which may then be
     * passed to trigger().
     *
     * @param  array $args
     * @return ArrayObject
     */
    public function prepareArgs(array $args)
    {
        return new ArrayObject($args);
    }

    /**
     * Trigger listeners
     *
     * Actual functionality for triggering listeners, to which trigger() delegate.
     *
     * @param  EventInterface $event
     * @param  null|callable $callback
     * @return ResponseCollection
     */
    protected function triggerListeners(EventInterface $event, callable $callback = null)
    {
        $name = $event->getName();

        if (empty($name)) {
            throw new Exception\RuntimeException('Event is missing a name; cannot trigger!');
        }

        // Initial value of stop propagation flag should be false
        $event->stopPropagation(false);

        $responses = new ResponseCollection();

        foreach ($this->getListenersByEventName($name) as $listener) {
            $response = $listener($event);
            $responses->push($response);

            // If the event was asked to stop propagating, do so
            if ($event->propagationIsStopped()) {
                $responses->setStopped(true);
                break;
            }

            // If the result causes our validation callback to return true,
            // stop propagation
            if ($callback && $callback($response)) {
                $responses->setStopped(true);
                break;
            }
        }

        return $responses;
    }

    /**
     * Get listeners for the currently triggered event.
     *
     * @param  string $eventName
     * @return callable[]
     */
    private function getListenersByEventName($eventName)
    {
        $listeners = array_merge_recursive(
            isset($this->events[$eventName]) ? $this->events[$eventName] : [],
            isset($this->events['*']) ? $this->events['*'] : [],
            $this->sharedManager ? $this->sharedManager->getListeners($this->identifiers, $eventName) : []
        );

        krsort($listeners, SORT_NUMERIC);

        $listenersForEvent = [];

        foreach ($listeners as $priority => $listenersByPriority) {
            foreach ($listenersByPriority as $listener) {
                // Performance note: after some testing, it appears that accumulating listeners and sending
                // them at the end of the method is FASTER than using generators (ie. yielding)
                $listenersForEvent[] = $listener;
            }
        }

        return $listenersForEvent;
    }
}
