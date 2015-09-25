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
     * @param  string $id Identifier for event emitting component.
     * @param  string $event
     * @param  callable $listener Listener that will handle the event.
     * @param  int $priority Priority at which listener should execute
     * @return void
     * @throws Exception\InvalidArgumentException for invalid identifier arguments.
     * @throws Exception\InvalidArgumentException for invalid event arguments.
     */
    public function attach($id, $event, callable $listener, $priority = 1)
    {
        if (! is_string($id) || empty($id)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid identifier provided; must be a string; received "%s"',
                (is_object($id) ? get_class($id) : gettype($id))
            ));
        }

        if (! is_string($event) || empty($event)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid event provided; must be a non-empty string; received "%s"',
                (is_object($event) ? get_class($event) : gettype($event))
            ));
        }

        $this->identifiers[$id][$event][((int) $priority) . '.0'][] = $listener;
    }

    /**
     * @inheritDoc
     */
    public function detach(callable $listener, $id = null, $event = null)
    {
        if (null === $id) {
            foreach (array_keys($this->identifiers) as $id) {
                $this->detach($listener, $id, $event);
            }
            return;
        }

        if (! isset($this->identifiers[$id])) {
            return;
        }

        if (null === $event) {
            foreach (array_keys($this->identifiers[$id]) as $event) {
                $this->detach($listener, $id, $event);
            }
            return;
        }

        if (! isset($this->identifiers[$id][$event])) {
            return;
        }

        foreach ($this->identifiers[$id][$event] as $index => $compare) {
            if ($compare['listener'] === $listener) {
                unset($this->identifiers[$id][$event][$index]);
            }
        }
    }

    /**
     * Retrieve all listeners for a given identifier and event
     *
     * @param  array $identifiers
     * @param  string $event
     * @return array[]
     */
    public function getListenersByIdentifiers(array $identifiers, $event = null)
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
