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
 * Shared/contextual EventManager
 *
 * Allows attaching to EMs composed by other classes without having an instance first.
 * The assumption is that the SharedEventManager will be injected into EventManager
 * instances, and then queried for additional listeners when triggering an event.
 */
class SharedEventManager implements SharedEventManagerInterface
{
    /**
     * Identifiers with event connections
     * @var array
     */
    protected $identifiers = [];

    /**
     * Attach a listener to an event emitted by components with specific identifiers.
     *
     * Allows attaching a listener to an event offered by an identifying
     * components. As an example, the following connects to the "getAll" event
     * of both an AbstractResource and EntityResource:
     *
     * <code>
     * $sharedEventManager = new SharedEventManager();
     * foreach (['My\Resource\AbstractResource', 'My\Resource\EntityResource'] as $identifier) {
     *     $sharedEventManager->attach(
     *         $identifier,
     *         'getAll',
     *         function ($e) use ($cache) {
     *             if (!$id = $e->getParam('id', false)) {
     *                 return;
     *             }
     *             if (!$data = $cache->load(get_class($resource) . '::getOne::' . $id )) {
     *                 return;
     *             }
     *             return $data;
     *         }
     *     );
     * }
     * </code>
     *
     * @param  string $identifier Identifier for event emitting component.
     * @param  string $event
     * @param  callable $listener Listener that will handle the event.
     * @param  int $priority Priority at which listener should execute
     * @return void
     * @throws Exception\InvalidArgumentException for invalid identifier arguments.
     * @throws Exception\InvalidArgumentException for invalid event arguments.
     */
    public function attach($identifier, $event, callable $listener, $priority = 1)
    {
        if (! is_string($identifier) || empty($identifier)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid identifier provided; must be a string; received "%s"',
                (is_object($identifier) ? get_class($identifier) : gettype($identifier))
            ));
        }

        if (! is_string($event) || empty($event)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid event provided; must be a non-empty string; received "%s"',
                (is_object($event) ? get_class($event) : gettype($event))
            ));
        }

        $this->identifiers[$identifier][$event][((int) $priority) . '.0'][] = $listener;
    }

    /**
     * @inheritDoc
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
                (is_object($identifier) ? get_class($identifier) : gettype($identifier))
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
                (is_object($eventName) ? get_class($eventName) : gettype($eventName))
            ));
        }

        if (! isset($this->identifiers[$identifier][$eventName])) {
            return;
        }

        foreach ($this->identifiers[$identifier][$eventName] as $priority => $listeners) {
            foreach ($listeners as $index => $evaluatedListener) {
                if ($evaluatedListener !== $listener) {
                    continue;
                }

                // Found the listener; remove it.
                unset($this->identifiers[$identifier][$eventName][$priority][$index]);

                // Is the priority queue empty?
                if (empty($this->identifiers[$identifier][$eventName][$priority])) {
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
     * Retrieve all listeners for a given identifier and event
     *
     * @param  array $identifiers
     * @param  string $eventName
     * @return array[]
     * @throws Exception\InvalidArgumentException
     */
    public function getListeners(array $identifiers, $eventName)
    {
        if ('*' === $eventName || ! is_string($eventName) || empty($eventName)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Event name passed to %s must be a non-empty, non-wildcard string',
                __METHOD__
            ));
        }

        $listeners = [];

        foreach ($identifiers as $identifier) {
            if ('*' === $identifier || ! is_string($identifier) || empty($identifier)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Identifier names passed to %s must be non-empty, non-wildcard strings',
                    __METHOD__
                ));
            }

            $listenersByIdentifier = isset($this->identifiers[$identifier]) ? $this->identifiers[$identifier] : [];

            $listeners = array_merge_recursive(
                $listeners,
                isset($listenersByIdentifier[$eventName]) ? $listenersByIdentifier[$eventName] : [],
                isset($listenersByIdentifier['*']) ? $listenersByIdentifier['*'] : []
            );
        }

        if (isset($this->identifiers['*']) && ! in_array('*', $identifiers)) {
            $wildcardIdentifier = $this->identifiers['*'];

            $listeners = array_merge_recursive(
                $listeners,
                isset($wildcardIdentifier[$eventName]) ? $wildcardIdentifier[$eventName] : [],
                isset($wildcardIdentifier['*']) ? $wildcardIdentifier['*'] : []
            );
        }

        return $listeners;
    }

    /**
     * @inheritDoc
     */
    public function clearListeners($identifier, $eventName = null)
    {
        if (! array_key_exists($identifier, $this->identifiers)) {
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
}
