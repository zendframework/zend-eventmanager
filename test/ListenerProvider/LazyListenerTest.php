<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\EventManager\ListenerProvider;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Zend\EventManager\EventInterface;
use Zend\EventManager\Exception\InvalidArgumentException;
use Zend\EventManager\ListenerProvider\LazyListener;

class LazyListenerTest extends TestCase
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
            'empty'      => [''],
            'array'      => [['event']],
            'object'     => [(object) ['event' => 'event']],
        ];
    }

    /**
     * @dataProvider invalidListenerTypes
     * @param mixed $listener
     */
    public function testConstructorRaisesExceptionForInvalidListenerType($listener)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a non-empty string $listener argument');
        new LazyListener($this->container->reveal(), $listener);
    }

    public function invalidMethodArguments()
    {
        return array_merge($this->invalidListenerTypes(), [
            'digit-first'     => ['0invalid'],
            'with-whitespace' => ['also invalid'],
            'with-dash'       => ['also-invalid'],
            'with-symbols'    => ['alsoInv@l!d'],
        ]);
    }

    /**
     * @dataProvider invalidMethodArguments
     * @param mixed $method
     */
    public function testConstructorRaisesExceptionForInvalidMethodArgument($method)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a valid string $method argument');
        new LazyListener($this->container->reveal(), 'valid-listener-name', $method);
    }

    public function invalidEventArguments()
    {
        $types = $this->invalidListenerTypes();
        unset($types['null']);
        return $types;
    }

    /**
     * @dataProvider invalidEventArguments
     * @param mixed $event
     */
    public function testConstructorRaisesExceptionForInvalidEventArgument($event)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires a null or non-empty string $event argument');
        new LazyListener($this->container->reveal(), 'valid-listener-name', '__invoke', $event);
    }

    public function testGetEventReturnsNullWhenNoEventProvidedToConstructor()
    {
        $listener = new LazyListener($this->container->reveal(), 'valid-listener-name');
        $this->assertNull($listener->getEvent());
    }

    public function testGetEventReturnsEventNameWhenEventProvidedToConstructor()
    {
        $listener = new LazyListener($this->container->reveal(), 'valid-listener-name', '__invoke', 'test');
        $this->assertEquals('test', $listener->getEvent());
    }

    public function testGetPriorityReturnsPriorityDefaultWhenNoPriorityProvidedToConstructor()
    {
        $listener = new LazyListener($this->container->reveal(), 'valid-listener-name');
        $this->assertEquals(100, $listener->getPriority(100));
    }

    public function testGetPriorityReturnsIntegerPriorityValueWhenPriorityProvidedToConstructor()
    {
        $listener = new LazyListener($this->container->reveal(), 'valid-listener-name', '__invoke', 'test', 100);
        $this->assertEquals(100, $listener->getPriority());
    }

    public function testGetPriorityReturnsIntegerPriorityValueWhenPriorityProvidedToConstructorAndToMethod()
    {
        $listener = new LazyListener($this->container->reveal(), 'valid-listener-name', '__invoke', 'test', 100);
        $this->assertEquals(100, $listener->getPriority(1000));
    }

    public function methodsToInvoke()
    {
        return [
            '__invoke' => ['__invoke', '__invoke'],
            'run'      => ['run', 'run'],
            'onEvent'  => ['onEvent', 'onEvent'],
        ];
    }

    /**
     * @dataProvider methodsToInvoke
     * @param string $method
     * @param string $expected
     */
    public function testInvocationInvokesMethodDefinedInListener($method, $expected)
    {
        $listener = new TestAsset\MultipleListener();

        $this->container
            ->get('listener')
            ->willReturn($listener)
            ->shouldBeCalledTimes(1);

        $event = (object) ['value' => null];

        $lazyListener = new LazyListener($this->container->reveal(), 'listener', $method);

        $lazyListener($event);

        $this->assertEquals($expected, $event->value);
    }
}
