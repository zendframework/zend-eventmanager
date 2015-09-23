<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\EventManager;

use Interop\Container\ContainerInterface;

/**
 * Lazy listener instance.
 *
 * Used as an internal class for the LazyAggregate to allow lazy creation of
 * listeners via a dependency injection container.
 *
 * Lazy listener structs have the following members:
 *
 * - event: the event name to attach to.
 * - listener: the service name of the listener to use.
 * - method: the method name of the listener to invoke for the specified event.
 * - priority: the priority at which to attach the listener.
 *
 * If desired, you can pass $env at instantiation; this will be passed to the
 * container's `build()` method, if it has one, when creating the listener
 * instance.
 *
 * You can also use this to create your own lazy listeners; if you do, pass the
 * return value of `getListener()` to the event manager's `attach()` method;
 * the value is a closure around instance creation and method invocation.
 */
class LazyListener
{
    /**
     * @var ContainerInterface Container from which to pull listener.
     */
    private $container;

    /**
     * @var array Variables/options to use during service creation, if any.
     */
    private $env;

    /**
     * @var string Event name to which to attach.
     */
    private $event;

    /**
     * @var callable Marshaled listener callback.
     */
    private $listener;

    /**
     * @var string Method name to invoke on listener.
     */
    private $method;

    /**
     * @var null|int Priority at which to attach.
     */
    private $priority;

    /**
     * @var string Service name of listener.
     */
    private $service;

    /**
     * @param array $struct
     * @param ContainerInterface $container
     * @param array $env
     */
    public function __construct(array $struct, ContainerInterface $container, array $env = [])
    {
        if ((! isset($struct['event']) || ! is_string($struct['event']) || empty($struct['event']))) {
            throw new Exception\InvalidArgumentException(
                'Lazy listener struct is missing a valid "event" member; cannot create LazyListener'
            );
        }

        if ((! isset($struct['listener']) || ! is_string($struct['listener']) || empty($struct['listener']))) {
            throw new Exception\InvalidArgumentException(
                'Lazy listener struct is missing a valid "listener" member; cannot create LazyListener'
            );
        }

        if ((! isset($struct['method']) || ! is_string($struct['method']) || empty($struct['method']))) {
            throw new Exception\InvalidArgumentException(
                'Lazy listener struct is missing a valid "method" member; cannot create LazyListener'
            );
        }

        $this->event     = $struct['event'];
        $this->service   = $struct['listener'];
        $this->method    = $struct['method'];
        $this->priority  = isset($struct['priority']) ? (int) $struct['priority'] : null;
        $this->container = $container;
        $this->env       = $env;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function getListener()
    {
        if (! $this->listener) {
            $this->listener = $this->createListenerClosure();
        }

        return $this->listener;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getPriority($default)
    {
        return (null !== $this->priority) ? $this->priority : $default;
    }

    /**
     * Create the closure around retrieval and invocation of the listener.
     *
     * @return callable
     */
    private function createListenerClosure()
    {
        return function ($e) {
            if (method_exists($this->container, 'build') && ! empty($this->env)) {
                $listener = $this->container->build($this->service, $this->env);
            } else {
                $listener = $this->container->get($this->service);
            }

            $method   = $this->method;
            return $listener->{$method}($e);
        };
    }
}
