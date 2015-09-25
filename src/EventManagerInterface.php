<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace Zend\EventManager;

use Traversable;

/**
 * Interface for messengers
 */
interface EventManagerInterface extends SharedEventsCapableInterface
{
    /**
     * Create and trigger an event.
     *
     * Use this method when you do not want to create an EventInterface
     * instance prior to triggering. You will be required to pass:
     *
     * - the event name
     * - the event target (can be null)
     * - any event parameters you want to provide (empty array by default)
     *
     * It will create the Event instance for you and then trigger all listeners
     * related to the event.
     *
     * @param  string $event
     * @param  null|object|string $target
     * @param  array|object $argv
     * @return ResponseCollection
     */
    public function trigger($event, $target = null, $argv = []);

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
     * It will create the Event instance for you, and trigger all listeners
     * related to the event.
     *
     * The result of each listener is passed to $callback; if $callback returns
     * a boolean true value, the manager must short-circuit listener execution.
     *
     * @param  callable $callback
     * @param  string $event
     * @param  null|object|string $target
     * @param  array|object $argv
     * @return ResponseCollection
     */
    public function triggerUntil(callable $callback, $event, $target = null, $argv = []);

    /**
     * Trigger an event
     *
     * Provided an EventInterface instance, this method will trigger listeners
     * based on the event name, raising an exception if the event name is missing.
     *
     * @param  EventInterface $event
     * @return ResponseCollection
     */
    public function triggerEvent(EventInterface $event);

    /**
     * Trigger an event, applying a callback to each listener result.
     *
     * Provided an EventInterface instance, this method will trigger listeners
     * based on the event name, raising an exception if the event name is missing.
     *
     * The result of each listener is passed to $callback; if $callback returns
     * a boolean true value, the manager must short-circuit listener execution.
     *
     * @param  callable $callback
     * @param  EventInterface $event
     * @return ResponseCollection
     */
    public function triggerEventUntil(callable $callback, EventInterface $event);

    /**
     * Attach a listener to an event
     *
     * @param  string $event Event to which to listen.
     * @param  callable $listener
     * @param  int $priority Priority at which to register listener.
     */
    public function attach($event, callable $listener, $priority = 1);

    /**
     * Detach a listener.
     *
     * If no $event or '*' is provided, detaches listener from all events;
     * otherwise, detaches only from the named event.
     *
     * @param callable $listener
     * @param null|string $event Event from which to detach; null and '*'
     *     indicate all events.
     */
    public function detach(callable $listener, $event = null);

    /**
     * Clear all listeners for a given event
     *
     * @param  string $event
     * @return void
     */
    public function clearListeners($event);

    /**
     * Provide an event prototype to use with trigger().
     *
     * When `trigger()` needs to create an event instance, it should clone the
     * prototype provided to this method.
     *
     * @param  EventInterface $prototype
     * @return EventManagerInterface
     */
    public function setEventPrototype(EventInterface $prototype);

    /**
     * Get the identifier(s) for this EventManager
     *
     * @return array
     */
    public function getIdentifiers();

    /**
     * Set the identifiers (overrides any currently set identifiers)
     *
     * @param string[] $identifiers
     * @return EventManagerInterface
     */
    public function setIdentifiers(array $identifiers);

    /**
     * Add identifier(s) (appends to any currently set identifiers)
     *
     * @param string[] $identifiers
     * @return EventManagerInterface
     */
    public function addIdentifiers(array $identifiers);

    /**
     * Attach a listener aggregate
     *
     * @param  ListenerAggregateInterface $aggregate
     * @param  int $priority If provided, a suggested priority for the aggregate to use
     * @return mixed return value of {@link ListenerAggregateInterface::attach()}
     */
    public function attachAggregate(ListenerAggregateInterface $aggregate, $priority = 1);

    /**
     * Detach a listener aggregate.
     *
     * Should delegate to the aggregate's detach() method.
     *
     * @param  ListenerAggregateInterface $aggregate
     */
    public function detachAggregate(ListenerAggregateInterface $aggregate);
}
