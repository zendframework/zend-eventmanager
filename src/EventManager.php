<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace Zend\EventManager;

use ArrayAccess;
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
     * @var array[]
     */
    protected $events = [];

    /**
     * @var EventInterface Prototype to use when creating an event at trigger().
     */
    protected $eventPrototype;

    /**
     * Identifiers, used to pull shared signals from SharedEventManagerInterface instance
     * @var array
     */
    protected $identifiers = [];

    /**
     * Cached list of shared listeners already attached to this instance.
     * @var array
     */
    protected $sharedListeners = [];

    /**
     * Shared event manager
     * @var false|null|SharedEventManagerInterface
     */
    protected $sharedManager = null;

    /**
     * Constructor
     *
     * Allows optionally specifying identifier(s) to use to pull signals from a
     * SharedEventManagerInterface.
     *
     * @param array $identifiers
     * @param SharedEventManagerInterface $sharedEventManager
     */
    public function __construct(array $identifiers = null, SharedEventManagerInterface $sharedEventManager = null)
    {
        if ($sharedEventManager) {
            $this->sharedManager = $sharedEventManager;
        }

        if ($identifiers !== null) {
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
     * Get the identifier(s) for this EventManager
     *
     * @return array
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    /**
     * Set the identifiers (overrides any currently set identifiers)
     *
     * @param  string[] $identifiers
     * @return void
     */
    public function setIdentifiers(array $identifiers)
    {
        $this->identifiers = array_unique($identifiers);
    }

    /**
     * Add identifiers (merges to any currently set identifiers)
     *
     * @param  string[] $identifiers
     * @return void
     * @throws Exception\RuntimeException if called more than once.
     */
    public function addIdentifiers(array $identifiers)
    {
        $this->identifiers = array_unique(array_merge(
            $this->identifiers,
            $identifiers
        ));
    }

    /**
     * Trigger all listeners for a given event
     *
     * @param  string $event Name of event to trigger
     * @param  null|string|object $target Object calling emit, or symbol
     *     describing target (such as static method name)
     * @param  array|ArrayAccess $argv Array of arguments; typically, should be
     *     associative
     * @return ResponseCollection All listener return values
     */
    public function trigger($event, $target = null, $argv = [])
    {
        $e = clone $this->eventPrototype;
        $e->setName($event);
        $e->setTarget($target);
        $e->setParams($argv);

        return $this->triggerListeners($e);
    }

    /**
     * Create and trigger an event, applying a callback to each listener result.
     *
     * Use this method when you do not want to create an EventInterface
     * instance prior to triggering. You will be required to pass:
     *
     * - the event name
     * - the event target (can be null)
     * - any event parameters you want to provide (empty array by default)
     *
     * It will create the Event instance for you, passing that and the
     * $callback to `triggerEventUntil()`.
     *
     * @param  callable $callback
     * @param  string $event
     * @param  null|object|string $target
     * @param  array|object $argv
     * @return ResponseCollection
     */
    public function triggerUntil(callable $callback, $event, $target = null, $argv = [])
    {
        $e = clone $this->eventPrototype;
        $e->setName($event);
        $e->setTarget($target);
        $e->setParams($argv);

        return $this->triggerListeners($e, $callback);
    }

    /**
     * Trigger an event
     *
     * Provided an EventInterface instance, this method will trigger listeners
     * based on the event name, raising an exception if the event name is missing.
     *
     * @param  EventInterface $event
     * @return ResponseCollection
     */
    public function triggerEvent(EventInterface $event)
    {
        return $this->triggerListeners($event);
    }

    /**
     * Trigger an event, applying a callback to each listener result.
     *
     * Provided an EventInterface instance, this method will trigger listeners
     * based on the event name, raising an exception if the event name is missing.
     *
     * Each result returned by a listener is passed to $callback; if $callback
     * returns a boolean true value, the manager should short-circuit execution
     * of the listener queue.
     *
     * @param  callable $callback
     * @param  EventInterface $event
     * @return ResponseCollection
     */
    public function triggerEventUntil(callable $callback, EventInterface $event)
    {
        return $this->triggerListeners($event, $callback);
    }

    /**
     * Attach a listener to an event
     *
     * The first argument is the event, and the next argument is a
     * callable that will respond to that event.
     *
     * The last argument indicates a priority at which the event should be
     * executed; by default, this value is 1; however, you may set it for any
     * integer value. Higher values have higher priority (i.e., execute first).
     *
     * You can specify "*" for the event name. In such cases, the listener will
     * be triggered for every event *that has registered listeners at the time
     * it is attached*. As such, register wildcard events last whenever possible!
     *
     * @param  string $event Event to which to attach.
     * @param  callable $listener Event listener.
     * @param  int $priority If provided, the priority at which to register the
     *     listener.
     * @return callable Returns the listener.
     * @throws Exception\InvalidArgumentException
     */
    public function attach($event, callable $listener, $priority = 1)
    {
        if (! is_string($event)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string for the event; received %s',
                __METHOD__,
                (is_object($event) ? get_class($event) : gettype($event))
            ));
        }

        $this->events[$event][((int) $priority) . '.0'][] = $listener;
        return $listener;
    }

    /**
     * @inheritDoc
     * @throws Exception\InvalidArgumentException for invalid event types.
     */
    public function detach(callable $listener, $event = null)
    {
        if (null === $event || '*' === $event) {
            $this->detachWildcardListener($listener);
            foreach (array_keys($this->events) as $event) {
                $this->detach($listener, $event);
            }
            return;
        }

        if (! is_string($event)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string for the event; received %s',
                __METHOD__,
                (is_object($event) ? get_class($event) : gettype($event))
            ));
        }

        if (! isset($this->events[$event])) {
            return;
        }

        // Remove all instances from queue.
        $queue = $this->events[$event];
        while ($queue->contains($listener)) {
            $queue->remove($listener);
        }
    }

    /**
     * Attach a listener aggregate
     *
     * Listener aggregates accept an EventManagerInterface instance, and call attach()
     * one or more times, typically to attach to multiple events using local
     * methods.
     *
     * @param  ListenerAggregateInterface $aggregate
     * @param  int $priority If provided, a suggested priority for the aggregate to use
     * @return void
     */
    public function attachAggregate(ListenerAggregateInterface $aggregate, $priority = 1)
    {
        $aggregate->attach($this, $priority);
    }

    /**
     * @inheritDoc
     */
    public function detachAggregate(ListenerAggregateInterface $aggregate)
    {
        $aggregate->detach($this);
    }

    /**
     * Clear all listeners for a given event
     *
     * @param  string $event
     * @return void
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

            // If the event was asked to stop propagating or causes our validation callback to
            // return true, stop propagation
            if ($event->propagationIsStopped() || ($callback && $callback($response))) {
                $responses->setStopped(true);
                break;
            }
        }

        return $responses;
    }

    /**
     * Get listeners for the currently triggered event.
     *
     * If we have listeners defined for this specific event already, we can
     * return them directly; however, if not, we need to attach wildcard
     * listeners, as we have an event with no dedicated listeners.
     *
     * @param  string $eventName
     * @return callable[]
     */
    private function getListenersByEventName($eventName)
    {
        $listeners = array_merge_recursive(
            isset($this->events[$eventName]) ? $this->events[$eventName] : [],
            isset($this->events['*']) ? $this->events['*'] : [],
            $this->sharedManager ? $this->sharedManager->getListenersByIdentifiers($this->identifiers, $eventName) : []
        );

        krsort($listeners, SORT_NUMERIC);

        foreach ($listeners as $priority => $listenersByPriority) {
            foreach ($listenersByPriority as $listener) {
                yield $listener;
            }
        }
    }
}
