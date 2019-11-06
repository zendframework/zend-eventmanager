<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace Zend\EventManager\ListenerProvider;

use Zend\EventManager\Exception;

/**
 * Listener subscriber for attaching lazy listeners.
 *
 * Lazy listeners are listeners where creation is deferred until they are
 * triggered; this removes the most costly mechanism of pulling a listener
 * from a container unless the listener is actually invoked.
 *
 * Usage is:
 *
 * <code>
 * $subscriber = new LazyListenerSubscriber($listOfLazyListeners);
 * $subscriber->attach($provider, $priority);
 * ));
 * </code>
 */
class LazyListenerSubscriber implements ListenerSubscriberInterface
{
    /**
     * LazyListener instances.
     *
     * @var LazyListener[]
     */
    private $listeners = [];

    /**
     * @throws Exception\InvalidArgumentException if any member of $listeners
     *     is not a LazyListener instance.
     * @throws Exception\InvalidArgumentException if any member of $listeners
     *     does not have a defined event to which to attach.
     */
    public function __construct(array $listeners)
    {
        $this->validateListeners($listeners);
        $this->listeners = $listeners;
    }

    /**
     * Subscribe listeners to the provider.
     *
     * Loops through all composed lazy listeners, and attaches them to the
     * provider.
     */
    public function attach(PrioritizedListenerAttachmentInterface $provider, $priority = 1)
    {
        foreach ($this->listeners as $listener) {
            $provider->attach(
                $listener->getEvent(),
                $listener,
                $listener->getPriority($priority)
            );
        }
    }

    public function detach(PrioritizedListenerAttachmentInterface $provider)
    {
        foreach ($this->listeners as $listener) {
            $provider->detach($listener, $listener->getEvent());
        }
    }

    /**
     * @throws Exception\InvalidArgumentException if any member of $listeners
     *     is not a LazyListener instance.
     * @throws Exception\InvalidArgumentException if any member of $listeners
     *     does not have a defined event to which to attach.
     */
    private function validateListeners(array $listeners)
    {
        foreach ($listeners as $index => $listener) {
            if (! $listener instanceof LazyListener) {
                throw new Exception\InvalidArgumentException(sprintf(
                    '%s only accepts %s instances; received listener of type %s at index %s',
                    __CLASS__,
                    LazyListener::class,
                    gettype($listener),
                    $index
                ));
            }

            if (null === $listener->getEvent()) {
                throw new Exception\InvalidArgumentException(sprintf(
                    '%s requires that all %s instances compose a non-empty string event to which to attach;'
                    . ' none provided for listener at index %s',
                    __CLASS__,
                    LazyListener::class,
                    $index
                ));
            }
        }
    }
}
