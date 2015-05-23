<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\EventManager;

use Zend\EventManager\Event;
use Zend\EventManager\EventManager;
use Zend\EventManager\Exception\RuntimeException;

class EventManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testExpectedAttachAndTriggerBehaviour()
    {
        $eventManager = new EventManager();

        $listenersTriggered = [];

        $eventManager->attach('MyEvent', function (Event $event) use (&$listenersTriggered) {
            $listenersTriggered[] = 'listenerOne';
        }, 1);

        $eventManager->attach('MyEvent', function (Event $event) use (&$listenersTriggered) {
            $listenersTriggered[] = 'listenerTwo';
            $event->stopPropagation();
        }, 2);

        $eventManager->attach('MyEvent', function (Event $event) use (&$listenersTriggered) {
            $listenersTriggered[] = 'listenerThree';
        }, 3);

        $eventManager->trigger('MyEvent', new Event());

        // We expect two and three to have been triggered (because two stops propagation)
        $this->assertContains('listenerTwo', $listenersTriggered);
        $this->assertContains('listenerThree', $listenersTriggered);

        // Ensure events have been triggered according to highest priority first
        $this->assertSame([
            'listenerThree',
            'listenerTwo',
        ], $listenersTriggered);
    }

    public function testCreatingEventManagerWithoutListenerInstantiatorCausesExceptionWhenEventsFired()
    {
        $eventManager = new EventManager();

        $eventManager->attach('MyEvent', ['SomeEventListenerClass', 'listenerMethod']);

        $this->setExpectedException(RuntimeException::class);

        $eventManager->trigger('MyEvent', new Event());
    }

    public function testLazyInstantiator()
    {
        $lazilyInstantiatedListener = null;

        $eventManager = new EventManager(function ($requestedListener) use (&$lazilyInstantiatedListener) {
            $this->assertSame(LazilyInstantiatedListener::class, $requestedListener);
            $lazilyInstantiatedListener = new LazilyInstantiatedListener();
            return $lazilyInstantiatedListener;
        });

        $eventManager->attach('MyEvent', [LazilyInstantiatedListener::class, 'listenerMethod']);

        $event = new Event();

        // Before triggering, we expect the listener to not be instantiated yet
        $this->assertNull($lazilyInstantiatedListener);

        $eventManager->trigger('MyEvent', $event);

        // Now (by reference in the closure passed to EVM), the listener should be an instance, and have been triggered
        $this->assertInstanceOf(LazilyInstantiatedListener::class, $lazilyInstantiatedListener);
        $this->assertTrue($lazilyInstantiatedListener->hasBeenTriggered());
    }

    public function testDetachReturnsFalseIfEventDoesNotExist()
    {
        $eventManager = new EventManager();
        $result = $eventManager->detach('MyEvent', 'doesNotExist');

        $this->assertFalse($result);
    }

    public function testDetachAllListenersForEvent()
    {
        $eventManager = new EventManager();

        $didTriggerEvent = false;

        $eventManager->attach('MyEvent', function (Event $event) use (&$didTriggerEvent) {
            $didTriggerEvent = true;
        });
        $eventManager->attach('MyEvent', function (Event $event) use (&$didTriggerEvent) {
            $didTriggerEvent = true;
        });

        $eventManager->detach('MyEvent');

        $eventManager->trigger('MyEvent', new Event());

        $this->assertFalse($didTriggerEvent);
    }

    public function testDetachSingleListener()
    {
        $eventManager = new EventManager();

        $hasBeenTriggered = false;
        $listener = function ($event) use (&$hasBeenTriggered) {
            $hasBeenTriggered = true;
        };

        $eventManager->attach('MyEvent', $listener);
        $this->assertFalse($hasBeenTriggered, 'Event has been triggered before trigger was called');

        $eventManager->trigger('MyEvent', new Event());
        $this->assertTrue($hasBeenTriggered, 'Event has not been triggered when it was expected');

        $hasBeenTriggered = false;
        $eventManager->detach('MyEvent', $listener);
        $eventManager->trigger('MyEvent', new Event());
        $this->assertFalse($hasBeenTriggered, 'The event listener was NOT detached properly');
    }

    public function testTriggerUntil()
    {
        $eventManager = new EventManager();

        $listenersTriggered = [];

        $eventManager->attach('MyEvent', function (Event $event) use (&$listenersTriggered) {
            $listenersTriggered[] = 'listenerOne';
            return 'go';
        }, 1);

        $eventManager->attach('MyEvent', function (Event $event) use (&$listenersTriggered) {
            $listenersTriggered[] = 'listenerTwo';
            return 'stop';
        }, 2);

        $eventManager->attach('MyEvent', function (Event $event) use (&$listenersTriggered) {
            $listenersTriggered[] = 'listenerThree';
            return 'go';
        }, 3);

        $responseCollection = $eventManager->triggerUntil('MyEvent', new Event(), function ($response) {
            return $response === 'stop';
        });

        // We expect two and three to have been triggered (because two stops propagation)
        $this->assertContains('listenerTwo', $listenersTriggered);
        $this->assertContains('listenerThree', $listenersTriggered);

        // Ensure events have been triggered according to highest priority first
        $this->assertSame([
            'listenerThree',
            'listenerTwo',
        ], $listenersTriggered);

        $this->assertSame(2, $responseCollection->count());
    }

    public function testWildcardListener()
    {
        $eventManager = new EventManager();

        $triggerCount = 0;

        $eventManager->attach('*', function (Event $event) use (&$triggerCount) {
            $triggerCount++;
        }, 1);

        $this->assertSame(0, $triggerCount);

        $eventManager->trigger('MyEvent1', new Event());
        $this->assertSame(1, $triggerCount);

        $eventManager->trigger('MyEvent2', new Event());
        $this->assertSame(2, $triggerCount);
    }

    public function testWildcardListenerPriorityAgainstNonWildcardListener()
    {
        $eventManager = new EventManager();

        $listenersTriggered = [];

        $eventManager->attach('*', function (Event $event) use (&$listenersTriggered) {
            $listenersTriggered[] = '*';
        }, 1);

        $eventManager->attach('SpecificListener', function (Event $event) use (&$listenersTriggered) {
            $listenersTriggered[] = 'SpecificListener';
        }, 1);

        $eventManager->trigger('SpecificListener', new Event());

        // The order is important - a named listener must be executed before wildcard listener
        $this->assertSame([
            'SpecificListener',
            '*',
        ], $listenersTriggered);
    }
}
