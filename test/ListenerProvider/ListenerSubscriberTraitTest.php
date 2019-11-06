<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\EventManager\ListenerProvider;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Zend\EventManager\ListenerProvider\ListenerSubscriberInterface;
use Zend\EventManager\ListenerProvider\PrioritizedListenerAttachmentInterface;

class ListenerSubscriberTraitTest extends TestCase
{
    /**
     * @return ListenerSubscriberInterface
     */
    public function createProvider(callable $attachmentCallback)
    {
        return new TestAsset\CallbackSubscriber($attachmentCallback);
    }

    public function testSubscriberAttachesListeners()
    {
        $baseListener = function () {
        };
        $listener1    = clone $baseListener;
        $listener2    = clone $baseListener;
        $listener3    = clone $baseListener;

        $provider = $this->prophesize(PrioritizedListenerAttachmentInterface::class);
        $provider->attach('foo.bar', $listener1, 100)->will(function ($args) {
            return $args[1];
        });
        $provider->attach('foo.baz', $listener2, 100)->will(function ($args) {
            return $args[1];
        });

        $subscriber = $this->createProvider(function ($provider, $priority) use ($listener1, $listener2) {
            $this->listeners[] = $provider->attach('foo.bar', $listener1, $priority);
            $this->listeners[] = $provider->attach('foo.baz', $listener2, $priority);
        });

        $subscriber->attach($provider->reveal(), 100);

        $this->assertAttributeSame([$listener1, $listener2], 'listeners', $subscriber);

        return [
            'subscriber' => $subscriber,
            'provider'   => $provider,
            'listener1'  => $listener1,
            'listener2'  => $listener2,
        ];
    }

    /**
     * @depends testSubscriberAttachesListeners
     * @param  array $dependencies
     */
    public function testDetachRemovesAttachedListeners(array $dependencies)
    {
        $subscriber = $dependencies['subscriber'];
        $provider   = $dependencies['provider'];

        $provider->detach($dependencies['listener1'])->shouldBeCalledTimes(1);
        $provider->detach($dependencies['listener2'])->shouldBeCalledTimes(1);

        $subscriber->detach($provider->reveal());
        $this->assertAttributeSame([], 'listeners', $subscriber);
    }
}
