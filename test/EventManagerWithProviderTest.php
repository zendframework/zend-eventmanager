<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\EventManager;

use PHPUnit\Framework\TestCase;
use Zend\EventManager\EventManager;
use Zend\EventManager\Exception\RuntimeException;
use Zend\EventManager\ListenerProvider\ListenerProviderInterface;
use Zend\EventManager\ListenerProvider\PrioritizedListenerAttachmentInterface;

/**
 * Demonstrate usage with an explicitly provided ListenerProvider
 */
class EventManagerWithProviderTest extends TestCase
{
    public function testCanCreateInstanceWithListenerProvider()
    {
        $provider = $this->prophesize(ListenerProviderInterface::class)->reveal();

        $manager = EventManager::createUsingListenerProvider($provider);

        $this->assertInstanceOf(EventManager::class, $manager);
        $this->assertAttributeSame($provider, 'provider', $manager);
        $this->assertAttributeEmpty('prioritizedProvider', $manager);

        return $manager;
    }

    public function testCanCreateInstanceWithPrioritizedListenerProvider()
    {
        $provider = $this->prophesize(ListenerProviderInterface::class);
        $provider->willImplement(PrioritizedListenerAttachmentInterface::class);

        $manager = EventManager::createUsingListenerProvider($provider->reveal());

        $this->assertInstanceOf(EventManager::class, $manager);
        $this->assertAttributeSame($provider->reveal(), 'provider', $manager);
        $this->assertAttributeSame($provider->reveal(), 'prioritizedProvider', $manager);
    }

    public function attachableProviderMethods()
    {
        $listener = function ($e) {
        };
        return [
            'attach'                 => ['attach', ['foo', $listener, 100]],
            'attachWildcardListener' => ['attachWildcardListener', [$listener, 100]],
            'detach'                 => ['detach', [$listener, 'foo']],
            'detachWildcardListener' => ['detachWildcardListener', [$listener]],
            'clearListeners'         => ['clearListeners', ['foo']],
        ];
    }

    /**
     * @dataProvider attachableProviderMethods
     * @depends testCanCreateInstanceWithListenerProvider
     * @param string $method Method to call on manager
     * @param array $arguments Arguments to pass to $method
     * @param EventManager $manager Event manager on which to call $method
     */
    public function testAttachmentMethodsRaiseExceptionForNonAttachableProvider(
        $method,
        array $arguments,
        EventManager $manager
    ) {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('instance is not of type ' . PrioritizedListenerAttachmentInterface::class);
        $manager->{$method}(...$arguments);
    }

    /**
     * @dataProvider attachableProviderMethods
     * @depends testCanCreateInstanceWithPrioritizedListenerProvider
     * @param string $method Method to call on manager
     * @param array $arguments Arguments to pass to $method
     */
    public function testAttachmentMethodsProxyToAttachableProvider($method, array $arguments)
    {
        // Creating instances here, because prophecies cannot be passed as dependencies
        $provider = $this->prophesize(ListenerProviderInterface::class);
        $provider->willImplement(PrioritizedListenerAttachmentInterface::class);
        $manager = EventManager::createUsingListenerProvider($provider->reveal());

        $manager->{$method}(...$arguments);

        $provider->{$method}(...$arguments)->shouldHaveBeenCalledTimes(1);
    }

    public function testGetListenersForEventProxiesToProvider()
    {
        $event    = (object) ['name' => 'test'];
        $listener = function ($e) {
        };

        $listeners = [
            clone $listener,
            clone $listener,
            clone $listener,
        ];

        $provider = $this->prophesize(ListenerProviderInterface::class);
        $provider
            ->getListenersForEvent($event, [])
            ->willReturn($listeners);

        $manager = EventManager::createUsingListenerProvider($provider->reveal());

        $test = $manager->getListenersForEvent($event);

        $this->assertSame($listeners, iterator_to_array($test, false));
    }
}
