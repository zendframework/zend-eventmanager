<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\EventManager\ListenerProvider;

class AbstractListenerSubscriberTest extends ListenerSubscriberTraitTest
{
    /**
     * {@inheritDoc}
     */
    public function createProvider(callable $attachmentCallback)
    {
        return new TestAsset\ExtendedCallbackSubscriber($attachmentCallback);
    }
}
