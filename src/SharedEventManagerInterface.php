<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\EventManager;

use Zend\Stdlib\PriorityQueue;

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
     * Attach a listener to an event
     *
     * @param  string|string[] $id Identifier(s) for event emitting component(s)
     * @param  string $event
     * @param  callable $listener Listener that will handle the event.
     * @param  int $priority Priority at which listener should execute
     */
    public function attach($id, $event, callable $listener, $priority = 1);

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
