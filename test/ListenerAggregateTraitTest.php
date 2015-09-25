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
use Prophecy\Argument;
use Zend\EventManager\EventManagerInterface;

class ListenerAggregateTraitTest extends TestCase
{
    public $aggregateClass = TestAsset\MockListenerAggregateTrait::class;

    public function testDetachRemovesAttachedListeners()
    {
        $class     = $this->aggregateClass;
        $aggregate = new $class();

        $prophecy = $this->prophesize(EventManagerInterface::class);
        $prophecy->attach('foo.bar', [$aggregate, 'doFoo'])->will(function ($args) {
            return $args[1];
        });
        $prophecy->attach('foo.baz', [$aggregate, 'doFoo'])->will(function ($args) {
            return $args[1];
        });
        $prophecy->detach([$aggregate, 'doFoo'])->shouldBeCalledTimes(2);
        $events = $prophecy->reveal();

        $aggregate->attach($events);

        $listeners = $aggregate->getCallbacks();
        $this->assertInternalType('array', $listeners);
        $this->assertCount(2, $listeners);

        foreach ($listeners as $listener) {
            $this->assertSame([$aggregate, 'doFoo'], $listener);
        }

        $aggregate->detach($events);

        $this->assertAttributeSame([], 'listeners', $aggregate);
    }
}
