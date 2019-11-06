<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace Zend\EventManager\SharedEventManager;

use Zend\EventManager\Exception;
use Zend\EventManager\ListenerProvider\ListenerProviderInterface;
use Zend\EventManager\SharedEventManagerInterface;

class SharedEventManagerDecorator implements
    ListenerProviderInterface,
    SharedEventManagerInterface
{
    /**
     * @var SharedEventManagerInterface
     */
    private $proxy;

    public function __construct(SharedEventManagerInterface $proxy)
    {
        $this->proxy = $proxy;
    }

    /**
     * {@inheritDoc}
     * @var iterable $identifiers Identifiers provided by dispatcher, if any.
     *     This argument is deprecated, and will be removed in version 4.
     */
    public function getListenersForEvent($event, array $identifiers = [])
    {
        return $this->getListeners($identifiers, $this->getEventName($event, __METHOD__));
    }

    /**
     * {@inheritDoc}
     */
    public function attach($identifier, $eventName, callable $listener, $priority = 1)
    {
        return $this->proxy->attach($identifier, $eventName, $listener, $priority);
    }

    /**
     * {@inheritDoc}
     */
    public function detach(callable $listener, $identifier = null, $eventName = null)
    {
        return $this->proxy->detach($listener, $identifier, $eventName);
    }

    /**
     * {@inheritDoc}
     */
    public function getListeners(array $identifiers, $eventName)
    {
        return $this->proxy->getListeners($identifiers, $this->getEventName($eventName));
    }

    /**
     * {@inheritDoc}
     */
    public function clearListeners($identifier, $eventName = null)
    {
        return $this->proxy->clearListeners($identifier, $eventName);
    }

    /**
     * @param  mixed $event
     * @param  string $method Method that called this one
     * @return string
     */
    private function getEventName($event, $method)
    {
        if (is_string($event) && ! empty($event)) {
            return $event;
        }

        if (! is_object($event)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an object or non-empty string $event argument; received %s',
                $method,
                gettype($event)
            ));
        }

        if (is_callable([$event, 'getName'])) {
            return $event->getName() ?: get_class($event);
        }

        return get_class($event);
    }
}
