<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace Zend\EventManager\ListenerProvider;

/**
 * Provides an implementation of ListenerSubscriberInterface::detach
 */
trait ListenerSubscriberTrait
{
    /**
     * @var callable[]
     */
    private $listeners = [];

    /**
     * {@inheritDoc}
     */
    public function detach(PrioritizedListenerAttachmentInterface $provider)
    {
        foreach ($this->listeners as $index => $callback) {
            $provider->detach($callback);
            unset($this->listeners[$index]);
        }
    }
}
