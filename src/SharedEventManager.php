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
    public function detach(callable $listener, $identifier = null, $eventName = null)
    {
        // If event is wildcard, we need to iterate through each listeners
        if ($eventName === null || $eventName === '*') {
            foreach ($this->identifiers as $currentIdentifier => &$listenersByIdentifiers) {
                if ($identifier !== null && $identifier !== '*' && $currentIdentifier !== $identifier) {
                    continue;
                }

                foreach ($listenersByIdentifiers as &$listenersByEvent) {
                    foreach ($listenersByEvent as &$listenersByPriority) {
                        foreach ($listenersByPriority as $key => $currentListener) {
                            if ($currentListener === $listener) {
                                unset($listenersByPriority[$key]);
                            }
                        }
                    }
                }
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

        // @TODO: do logic
    }

    /**
     * Retrieve all listeners for a given identifier and event
     *
     * @param  array $identifiers
     * @param  string $event
     * @return array[]
     */
    public function getListeners(array $identifiers, $event = null)
    {
        $listeners = [];

        foreach ($identifiers as $identifier) {
            $listenersByIdentifier = isset($this->identifiers[$identifier]) ? $this->identifiers[$identifier] : [];

            $listeners = array_merge_recursive(
                $listeners,
                isset($listenersByIdentifier[$event]) ? $listenersByIdentifier[$event] : [],
                isset($listenersByIdentifier['*']) ? $listenersByIdentifier['*'] : []
            );
        }

        if (isset($this->identifiers['*'])) {
            $wildcardIdentifier = $this->identifiers['*'];

            $listeners = array_merge_recursive(
                $listeners,
                isset($wildcardIdentifier[$event]) ? $wildcardIdentifier[$event] : [],
                isset($wildcardIdentifier['*']) ? $wildcardIdentifier['*'] : []
            );
        }

        return $listeners;
    }

    /**
     * @inheritDoc
     */
    public function clearListeners($identifier, $event = null)
    {
        if (! array_key_exists($identifier, $this->identifiers)) {
            return false;
        }

        if (null === $event) {
            unset($this->identifiers[$identifier]);
            return true;
        }

        if (! isset($this->identifiers[$identifier][$event])) {
            return true;
        }

        unset($this->identifiers[$identifier][$event]);

        return true;
    }
}
