<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace ZendTest\EventManager;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;

class SharedListenerIntegrationTest extends TestCase
{
    public function setUp()
    {
        $this->identifiers = ['Foo', 'Bar', 'Baz'];
        $this->sharedEvents = new SharedEventManager();
        $this->events = new EventManager($this->sharedEvents, $this->identifiers);
    }

    public function testCanTriggerTheSameSharedListenerMultipleTimes()
    {
        $listener = new TestAsset\CountingListener;
        $this->sharedEvents->attach('Foo', 'foo', $listener);

        $iterations = array_rand(range(5, 100));
        for ($i = 0; $i < $iterations; $i += 1) {
            $this->events->trigger('foo');
        }
        $this->assertSame($iterations, $listener->count);
    }

    public function testTriggeringSameEventMultipleTimesTriggersNewSharedListeners()
    {
        $listeners = [];

        for ($i = 0; $i < 5; $i += 1) {
            $listeners[$i] = $listener = new TestAsset\CountingListener();
            $this->sharedEvents->attach('Foo', 'foo', $listener);
            $this->events->trigger('foo');
        }

        for ($i = 0; $i < 5; $i += 1) {
            $expected = 5 - $i;
            $listener = $listeners[$i];
            $this->assertSame(
                $expected,
                $listener->count,
                sprintf('Listener %s was not triggered expected %d times; instead %d', $i, $expected, $listener->count)
            );
        }
    }

    public function testTriggeringSameEventMultipleTimesDoesNotTriggersDetachedSharedListeners()
    {
        $listeners = [];
        $identifiers = ['Foo', 'Bar', 'Baz'];
        $sharedEvents = new SharedEventManager();
        $events = new EventManager($sharedEvents, $identifiers);

        for ($i = 0; $i < 5; $i += 1) {
            $listeners[$i] = $listener = new TestAsset\CountingListener();
            $listener->index = $i;
            $sharedEvents->attach('Foo', 'foo', $listener);
        }

        for ($i = 0; $i < 5; $i += 1) {
            $sharedEvents->detach($listeners[$i], 'Foo', 'foo');
            $events->trigger('foo');
        }

        for ($i = 0; $i < 5; $i += 1) {
            $listener = $listeners[$i];
            $this->assertEquals($i, $listener->count);
        }
    }
}
