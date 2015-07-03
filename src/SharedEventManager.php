<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\EventManager;

/**
 * Shared/contextual EventManager
 *
 * Allows attaching to EMs composed by other classes without having an instance first.
 * The assumption is that the SharedEventManager will be injected into EventManager
 * instances, and then queried for additional listeners when triggering an event.
 */
class SharedEventManager implements
    SharedEventAggregateAwareInterface,
    SharedEventManagerInterface
{
    /**
     * Identifiers with event connections
     * @var array
     */
    protected $identifiers = [];

    /**
     * Attach a listener to an event
     *
     * Allows attaching a listener to an event offered by one or more
     * identifying components. As an example, the following connects to the
     * "getAll" event of both an AbstractResource and EntityResource:
     *
     * <code>
     * $sharedEventManager = new SharedEventManager();
     * $sharedEventManager->attach(
     *     array('My\Resource\AbstractResource', 'My\Resource\EntityResource'),
     *     'getAll',
     *     function ($e) use ($cache) {
     *         if (!$id = $e->getParam('id', false)) {
     *             return;
     *         }
     *         if (!$data = $cache->load(get_class($resource) . '::getOne::' . $id )) {
     *             return;
     *         }
     *         return $data;
     *     }
     * );
     * </code>
     *
     * @param  string|array $id Identifier(s) for event emitting component(s)
     * @param  string $event
     * @param  callable $listener Listener that will handle the event.
     * @param  int $priority Priority at which listener should execute
     * @return void
     */
    public function attach($id, $event, callable $listener, $priority = 1)
    {
        $ids = (array) $id;
        foreach ($ids as $id) {
            if (! isset($this->identifiers[$id][$event])) {
                $this->identifiers[$id][$event] = [];
            }
            $listeners[] = $this->identifiers[$id][$event][] = [
                'listener' => $listener,
                'priority' => $priority,
            ];
        }
    }

    /**
     * Attach a listener aggregate
     *
     * Listener aggregates accept an EventManagerInterface instance, and call attachShared()
     * one or more times, typically to attach to multiple events using local
     * methods.
     *
     * @param  SharedListenerAggregateInterface $aggregate
     * @param  int $priority If provided, a suggested priority for the aggregate to use
     * @return mixed return value of {@link ListenerAggregateInterface::attachShared()}
     */
    public function attachAggregate(SharedListenerAggregateInterface $aggregate, $priority = 1)
    {
        return $aggregate->attachShared($this, $priority);
    }

    /**
     * Retrieve all registered events for a given resource
     *
     * @param  string|int $id
     * @return array
     */
    public function getEvents($id)
    {
        if (! array_key_exists($id, $this->identifiers)) {
            // Check if there are any identifier wildcard listeners
            if ('*' != $id && array_key_exists('*', $this->identifiers)) {
                return array_keys($this->identifiers['*']);
            }
            return false;
        }
        return array_keys($this->identifiers[$id]);
    }

    /**
     * Retrieve all listeners for a given identifier and event
     *
     * @param  string $id
     * @param  string $event
     * @return array[]
     */
    public function getListeners($id, $event = null)
    {
        if (! array_key_exists($id, $this->identifiers)) {
            return [];
        }

        if (! $event) {
            return $this->identifiers[$id];
        }

        if (! isset($this->identifiers[$id][$event])) {
            return [];
        }

        return $this->identifiers[$id][$event];
    }

    /**
     * Clear all listeners for a given identifier, optionally for a specific event
     *
     * @param  string|int $id
     * @param  null|string $event
     * @return bool
     */
    public function clearListeners($id, $event = null)
    {
        if (! array_key_exists($id, $this->identifiers)) {
            return false;
        }

        if (null === $event) {
            unset($this->identifiers[$id]);
            return true;
        }

        if (! isset($this->identifiers[$id][$event])) {
            return true;
        }

        unset($this->identifiers[$id][$event]);
        return true;
    }
}
