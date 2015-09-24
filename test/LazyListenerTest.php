<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\EventManager;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use stdClass;
use Zend\EventManager\EventInterface;
use Zend\EventManager\LazyListener;
use Zend\EventManager\Exception\InvalidArgumentException;

class LazyListenerTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function invalidTypes()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'empty'      => [''],
            'array'      => [['event']],
            'object'     => [(object) ['event' => 'event']],
        ];
    }

    public function testConstructorRaisesExceptionForMissingEvent()
    {
        $struct = [
            'listener' => 'listener',
            'method'   => 'method',
        ];
        $this->setExpectedException(InvalidArgumentException::class, 'missing a valid "event"');
        new LazyListener($struct, $this->container->reveal());
    }

    /**
     * @dataProvider invalidTypes
     */
    public function testConstructorRaisesExceptionForInvalidEventType($event)
    {
        $struct = [
            'event'    => $event,
            'listener' => 'listener',
            'method'   => 'method',
        ];
        $this->setExpectedException(InvalidArgumentException::class, 'missing a valid "event"');
        new LazyListener($struct, $this->container->reveal());
    }

    public function testConstructorRaisesExceptionForMissingListener()
    {
        $struct = [
            'event'  => 'event',
            'method' => 'method',
        ];
        $this->setExpectedException(InvalidArgumentException::class, 'missing a valid "listener"');
        new LazyListener($struct, $this->container->reveal());
    }

    /**
     * @dataProvider invalidTypes
     */
    public function testConstructorRaisesExceptionForInvalidListenerType($listener)
    {
        $struct = [
            'event'    => 'event',
            'listener' => $listener,
            'method'   => 'method',
        ];
        $this->setExpectedException(InvalidArgumentException::class, 'missing a valid "listener"');
        new LazyListener($struct, $this->container->reveal());
    }

    public function testConstructorRaisesExceptionForMissingMethod()
    {
        $struct = [
            'event'    => 'event',
            'listener' => 'listener',
        ];
        $this->setExpectedException(InvalidArgumentException::class, 'missing a valid "method"');
        new LazyListener($struct, $this->container->reveal());
    }

    /**
     * @dataProvider invalidTypes
     */
    public function testConstructorRaisesExceptionForInvalidMethodType($method)
    {
        $struct = [
            'event'    => 'event',
            'listener' => 'listener',
            'method'   => $method,
        ];
        $this->setExpectedException(InvalidArgumentException::class, 'missing a valid "method"');
        new LazyListener($struct, $this->container->reveal());
    }

    public function testCanInstantiateLazyListenerWithValidDefinition()
    {
        $struct = [
            'event'    => 'event',
            'listener' => 'listener',
            'method'   => 'method',
            'priority' => 5,
        ];

        $listener = new LazyListener($struct, $this->container->reveal());
        $this->assertInstanceOf(LazyListener::class, $listener);
        return $listener;
    }

    /**
     * @depends testCanInstantiateLazyListenerWithValidDefinition
     */
    public function testCanRetrieveEventFromListener($listener)
    {
        $this->assertEquals('event', $listener->getEvent());
    }

    /**
     * @depends testCanInstantiateLazyListenerWithValidDefinition
     */
    public function testInstatiationSetsListenerMethod($listener)
    {
        $this->assertAttributeEquals('method', 'method', $listener);
    }

    /**
     * @depends testCanInstantiateLazyListenerWithValidDefinition
     */
    public function testCanRetrievePriorityFromListener($listener)
    {
        $this->assertEquals(5, $listener->getPriority());
    }

    public function testGetPriorityWillReturnProvidedPriorityIfNoneGivenAtInstantiation()
    {
        $struct = [
            'event'    => 'event',
            'listener' => 'listener',
            'method'   => 'method',
        ];

        $listener = new LazyListener($struct, $this->container->reveal());
        $this->assertInstanceOf(LazyListener::class, $listener);
        $this->assertEquals(5, $listener->getPriority(5));
    }

    public function testLazyListenerActsAsInvokableAroundListenerCreation()
    {
        $listener = $this->prophesize(TestAsset\BuilderInterface::class);
        $listener->build(Argument::type(EventInterface::class))->willReturn('RECEIVED');

        $event = $this->prophesize(EventInterface::class);

        $this->container->get('listener')->will(function ($args) use ($listener) {
            return $listener->reveal();
        });

        $struct = [
            'event'    => 'event',
            'listener' => 'listener',
            'method'   => 'build',
            'priority' => 5,
        ];

        $lazyListener = new LazyListener($struct, $this->container->reveal());
        $this->assertInstanceOf(LazyListener::class, $lazyListener);

        $this->assertEquals('RECEIVED', $lazyListener($event->reveal()));
    }

    public function testInvocationWillDelegateToContainerBuildMethodWhenPresentAndEnvIsNonEmpty()
    {
        $listener = $this->prophesize(TestAsset\BuilderInterface::class);
        $listener->build(Argument::type(EventInterface::class))->willReturn('RECEIVED');

        $event = $this->prophesize(EventInterface::class);

        $instance = new stdClass;
        $env      = [
            'foo' => 'bar',
        ];

        $container = $this->prophesize(TestAsset\BuilderInterface::class);
        $container->build('listener', $env)->will(function ($args) use ($listener) {
            return $listener->reveal();
        });

        $struct = [
            'event'    => 'event',
            'listener' => 'listener',
            'method'   => 'build',
            'priority' => 5,
        ];

        $lazyListener = new LazyListener($struct, $container->reveal(), $env);
        $this->assertInstanceOf(LazyListener::class, $lazyListener);

        $this->assertEquals('RECEIVED', $lazyListener($event->reveal()));
    }
}
