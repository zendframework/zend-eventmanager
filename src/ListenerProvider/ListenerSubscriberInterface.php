<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace Zend\EventManager\ListenerProvider;

interface ListenerSubscriberInterface
{
    /**
     * @param  int $priority Default priority at which to attach composed listeners.
     * @return void
     */
    public function attach(PrioritizedListenerAttachmentInterface $provider, $priority = 1);

    /**
     * @return void
     */
    public function detach(PrioritizedListenerAttachmentInterface $provider);
}
