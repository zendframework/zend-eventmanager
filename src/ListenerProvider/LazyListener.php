<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace Zend\EventManager\ListenerProvider;

use Psr\Container\ContainerInterface;
use Zend\EventManager\Exception;

/**
 * Lazy listener instance.
 *
 * Used to allow lazy creation of listeners via a dependency injection
 * container.
 */
class LazyListener
{
    /**
     * @var ContainerInterface Container from which to pull listener.
     */
    private $container;

    /**
     * @var null|string Event name to which to attach; for use with
     *     ListenerSubscriberInterface instances.
     */
    private $event;

    /**
     * @var object Service pulled from container
     */
    private $listener;

    /**
     * @var string Method name to invoke on listener.
     */
    private $method;

    /**
     * @var null|int Priority at which to attach; for use with
     *     ListenerSubscriberInterface instances.
     */
    private $priority;

    /**
     * @var string Service name of listener.
     */
    private $service;

    /**
     * @param ContainerInterface $container Container from which to pull
     *     listener service
     * @param string $listener Name of listener service to retrive from
     *     container
     * @param null|string $method Name of method on listener service to use
     *     when calling listener; defaults to __invoke.
     * @param null|string $event Name of event to which to attach; for use
     *     with ListenerSubscriberInterface instances. In that scenario, null
     *     indicates it should attach to any event.
     * @param null|int $priority Priority at which to attach; for use with
     *     ListenerSubscriberInterface instances. In that scenario, null indicates
     *     that the default priority should be used.
     * @throws Exception\InvalidArgumentException for invalid $listener arguments
     * @throws Exception\InvalidArgumentException for invalid $method arguments
     * @throws Exception\InvalidArgumentException for invalid $event arguments
     */
    public function __construct(
        ContainerInterface $container,
        $listener,
        $method = '__invoke',
        $event = null,
        $priority = null
    ) {
        if (! is_string($listener) || empty($listener)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s requires a non-empty string $listener argument'
                . ' representing a service name; received %s',
                __CLASS__,
                gettype($listener)
            ));
        }

        if (! is_string($method)
            || ! preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $method)
        ) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s requires a valid string $method argument; received %s',
                __CLASS__,
                is_string($method) ? sprintf('"%s"', $method) : gettype($method)
            ));
        }

        if (null !== $event && (! is_string($event) || empty($event))) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s requires a null or non-empty string $event argument; received %s',
                __CLASS__,
                is_string($event) ? sprintf('"%s"', $event) : gettype($event)
            ));
        }

        $this->container = $container;
        $this->service   = $listener;
        $this->method    = $method;
        $this->event     = $event;
        $this->priority  = $priority;
    }

    /**
     * Use the listener as an invokable, allowing direct attachment to an event manager.
     *
     * @param  object $event
     * @return void
     */
    public function __invoke($event)
    {
        $listener = $this->fetchListener();
        $method   = $this->method;
        $listener->{$method}($event);
    }

    /**
     * @return null|string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Return the priority, or, if not set, the default provided.
     *
     * @param  int $default
     * @return int
     */
    public function getPriority($default = 1)
    {
        return null !== $this->priority ? (int) $this->priority : (int) $default;
    }

    /**
     * @return callable
     */
    private function fetchListener()
    {
        if ($this->listener) {
            return $this->listener;
        }

        $this->listener = $this->container->get($this->service);

        return $this->listener;
    }
}
