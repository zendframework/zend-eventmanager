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
     * @param  string|array $ids Identifier(s) for event emitting component(s)
     * @param  string $event
     * @param  callable $listener Listener that will handle the event.
     * @param  int $priority Priority at which listener should execute
     * @return void
     * @throws Exception\InvalidArgumentException for invalid identifier arguments.
     * @throws Exception\InvalidArgumentException for invalid event arguments.
     */
    public function attach($ids, $event, callable $listener, $priority = 1)
    {
        if ($ids instanceof Traversable) {
            $ids = iterator_to_array($ids);
        }

        if (is_string($ids) && ! empty($ids)) {
            $ids = (array) $ids;
        }

        if (! is_array($ids)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid identifier(s) provided; must be a string, or an array/Traversable of strings; '
                . 'received "%s"',
                (is_object($ids) ? get_class($ids) : gettype($ids))
            ));
        }

        if (! is_string($event) || empty($event)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid event provided; must be a non-empty string; received "%s"',
                (is_object($ids) ? get_class($ids) : gettype($ids))
            ));
        }

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
        $events = array_merge(
            $this->getEventsForIdentifier($id),
            $this->getEventsForWildcardIdentifiers()
        );
        return array_unique($events);
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

        if ('*' === $event) {
            return $this->getListenersForEvent($id, '*');
        }

        return array_merge(
            $this->getListenersForEvent($id, $event),
            $this->getListenersForEvent($id, '*')
        );
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

    /**
     * Retrieve unique events for a given identifier.
     *
     * Removes the wildcard event from the list, if present.
     *
     * @param string $id
     * @return array
     */
    private function getEventsForIdentifier($id)
    {
        if (! isset($this->identifiers[$id])) {
            return [];
        }

        $events = array_keys($this->identifiers[$id]);
        $events = array_flip($events);
        if (isset($events['*'])) {
            unset($events['*']);
        }
        return array_keys($events);
    }

    /**
     * Retrieves set of named events from wildcard identifiers.
     *
     * @return array
     */
    private function getEventsForWildcardIdentifiers()
    {
        if (! isset($this->identifiers['*'])) {
            return [];
        }

        return $this->getEventsForIdentifier('*');
    }

    /**
     * Retrieve all listeners for a given identifier and event.
     *
     * @param string $id
     * @param string $event
     * @return array
     */
    private function getListenersForEvent($id, $event)
    {
        if (! isset($this->identifiers[$id][$event])) {
            return [];
        }

        return $this->identifiers[$id][$event];
    }
}
