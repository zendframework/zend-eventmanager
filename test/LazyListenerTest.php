<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace ZendTest\EventManager;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use stdClass;
use Zend\EventManager\EventInterface;
use Zend\EventManager\Exception\InvalidArgumentException;
use Zend\EventManager\LazyListener;

class LazyListenerTest extends TestCase
{
    public function setUp()
    {
        $this->listenerClass = LazyListener::class;
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

    public function testConstructorRaisesExceptionForMissingListener()
    {
        $class  = $this->listenerClass;
        $struct = [
            'event'  => 'event',
            'method' => 'method',
        ];
        $this->setExpectedException(InvalidArgumentException::class, 'missing a valid "listener"');
        new $class($struct, $this->container->reveal());
    }

    /**
     * @dataProvider invalidTypes
     */
    public function testConstructorRaisesExceptionForInvalidListenerType($listener)
    {
        $class  = $this->listenerClass;
        $struct = [
            'event'    => 'event',
            'listener' => $listener,
            'method'   => 'method',
        ];
        $this->setExpectedException(InvalidArgumentException::class, 'missing a valid "listener"');
        new $class($struct, $this->container->reveal());
    }

    public function testConstructorRaisesExceptionForMissingMethod()
    {
        $class  = $this->listenerClass;
        $struct = [
            'event'    => 'event',
            'listener' => 'listener',
        ];
        $this->setExpectedException(InvalidArgumentException::class, 'missing a valid "method"');
        new $class($struct, $this->container->reveal());
    }

    /**
     * @dataProvider invalidTypes
     */
    public function testConstructorRaisesExceptionForInvalidMethodType($method)
    {
        $class  = $this->listenerClass;
        $struct = [
            'event'    => 'event',
            'listener' => 'listener',
            'method'   => $method,
        ];
        $this->setExpectedException(InvalidArgumentException::class, 'missing a valid "method"');
        new $class($struct, $this->container->reveal());
    }

    public function testCanInstantiateLazyListenerWithValidDefinition()
    {
        $class  = $this->listenerClass;
        $struct = [
            'listener' => 'listener',
            'method'   => 'method',
        ];

        $listener = new $class($struct, $this->container->reveal());
        $this->assertInstanceOf($class, $listener);
        return $listener;
    }

    /**
     * @depends testCanInstantiateLazyListenerWithValidDefinition
     */
    public function testInstatiationSetsListenerMethod($listener)
    {
        $this->assertAttributeEquals('method', 'method', $listener);
    }

    public function testLazyListenerActsAsInvokableAroundListenerCreation()
    {
        $class    = $this->listenerClass;
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

        $lazyListener = new $class($struct, $this->container->reveal());
        $this->assertInstanceOf($class, $lazyListener);

        $this->assertEquals('RECEIVED', $lazyListener($event->reveal()));
    }

    public function testInvocationWillDelegateToContainerBuildMethodWhenPresentAndEnvIsNonEmpty()
    {
        $class    = $this->listenerClass;
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

        $lazyListener = new $class($struct, $container->reveal(), $env);
        $this->assertInstanceOf($class, $lazyListener);

        $this->assertEquals('RECEIVED', $lazyListener($event->reveal()));
    }
}
