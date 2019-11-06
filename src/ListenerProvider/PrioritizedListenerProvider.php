<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace Zend\EventManager\ListenerProvider;

use Zend\EventManager\Exception;

class PrioritizedListenerProvider implements
    PrioritizedListenerAttachmentInterface,
    PrioritizedListenerProviderInterface
{
    /**
     * Subscribed events and their listeners
     *
     * STRUCTURE:
     * [
     *     <string name> => [
     *         <int priority> => [
     *             0 => [<callable listener>, ...]
     *         ],
     *         ...
     *     ],
     *     ...
     * ]
     *
     * NOTE:
     * This structure helps us to reuse the list of listeners
     * instead of first iterating over it and generating a new one
     * -> In result it improves performance by up to 25% even if it looks a bit strange
     *
     * @var array<string, array<int, callable[]>>
     */
    protected $events = [];

    /**
     * {@inheritDoc}
     */
    public function getListenersForEvent($event)
    {
        // @todo Use `yield from $this->iterateByPriority(...)`
        return $this->iterateByPriority(
            $this->getListenersForEventByPriority($event)
        );
    }

    /**
     * {@inheritDoc}
     * @param  string[] $identifiers Ignored in this implementation.
     * @throws Exception\InvalidArgumentException for invalid $event types.
     */
    public function getListenersForEventByPriority($event, array $identifiers = [])
    {
        if (! is_object($event)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects the $event argument to be an object; received %s',
                __METHOD__,
                gettype($event)
            ));
        }

        $identifiers = is_callable([$event, 'getName'])
            ? [$event->getName()]
            : [];
        $identifiers = array_merge($identifiers, [get_class($event), '*']);

        $prioritizedListeners = [];
        foreach ($identifiers as $name) {
            if (! isset($this->events[$name])) {
                continue;
            }

            foreach ($this->events[$name] as $priority => $listOfListeners) {
                $prioritizedListeners[$priority] = isset($prioritizedListeners[$priority])
                    ? array_merge($prioritizedListeners[$priority], $listOfListeners[0])
                    : $listOfListeners[0];
            }
        }

        return $prioritizedListeners;
    }

    /**
     * {@inheritDoc}
     * @throws Exception\InvalidArgumentException for invalid $event types.
     */
    public function attach($event, callable $listener, $priority = 1)
    {
        if (! is_string($event)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string for the event; received %s',
                __METHOD__,
                gettype($event)
            ));
        }

        $this->events[$event][(int) $priority][0][] = $listener;

        return $listener;
    }

    /**
     * {@inheritDoc}
     * @param bool $force Internal; used by attachWildcardListener to force
     *     removal of the '*' event.
     * @throws Exception\InvalidArgumentException for invalid event types.
     */
    public function detach(callable $listener, $event = null, $force = false)
    {
        if (null === $event || ('*' === $event && ! $force)) {
            $this->detachWildcardListener($listener);
            return;
        }

        if (! is_string($event)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string for the event; received %s',
                __METHOD__,
                gettype($event)
            ));
        }

        if (! isset($this->events[$event])) {
            return;
        }

        foreach ($this->events[$event] as $priority => $listeners) {
            foreach ($listeners[0] as $index => $evaluatedListener) {
                if ($evaluatedListener !== $listener) {
                    continue;
                }

                // Found the listener; remove it.
                unset($this->events[$event][$priority][0][$index]);

                // If the queue for the given priority is empty, remove it.
                if (empty($this->events[$event][$priority][0])) {
                    unset($this->events[$event][$priority]);
                    break;
                }
            }
        }

        // If the queue for the given event is empty, remove it.
        if (empty($this->events[$event])) {
            unset($this->events[$event]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function attachWildcardListener(callable $listener, $priority = 1)
    {
        $this->events['*'][(int) $priority][0][] = $listener;
        return $listener;
    }

    /**
     * {@inheritDoc}
     */
    public function detachWildcardListener(callable $listener)
    {
        foreach (array_keys($this->events) as $event) {
            $this->detach($listener, $event, true);
        }
    }

    /**
     * {@inheritDoc}
     * @throws Exception\InvalidArgumentException for invalid event types.
     */
    public function clearListeners($event)
    {
        if (! is_string($event)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a string for the event; received %s',
                __METHOD__,
                gettype($event)
            ));
        }

        if (isset($this->events[$event])) {
            unset($this->events[$event]);
        }
    }

    /**
     * @todo   Use `yield from` once we bump the minimum supported PHP version to 7+.
     * @param  array $prioritizedListeners
     * @return iterable
     */
    private function iterateByPriority($prioritizedListeners)
    {
        krsort($prioritizedListeners);
        foreach ($prioritizedListeners as $listeners) {
            // @todo `yield from $listeners`
            foreach ($listeners as $listener) {
                yield $listener;
            }
        }
    }
}
