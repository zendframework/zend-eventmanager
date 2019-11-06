<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace Zend\EventManager;

use ArrayObject;

/**
 * Event manager: notification system
 *
 * Use the EventManager when you want to create a per-instance notification
 * system for your objects.
 */
class EventManager implements
    EventDispatcherInterface,
    EventManagerInterface,
    ListenerProvider\ListenerProviderInterface,
    ListenerProvider\PrioritizedListenerAttachmentInterface
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
     * @var array[]
     */
    protected $events = [];

    /**
     * @var EventInterface Prototype to use when creating an event at trigger().
     */
    protected $eventPrototype;

    /**
     * Identifiers, used to pull shared signals from SharedEventManagerInterface instance
     *
     * @var array
     */
    protected $identifiers = [];

    /**
     * @var ListenerProvider\PrioritizedListenerAttachmentInterface
     */
    protected $prioritizedProvider;

    /**
     * @var ListenerProvider\ListenerProviderInterface
     */
    protected $provider;

    /**
     * Shared event manager
     *
     * @var null|SharedEventManagerInterface
     */
    protected $sharedManager = null;

    /**
     * Use this method to create an instance that utilizes a specfic listener provider.
     *
     * When using the constructor in version 3 releases, the class will create an
     * empty PrioritizedListenerProvider instance, and push that and any provided
     * SharedEventManager instance into a PrioritizedAggregateListenerProvider;
     * this approach allows the class to also act as a provider, keeping backwards
     * compatibility.
     *
     * This method allows you to bypass that behavior, and instead attach a specific
     * provider to your event manager instance. This can be useful for making your
     * instance forwards compatible with the proposed version 4, which will only
     * consume providers.
     *
     * @param string[] $identifiers Deprecated. Identifiers to use when
     *     retrieving events from a prioritized provider. In general, use fully
     *     qualified event class names instead.
     * @return self
     */
    public static function createUsingListenerProvider(
        ListenerProvider\ListenerProviderInterface $provider,
        array $identifiers = []
    ) {
        $instance = new self(null, [], true);
        $instance->provider = $provider;
        if ($provider instanceof ListenerProvider\PrioritizedListenerAttachmentInterface) {
            $instance->prioritizedProvider = $provider;
        }
        return $instance;
    }

    /**
     * Constructor
     *
     * Allows optionally specifying identifier(s) to use to pull signals from a
     * SharedEventManagerInterface.
     *
     * @param SharedEventManagerInterface $sharedEventManager
     * @param array $identifiers
     * @param bool $skipProviderCreation Internal; used by
     *     createUsingListenerProvider to ensure that no provider is created during
     *     instantiation.
     */
    public function __construct(
        SharedEventManagerInterface $sharedEventManager = null,
        array $identifiers = [],
        $skipProviderCreation = false
    ) {
        $this->eventPrototype = new Event();

        if ($skipProviderCreation) {
            // Nothing else to do.
            return;
        }

        if ($sharedEventManager) {
            $this->sharedManager = $sharedEventManager instanceof SharedEventManager
                ? $sharedEventManager
                : new SharedEventManager\SharedEventManagerDecorator($sharedEventManager);
            $this->setIdentifiers($identifiers);
        }

        $this->prioritizedProvider = new ListenerProvider\PrioritizedListenerProvider();

        $this->provider = $this->createProvider($this->prioritizedProvider, $this->sharedManager);
    }

    /**
     * @deprecated Will be removed in version 4; use event instances when triggering
     *     events instead.
     */
    public function setEventPrototype(EventInterface $prototype)
    {
        $this->eventPrototype = $prototype;
    }

    /**
     * Retrieve the shared event manager, if composed.
     *
     * @deprecated Will be removed in version 4; use a listener provider and
     *     lazy listeners instead.
     * @return null|SharedEventManagerInterface $sharedEventManager
     */
    public function getSharedManager()
    {
        return $this->sharedManager;
    }

    /**
     * {@inheritDoc}
     * @deprecated Will be removed in version 4; use fully qualified event names
     *     and the object inheritance hierarchy instead.
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    /**
     * {@inheritDoc}
     * @deprecated Will be removed in version 4; use fully qualified event names
     *     and the object inheritance hierarchy instead.
     */
    public function setIdentifiers(array $identifiers)
    {
        $this->identifiers = array_unique($identifiers);
    }

    /**
     * {@inheritDoc}
     * @deprecated Will be removed in version 4; use fully qualified event names
     *     and the object inheritance hierarchy instead.
     */
    public function addIdentifiers(array $identifiers)
    {
        $this->identifiers = array_unique(array_merge(
            $this->identifiers,
            $identifiers
        ));
    }

    /**
     * {@inheritDoc}
     * @deprecated Will be removed in version 4; use dispatch() with an event
     *     instance instead.
     */
    public function trigger($eventName, $target = null, $argv = [])
    {
        $event = clone $this->eventPrototype;
        $event->setName($eventName);

        if ($target !== null) {
            $event->setTarget($target);
        }

        if ($argv) {
            $event->setParams($argv);
        }

        return $this->triggerListeners($event);
    }

    /**
     * {@inheritDoc}
     * @deprecated Will be removed in version 4; use dispatch() with an event
     *     instance instead, and encapsulate logic for stopping propagation
     *     within the event itself.
     */
    public function triggerUntil(callable $callback, $eventName, $target = null, $argv = [])
    {
        $event = clone $this->eventPrototype;
        $event->setName($eventName);

        if ($target !== null) {
            $event->setTarget($target);
        }

        if ($argv) {
            $event->setParams($argv);
        }

        return $this->triggerListeners($event, $callback);
    }

    /**
     * {@inheritDoc}
     * @deprecated Will be removed in version 4; use dispatch() instead.
     */
    public function triggerEvent(EventInterface $event)
    {
        return $this->triggerListeners($event);
    }

    /**
     * {@inheritDoc}
     * @deprecated Will be removed in version 4; use dispatch() instead, and
     *     encapsulate logic for stopping propagation within the event itself.
     */
    public function triggerEventUntil(callable $callback, EventInterface $event)
    {
        return $this->triggerListeners($event, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function dispatch($event)
    {
        if (! is_object($event)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an object; received "%s"',
                __CLASS__,
                gettype($event)
            ));
        }

        $this->triggerListeners($event);
        return $event;
    }

    /**
     * {@inheritDoc}
     * @deprecated This method will be removed in version 4.0; use listener
     *     providers and the createUsingListenerProvider method instead.
     * @throws Exception\RuntimeException if no prioritized provider is composed.
     */
    public function attach($eventName, callable $listener, $priority = 1)
    {
        if (! $this->prioritizedProvider) {
            throw new Exception\RuntimeException(sprintf(
                'The provider composed into this %s instance is not of type %s (received %s);'
                . ' attach listeners to it directly using its API before passing to the %s constructor',
                get_class($this),
                ListenerProvider\PrioritizedListenerAttachmentInterface::class,
                gettype($this->provider),
                get_class($this)
            ));
        }

        $this->prioritizedProvider->attach($eventName, $listener, $priority);
        return $listener;
    }

    /**
     * {@inheritDoc}
     * @deprecated This method will be removed in version 4.0; use listener
     *     providers and the createUsingListenerProvider method instead.
     */
    public function attachWildcardListener(callable $listener, $priority = 1)
    {
        if (! $this->prioritizedProvider) {
            throw new Exception\RuntimeException(sprintf(
                'The provider composed into this %s instance is not of type %s (received %s);'
                . ' attach wildcared listeners to it directly using its API before passing to the %s constructor',
                get_class($this),
                ListenerProvider\PrioritizedListenerAttachmentInterface::class,
                gettype($this->provider),
                get_class($this)
            ));
        }

        $this->prioritizedProvider->attachWildcardListener($listener, $priority);
        return $listener;
    }

    /**
     * {@inheritDoc}
     * @deprecated This method will be removed in version 4.0; use listener
     *     providers and the createUsingListenerProvider method instead.
     * @throws Exception\InvalidArgumentException for invalid event types.
     */
    public function detach(callable $listener, $eventName = null, $force = false)
    {
        if (! $this->prioritizedProvider) {
            throw new Exception\RuntimeException(sprintf(
                'The provider composed into this %s instance is not of type %s (received %s);'
                . ' detach listeners from it directly using its API',
                get_class($this),
                ListenerProvider\PrioritizedListenerAttachmentInterface::class,
                gettype($this->provider)
            ));
        }

        $this->prioritizedProvider->detach($listener, $eventName);
    }

    /**
     * {@inheritDoc}
     * @deprecated This method will be removed in version 4.0; use listener
     *     providers and the createUsingListenerProvider method instead.
     */
    public function detachWildcardListener(callable $listener)
    {
        if (! $this->prioritizedProvider) {
            throw new Exception\RuntimeException(sprintf(
                'The provider composed into this %s instance is not of type %s (received %s);'
                . ' detach wildcard listeners from it directly using its API',
                get_class($this),
                ListenerProvider\PrioritizedListenerAttachmentInterface::class,
                gettype($this->provider)
            ));
        }

        $this->prioritizedProvider->detachWildcardListener($listener);
    }

    /**
     * {@inheritDoc}
     * @deprecated This method will be removed in version 4.0; use listener
     *     providers and the createUsingListenerProvider method instead.
     */
    public function clearListeners($eventName)
    {
        if (! $this->prioritizedProvider) {
            throw new Exception\RuntimeException(sprintf(
                'The provider composed into this %s instance is not of type %s (received %s);'
                . ' clear wildcard listeners from it directly using its API',
                get_class($this),
                ListenerProvider\PrioritizedListenerAttachmentInterface::class,
                gettype($this->provider)
            ));
        }

        $this->prioritizedProvider->clearListeners($eventName);
    }

    /**
     * {@inheritDoc}
     * @todo Use `yield from` once we bump the minimum supported PHP version to 7+.
     * @deprecated This method will be removed in version 4.0, and EventManager
     *     will no longer be its own listener provider; use external listener
     *     providers and the createUsingListenerProvider method instead.
     */
    public function getListenersForEvent($event)
    {
        // @todo Use `yield from $this->provider->getListenersForEvent(...)
        foreach ($this->provider->getListenersForEvent($event, $this->identifiers) as $listener) {
            yield $listener;
        }
    }

    /**
     * Prepare arguments
     *
     * Use this method if you want to be able to modify arguments from within a
     * listener. It returns an ArrayObject of the arguments, which may then be
     * passed to trigger().
     *
     * @deprecated This method will be removed in version 4.0; always use context
     *     specific events with their own mutation methods.
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
     * @param  object $event
     * @param  null|callable $callback
     * @return ResponseCollection
     */
    protected function triggerListeners($event, callable $callback = null)
    {
        // Initial value of stop propagation flag should be false
        if ($event instanceof EventInterface) {
            $event->stopPropagation(false);
        }

        $stopMethod = $event instanceof StoppableEventInterface ? 'isPropagationStopped' : 'propagationIsStopped';

        // Execute listeners
        $responses = new ResponseCollection();

        foreach ($this->provider->getListenersForEvent($event, $this->identifiers) as $listener) {
            $response = $listener($event);
            $responses->push($response);

            // If the event was asked to stop propagating, do so
            if ($event->{$stopMethod}()) {
                $responses->setStopped(true);
                return $responses;
            }

            // If the result causes our validation callback to return true,
            // stop propagation
            if ($callback && $callback($response)) {
                $responses->setStopped(true);
                return $responses;
            }
        }

        return $responses;
    }

    /**
     * Creates the value for the $provider property, based on the
     * $sharedEventManager argument.
     *
     * @param  ListenerProvider\PrioritizedListenerProvider $prioritizedProvider
     * @param  null|SharedEventManagerInterface $sharedEventManager
     * @return ListenerProvider\ListenerProviderInterface
     */
    private function createProvider(
        ListenerProvider\PrioritizedListenerProvider $prioritizedProvider,
        SharedEventManagerInterface $sharedEventManager = null
    ) {
        if (! $sharedEventManager) {
            return $prioritizedProvider;
        }

        if ($sharedEventManager instanceof ListenerProvider\PrioritizedListenerProviderInterface) {
            return new ListenerProvider\PrioritizedAggregateListenerProvider([
                $prioritizedProvider,
                $sharedEventManager,
            ]);
        }

        return new ListenerProvider\PrioritizedAggregateListenerProvider(
            [$prioritizedProvider],
            $sharedEventManager
        );
    }
}
