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
use Zend\EventManager\EventInterface;
use Zend\EventManager\Exception\InvalidArgumentException;
use Zend\EventManager\LazyEventListener;

class LazyEventListenerTest extends LazyListenerTest
{
    public function setUp()
    {
        parent::setUp();
        $this->listenerClass = LazyEventListener::class;
    }

    public function testConstructorRaisesExceptionForMissingEvent()
    {
        $class  = $this->listenerClass;
        $struct = [
            'listener' => 'listener',
            'method'   => 'method',
        ];
        $this->setExpectedException(InvalidArgumentException::class, 'missing a valid "event"');
        new $class($struct, $this->container->reveal());
    }

    /**
     * @dataProvider invalidTypes
     */
    public function testConstructorRaisesExceptionForInvalidEventType($event)
    {
        $class  = $this->listenerClass;
        $struct = [
            'event'    => $event,
            'listener' => 'listener',
            'method'   => 'method',
        ];
        $this->setExpectedException(InvalidArgumentException::class, 'missing a valid "event"');
        new $class($struct, $this->container->reveal());
    }

    public function testCanInstantiateLazyListenerWithValidDefinition()
    {
        $class  = $this->listenerClass;
        $struct = [
            'event'    => 'event',
            'listener' => 'listener',
            'method'   => 'method',
            'priority' => 5,
        ];

        $listener = new $class($struct, $this->container->reveal());
        $this->assertInstanceOf($class, $listener);
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
    public function testCanRetrievePriorityFromListener($listener)
    {
        $this->assertEquals(5, $listener->getPriority());
    }

    public function testGetPriorityWillReturnProvidedPriorityIfNoneGivenAtInstantiation()
    {
        $class  = $this->listenerClass;
        $struct = [
            'event'    => 'event',
            'listener' => 'listener',
            'method'   => 'method',
        ];

        $listener = new $class($struct, $this->container->reveal());
        $this->assertInstanceOf($class, $listener);
        $this->assertEquals(5, $listener->getPriority(5));
    }
}
