<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace Zend\EventManager;

/**
 * Interface for shared event listener collections
 */
interface SharedEventManagerInterface
{
    /**
     * Retrieve all listeners for a given identifier and event
     *
     * @param  string $id
     * @param  null|string $event
     * @return false|array
     */
    public function getListeners($id, $event = null);

    /**
     * Attach a listener to an event emitted by components with specific identifiers.
     *
     * @param  string $id Identifier for event emitting component
     * @param  string $event
     * @param  callable $listener Listener that will handle the event.
     * @param  int $priority Priority at which listener should execute
     */
    public function attach($id, $event, callable $listener, $priority = 1);

    /**
     * Detach a shared listener.
     *
     * Allows detaching a listener from one or more events to which it may be
     * attached.
     *
     * @param  callable $listener Listener to detach.
     * @param  null|string $id Identifier from which to detach; null indicates
     *      all registered identifiers.
     * @param  null|string $event Event from which to detach; null indicates
     *      all registered events.
     * @return void
     * @throws Exception\InvalidArgumentException for invalid identifier arguments.
     * @throws Exception\InvalidArgumentException for invalid event arguments.
     */
    public function detach(callable $listener, $id = null, $event = null);

    /**
     * Retrieve all registered events for a given resource
     *
     * @param  string $id
     * @return array
     */
    public function getEvents($id);

    /**
     * Clear all listeners for a given identifier, optionally for a specific event
     *
     * @param  string $id
     * @param  null|string $event
     * @return bool
     */
    public function clearListeners($id, $event = null);
}
