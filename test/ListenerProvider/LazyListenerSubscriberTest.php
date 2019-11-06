<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\EventManager\ListenerProvider;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Zend\EventManager\Exception\InvalidArgumentException;
use Zend\EventManager\ListenerProvider\LazyListener;
use Zend\EventManager\ListenerProvider\LazyListenerSubscriber;
use Zend\EventManager\ListenerProvider\PrioritizedListenerAttachmentInterface;

class LazyListenerAggregateTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function invalidListenerTypes()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'string'     => ['listener'],
            'array'      => [['listener']],
            'object'     => [(object) ['event' => 'event', 'listener' => 'listener', 'method' => 'method']],
        ];
    }

    /**
     * @dataProvider invalidListenerTypes
     */
    public function testPassingInvalidListenerTypesAtInstantiationRaisesException($listener)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only accepts ' . LazyListener::class . ' instances');
        new LazyListenerSubscriber([$listener]);
    }

    public function testPassingLazyListenersMissingAnEventAtInstantiationRaisesException()
    {
        $listener = $this->prophesize(LazyListener::class);
        $listener->getEvent()->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('compose a non-empty string event');
        new LazyListenerSubscriber([$listener->reveal()]);
    }

    public function testAttachesLazyListenersToProviderUsingEventAndPriority()
    {
        $listener = $this->prophesize(LazyListener::class);
        $listener->getEvent()->willReturn('test');
        $listener->getPriority(1000)->willReturn(100);

        $subscriber = new LazyListenerSubscriber([$listener->reveal()]);

        $provider = $this->prophesize(PrioritizedListenerAttachmentInterface::class);
        $provider->attach('test', $listener->reveal(), 100)->shouldBeCalledTimes(1);

        $this->assertNull($subscriber->attach($provider->reveal(), 1000));

        return [
            'listener'   => $listener,
            'subscriber' => $subscriber,
            'provider'   => $provider,
        ];
    }

    /**
     * @depends testAttachesLazyListenersToProviderUsingEventAndPriority
     */
    public function testDetachesLazyListenersFromProviderUsingEvent(array $dependencies)
    {
        $listener   = $dependencies['listener'];
        $subscriber = $dependencies['subscriber'];
        $provider   = $dependencies['provider'];

        $provider->detach($listener->reveal(), 'test')->shouldBeCalledTimes(1);
        $this->assertNull($subscriber->detach($provider->reveal()));
    }
}
