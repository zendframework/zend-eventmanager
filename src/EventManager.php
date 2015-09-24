<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\EventManager;

use ArrayAccess;
use ArrayObject;
use Traversable;
use Zend\Stdlib\FastPriorityQueue;

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
     * @var FastPriorityQueue[]
     */
    protected $events = [];

    /**
     * @var string Class representing the event being emitted
     */
    protected $eventClass = 'Zend\EventManager\Event';

    /**
     * Identifiers, used to pull shared signals from SharedEventManagerInterface instance
     * @var array
     */
    protected $identifiers = [];

    /**
     * Have shared/wildcard listeners been prepared already?
     *
     * @var bool
     */
    private $isPrepared = false;

    /**
     * Shared event manager
     * @var false|null|SharedEventManagerInterface
     */
    protected $sharedManager = null;

    /**
     * @var array List of wildcard listeners.
     */
    private $wildcardListeners = [];

    /**
     * Constructor
     *
     * Allows optionally specifying identifier(s) to use to pull signals from a
     * SharedEventManagerInterface.
     *
     * @param  null|string|int|array|Traversable $identifiers
     */
    public function __construct($identifiers = null, SharedEventManagerInterface $sharedEventManager = null)
    {
        if ($sharedEventManager) {
            $this->sharedManager = $sharedEventManager;
        }

        if ($identifiers !== null) {
            $this->setIdentifiers($identifiers);
        }
    }

    /**
     * Set the event class to utilize
     *
     * @param  string $class
     * @return EventManager
     */
    public function setEventClass($class)
    {
        $this->eventClass = $class;
        return $this;
    }

    /**
     * Set shared event manager
     *
     * @param SharedEventManagerInterface $sharedEventManager
     * @return EventManager
     */
    public function setSharedManager(SharedEventManagerInterface $sharedEventManager)
    {
        $this->sharedManager = $sharedEventManager;
        return $this;
    }

    /**
     * @return null|SharedEventManagerInterface
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
     * @param string[] $identifiers
     * @return EventManager Provides a fluent interface
     */
    public function setIdentifiers(array $identifiers)
    {
        if ($this->isPrepared) {
            throw new Exception\RuntimeException(sprintf(
                '%s cannot be called after any events have been triggered',
                __METHOD__
            ));
        }

        $this->identifiers = array_unique($identifiers);

        return $this;
    }

    /**
     * Add identifiers (merges to any currently set identifiers)
     *
     * @param string[] $identifiers
     * @return EventManager Provides a fluent interface
     * @throws Exception\RuntimeException if called more than once.
     */
    public function addIdentifiers(array $identifiers)
    {
        if ($this->isPrepared) {
            throw new Exception\RuntimeException(sprintf(
                '%s cannot be called after any events have been triggered',
                __METHOD__
            ));
        }

        $this->identifiers = array_unique(array_merge(
            $this->identifiers,
            $identifiers
        ));

        return $this;
    }

    /**
     * Trigger all listeners for a given event
     *
     * @param  string|EventInterface $event
     * @param  string|object     $target   Object calling emit, or symbol describing target (such as static method name)
     * @param  array|ArrayAccess $argv     Array of arguments; typically, should be associative
     * @param  null|callable     $callback Trigger listeners until return value of this callback evaluate to true
     * @return ResponseCollection All listener return values
     * @throws Exception\InvalidCallbackException
     */
    public function trigger($event, $target = null, $argv = [], callable $callback = null)
    {
        if (! $this->isPrepared) {
            $this->prepareListeners();
        }

        if ($event instanceof EventInterface) {
            $e        = $event;
            $event    = $e->getName();
            $callback = $target;
        } elseif ($target instanceof EventInterface) {
            $e = $target;
            $e->setName($event);
            $callback = empty($argv) ? null : $argv;
        } elseif ($argv instanceof EventInterface) {
            $e = $argv;
            $e->setName($event);
            $e->setTarget($target);
        } else {
            $e = new $this->eventClass();
            $e->setName($event);
            $e->setTarget($target);
            $e->setParams($argv);
        }

        if ($callback && ! is_callable($callback)) {
            throw new Exception\InvalidCallbackException('Invalid callback provided');
        }

        // Initial value of stop propagation flag should be false
        $e->stopPropagation(false);

        return $this->triggerListeners($e, $callback);
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

        // Is this the wildcard event? If so, add the listener to the wildcard
        // list with its priority, to inject later.
        if ('*' === $event) {
            $struct = [
                'listener' => $listener,
                'priority' => $priority,
            ];
            $this->wildcardListeners[] = $struct;

            // Attaching after first trigger requires special handling.
            if ($this->isPrepared) {
                $this->prepareWildcardListeners($this->getEvents(), [ $struct ]);
            }

            return $listener;
        }

        // If we don't have a priority queue for the event yet, create one
        if (! isset($this->events[$event])) {
            $this->events[$event] = new FastPriorityQueue();
        }

        // Inject the listener into the queue
        $this->events[$event]->insert($listener, $priority);

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
            foreach ($this->getEvents() as $event) {
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
     * Retrieve all registered events
     *
     * @return array
     */
    public function getEvents()
    {
        return array_keys($this->events);
    }

    /**
     * Retrieve all listeners for a given event
     *
     * @param  string $event
     * @return FastPriorityQueue
     */
    public function getListeners($event)
    {
        if (! array_key_exists($event, $this->events)) {
            return new FastPriorityQueue();
        }

        return $this->events[$event];
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
        $responses = new ResponseCollection;

        foreach ($this->getListenersForEvent($event->getName()) as $listener) {
            // Trigger the listener, and push its result onto the response collection
            $response = call_user_func($listener, $event);
            $responses->push($response);

            // If the event was asked to stop propagating, do so
            if ($event->propagationIsStopped()) {
                $responses->setStopped(true);
                break;
            }

            // If the result causes our validation callback to return true,
            // stop propagation
            if ($callback && call_user_func($callback, $response)) {
                $responses->setStopped(true);
                break;
            }
        }

        return $responses;
    }

    /**
     * Prepare listeners.
     *
     * Attaches all listeners from the shared event manager to the current
     * instance by:
     *
     * - Looping through identifiers in this instance, and attaching any
     *   listeners from the shared manager on the given identifier.
     * - Looping through shared listeners on the wildcard identifier.
     * - Looping through any listeners on wildcard events and attaching them.
     *
     * This method is only called once per instance, the first time any event
     * is triggered; as such, all shared and wildcard listeners MUST be
     * injected BEFORE the first trigger.
     */
    private function prepareListeners()
    {
        if ($this->sharedManager) {
            $this->attachSharedListeners();
        }

        $this->prepareWildcardListeners($this->getEvents(), $this->wildcardListeners);

        $this->isPrepared = true;
    }

    /**
     * Attach shared listeners.
     *
     * Attaches shared listeners for identifiers in the current instance, as
     * well as any on the wildcard listener.
     */
    private function attachSharedListeners()
    {
        foreach ($this->identifiers as $identifier) {
            foreach ($this->sharedManager->getListeners($identifier) as $event => $listeners) {
                $this->attachListenerStructs($event, $listeners);
            }
        }

        foreach ($this->sharedManager->getListeners('*') as $event => $listeners) {
            $this->attachListenerStructs($event, $listeners);
        }
    }

    /**
     * Attach listener structs to a given event.
     *
     * Loops through each listener struct, attaching the listener at the given
     * priority to the specified event.
     *
     * @param string $event
     * @param array $listeners
     */
    private function attachListenerStructs($event, array $listeners)
    {
        foreach ($listeners as $struct) {
            $this->attach($event, $struct['listener'], $struct['priority']);
        }
    }

    /**
     * Inject wildcard listeners.
     *
     * Loops through each event, injecting each wildcard listener available.
     *
     * @param array $events
     * @param array $listeners
     */
    private function prepareWildcardListeners(array $events, array $listeners)
    {
        foreach ($events as $event) {
            $this->attachListenerStructs($event, $listeners);
        }
    }

    /**
     * Get listeners for the currently triggered event.
     *
     * If we have listeners defined for this specific event already, we can
     * return them directly; however, if not, we need to attach wildcard
     * listeners, as we have an event with no dedicated listeners.
     *
     * @param  string $event
     * @return FastPriorityQueue
     */
    private function getListenersForEvent($event)
    {
        if (isset($this->events[$event])) {
            return $this->events[$event];
        }

        $this->events[$event] = new FastPriorityQueue();
        $this->prepareWildcardListeners([$event], $this->wildcardListeners);
        return $this->events[$event];
    }

    /**
     * Detach a wildcard listener
     *
     * @param callable $listener
     */
    private function detachWildcardListener(callable $listener)
    {
        foreach ($this->wildcardListeners as $index => $struct) {
            if ($listener === $struct['listener']) {
                unset($this->wildcardListeners[$index]);
            }
        }
    }
}
