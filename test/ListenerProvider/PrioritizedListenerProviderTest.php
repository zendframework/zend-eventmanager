<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\EventManager\ListenerProvider;

use SplQueue;
use PHPUnit\Framework\TestCase;
use Zend\EventManager\Event;
use Zend\EventManager\ListenerProvider\PrioritizedListenerProvider;

class PrioritizedListenerProviderTest extends TestCase
{
    public function setUp()
    {
        $this->provider = new PrioritizedListenerProvider();
    }

    public function createEvent()
    {
        $accumulator = new SplQueue();
        $event = new Event();
        $event->setName('test');
        $event->setTarget($this);
        $event->setParams(compact('accumulator'));
        return $event;
    }

    public function createListener($return)
    {
        return function ($event) use ($return) {
            $event->getParam('accumulator')->enqueue($return);
        };
    }

    /**
     * @param object $event
     */
    public function triggerListeners(PrioritizedListenerProvider $provider, $event)
    {
        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }
    }

    /**
     * @param  iterable $listeners
     * @return array
     */
    public function flattenListeners($listeners)
    {
        $flattened = [];
        foreach ($listeners as $listener) {
            $flattened[] = $listener;
        }
        return $flattened;
    }

    public function testIteratesListenersOfDifferentPrioritiesInPriorityOrder()
    {
        for ($i = -1; $i < 5; $i += 1) {
            $this->provider->attach('test', $this->createListener($i), $i);
        }

        $event = $this->createEvent();
        $this->triggerListeners($this->provider, $event);

        $values = iterator_to_array($event->getParam('accumulator'));
        $this->assertEquals(
            [4, 3, 2, 1, 0, -1],
            $values,
            sprintf("Did not receive values in priority order: %s\n", var_export($values, 1))
        );
    }

    public function testIteratesListenersOfSamePriorityInAttachmentOrder()
    {
        for ($i = -1; $i < 5; $i += 1) {
            $this->provider->attach('test', $this->createListener($i));
        }

        $event = $this->createEvent();
        $this->triggerListeners($this->provider, $event);

        $values = iterator_to_array($event->getParam('accumulator'));
        $this->assertEquals(
            [-1, 0, 1, 2, 3, 4],
            $values,
            sprintf("Did not receive values in attachment order: %s\n", var_export($values, 1))
        );
    }

    public function testIteratesWildcardListenersAfterExplicitListenersOfSamePriority()
    {
        $this->provider->attachWildcardListener($this->createListener(2), 5);
        $this->provider->attach('test', $this->createListener(1), 5);
        $this->provider->attachWildcardListener($this->createListener(3), 5);

        $event = $this->createEvent();
        $this->triggerListeners($this->provider, $event);

        $values = iterator_to_array($event->getParam('accumulator'));
        $this->assertEquals(
            [1, 2, 3],
            $values,
            sprintf("Did not receive wildcard values after explicit listeners: %s\n", var_export($values, 1))
        );
    }

    public function testIteratesListenersAttachedToClassNameAfterThoseByNameWhenOfSamePriority()
    {
        $this->provider->attach(Event::class, $this->createListener(2), 5);
        $this->provider->attach('test', $this->createListener(1), 5);
        $this->provider->attach(Event::class, $this->createListener(3), 5);

        $event = $this->createEvent();
        $this->triggerListeners($this->provider, $event);

        $values = iterator_to_array($event->getParam('accumulator'));
        $this->assertEquals(
            [1, 2, 3],
            $values,
            sprintf("Did not receive class-name values after event-name values: %s\n", var_export($values, 1))
        );
    }

    public function testIteratesListenersAttachedToClassNameBeforeWildcardsWhenOfSamePriority()
    {
        $this->provider->attachWildcardListener($this->createListener(2), 5);
        $this->provider->attach(Event::class, $this->createListener(1), 5);
        $this->provider->attachWildcardListener($this->createListener(3), 5);

        $event = $this->createEvent();
        $this->triggerListeners($this->provider, $event);

        $values = iterator_to_array($event->getParam('accumulator'));
        $this->assertEquals(
            [1, 2, 3],
            $values,
            sprintf("Did not receive class-name values before wildcard values: %s\n", var_export($values, 1))
        );
    }

    public function testCanAttachAndIterateUsingOnlyEventClass()
    {
        $expected = ['value'];
        $this->provider->attach(SplQueue::class, function (SplQueue $event) {
            $event->enqueue('value');
        });

        $event = new SplQueue();
        $this->triggerListeners($this->provider, $event);

        $values = iterator_to_array($event);
        $this->assertSame($values, $expected);
    }

    public function testCanDetachPreviouslyAttachedListenerFromEvent()
    {
        $listener = function ($event) {
        };
        $this->provider->attach('test', $listener);

        $event = $this->createEvent();
        $listeners = iterator_to_array($this->provider->getListenersForEvent($event));
        $this->assertSame([$listener], $listeners, 'Expected one listener for event; none found?');

        $this->provider->detach($listener, 'test');
        $listeners = iterator_to_array($this->provider->getListenersForEvent($event));
        $this->assertSame([], $listeners, 'Listener found after detachment, and should not be');
    }

    public function testCanDetachListenerFromAllEventsUsingNullEventToDetach()
    {
        $listener = function ($event) {
        };
        $this->provider->attach('test', $listener);
        $this->provider->attach(Event::class, $listener);

        $event     = $this->createEvent();
        $listeners = $this->flattenListeners($this->provider->getListenersForEvent($event));
        $this->assertSame([$listener, $listener], $listeners);

        $this->provider->detach($listener);
        $listeners = iterator_to_array($this->provider->getListenersForEvent($event));
        $this->assertSame([], $listeners, 'Listener found after detachment, and should not be');
    }

    public function testCanDetachListenerFromAllEventsViaDetachWildcardListener()
    {
        $listener = function ($event) {
        };
        $this->provider->attach('test', $listener);
        $this->provider->attach(Event::class, $listener);

        $event     = $this->createEvent();
        $listeners = $this->flattenListeners($this->provider->getListenersForEvent($event));
        $this->assertSame([$listener, $listener], $listeners);

        $this->provider->detachWildcardListener($listener);
        $listeners = iterator_to_array($this->provider->getListenersForEvent($event));
        $this->assertSame([], $listeners, 'Listeners found after detachment, and should not be');
    }

    public function testCanDetachWildcardListenerFromAllEvents()
    {
        $listener = function ($event) {
        };
        $this->provider->attachWildcardListener($listener);
        $this->provider->attach('test', $listener);
        $this->provider->attach(Event::class, $listener);

        $event = $this->createEvent();
        $listeners = $this->flattenListeners($this->provider->getListenersForEvent($event));
        $this->assertSame([$listener, $listener, $listener], $listeners);

        $this->provider->detachWildcardListener($listener);
        $listeners = iterator_to_array($this->provider->getListenersForEvent($event));
        $this->assertSame([], $listeners, 'Listeners found after detachment, and should not be');
    }

    public function testCanClearListenersForASingleEventName()
    {
        $listener = function ($event) {
        };
        $this->provider->attachWildcardListener($listener);
        $this->provider->attach('test', $listener);
        $this->provider->attach(Event::class, $listener);

        $event = $this->createEvent();
        $listeners = $this->flattenListeners($this->provider->getListenersForEvent($event));
        $this->assertSame([$listener, $listener, $listener], $listeners);

        $this->provider->clearListeners('test');
        $listeners = $this->flattenListeners($this->provider->getListenersForEvent($event));
        $this->assertSame([$listener, $listener], $listeners);
    }
}
