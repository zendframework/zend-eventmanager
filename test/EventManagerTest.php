<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace ZendTest\EventManager;

use Prophecy\Argument;
use ReflectionProperty;
use stdClass;
use Zend\EventManager\Event;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\Exception;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\SharedEventManagerInterface;

class EventManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (isset($this->message)) {
            unset($this->message);
        }
        $this->events = new EventManager;
    }

    /**
     * Retrieve list of registered event names from a manager.
     *
     * @param EventManager $manager
     * @return string[]
     */
    public function getEventListFromManager(EventManager $manager)
    {
        $r = new ReflectionProperty($manager, 'events');
        $r->setAccessible(true);
        return array_keys($r->getValue($manager));
    }

    /**
     * Return listeners for a given event.
     *
     * @param string $event
     * @param EventManager $manager
     * @return array
     */
    public function getListenersForEvent($event, EventManager $manager)
    {
        $r = new ReflectionProperty($manager, 'events');
        $r->setAccessible(true);
        $events = $r->getValue($manager);

        $listenersByPriority = isset($events[$event]) ? $events[$event] : [];
        foreach ($listenersByPriority as $priority => & $listeners) {
            $listeners = $listeners[0];
        }

        return $listenersByPriority;
    }

    public function testAttachShouldAddListenerToEvent()
    {
        $listener  = [$this, __METHOD__];
        $this->events->attach('test', $listener);
        $listeners = $this->getListenersForEvent('test', $this->events);
        // Get first (and only) priority queue of listeners for event
        $listeners = array_shift($listeners);
        $this->assertCount(1, $listeners);
        $this->assertContains($listener, $listeners);
        return [
            'event'    => 'test',
            'events'   => $this->events,
            'listener' => $listener,
        ];
    }

    public function eventArguments()
    {
        return [
            'single-named-event' => ['test'],
            'wildcard-event'     => ['*'],
        ];
    }

    /**
     * @dataProvider eventArguments
     */
    public function testAttachShouldAddReturnTheListener($event)
    {
        $listener  = [$this, __METHOD__];
        $this->assertSame($listener, $this->events->attach($event, $listener));
    }

    public function testAttachShouldAddEventIfItDoesNotExist()
    {
        $this->assertAttributeEmpty('events', $this->events);
        $listener = $this->events->attach('test', [$this, __METHOD__]);
        $events = $this->getEventListFromManager($this->events);
        $this->assertNotEmpty($events);
        $this->assertContains('test', $events);
    }

    public function testTriggerShouldTriggerAttachedListeners()
    {
        $listener = $this->events->attach('test', [$this, 'handleTestEvent']);
        $this->events->trigger('test', $this, ['message' => 'test message']);
        $this->assertEquals('test message', $this->message);
    }

    public function testTriggerShouldReturnAllListenerReturnValues()
    {
        $this->events->attach('string.transform', function ($e) {
            $string = $e->getParam('string', '__NOT_FOUND__');
            return trim($string);
        });
        $this->events->attach('string.transform', function ($e) {
            $string = $e->getParam('string', '__NOT_FOUND__');
            return str_rot13($string);
        });
        $responses = $this->events->trigger('string.transform', $this, ['string' => ' foo ']);
        $this->assertInstanceOf('Zend\EventManager\ResponseCollection', $responses);
        $this->assertEquals(2, $responses->count());
        $this->assertEquals('foo', $responses->first());
        $this->assertEquals(\str_rot13(' foo '), $responses->last());
    }

    public function testTriggerUntilShouldReturnAsSoonAsCallbackReturnsTrue()
    {
        $this->events->attach('foo.bar', function ($e) {
            $string = $e->getParam('string', '');
            $search = $e->getParam('search', '?');
            return strpos($string, $search);
        });
        $this->events->attach('foo.bar', function ($e) {
            $string = $e->getParam('string', '');
            $search = $e->getParam('search', '?');
            return strstr($string, $search);
        });
        $responses = $this->events->triggerUntil(
            [$this, 'evaluateStringCallback'],
            'foo.bar',
            $this,
            ['string' => 'foo', 'search' => 'f']
        );
        $this->assertInstanceOf('Zend\EventManager\ResponseCollection', $responses);
        $this->assertSame(0, $responses->last());
    }

    public function testTriggerResponseCollectionContains()
    {
        $this->events->attach('string.transform', function ($e) {
            $string = $e->getParam('string', '');
            return trim($string);
        });
        $this->events->attach('string.transform', function ($e) {
            $string = $e->getParam('string', '');
            return str_rot13($string);
        });
        $responses = $this->events->trigger('string.transform', $this, ['string' => ' foo ']);
        $this->assertTrue($responses->contains('foo'));
        $this->assertTrue($responses->contains(\str_rot13(' foo ')));
        $this->assertFalse($responses->contains(' foo '));
    }

    public function handleTestEvent($e)
    {
        $message = $e->getParam('message', '__NOT_FOUND__');
        $this->message = $message;
    }

    public function evaluateStringCallback($value)
    {
        return (! $value);
    }

    public function testTriggerUntilShouldMarkResponseCollectionStoppedWhenConditionMet()
    {
        // @codingStandardsIgnoreStart
        $this->events->attach('foo.bar', function () { return 'bogus'; }, 4);
        $this->events->attach('foo.bar', function () { return 'nada'; }, 3);
        $this->events->attach('foo.bar', function () { return 'found'; }, 2);
        $this->events->attach('foo.bar', function () { return 'zero'; }, 1);
        // @codingStandardsIgnoreEnd

        $responses = $this->events->triggerUntil(function ($result) {
            return ($result === 'found');
        }, 'foo.bar', $this);
        $this->assertInstanceOf('Zend\EventManager\ResponseCollection', $responses);
        $this->assertTrue($responses->stopped());
        $result = $responses->last();
        $this->assertEquals('found', $result);
        $this->assertFalse($responses->contains('zero'));
    }

    public function testTriggerUntilShouldMarkResponseCollectionStoppedWhenConditionMetByLastListener()
    {
        // @codingStandardsIgnoreStart
        $this->events->attach('foo.bar', function () { return 'bogus'; });
        $this->events->attach('foo.bar', function () { return 'nada'; });
        $this->events->attach('foo.bar', function () { return 'zero'; });
        $this->events->attach('foo.bar', function () { return 'found'; });
        // @codingStandardsIgnoreEnd

        $responses = $this->events->triggerUntil(function ($result) {
            return ($result === 'found');
        }, 'foo.bar', $this);
        $this->assertInstanceOf('Zend\EventManager\ResponseCollection', $responses);
        $this->assertTrue($responses->stopped());
        $this->assertEquals('found', $responses->last());
    }

    public function testResponseCollectionIsNotStoppedWhenNoCallbackMatchedByTriggerUntil()
    {
        // @codingStandardsIgnoreStart
        $this->events->attach('foo.bar', function () { return 'bogus'; }, 4);
        $this->events->attach('foo.bar', function () { return 'nada'; }, 3);
        $this->events->attach('foo.bar', function () { return 'found'; }, 2);
        $this->events->attach('foo.bar', function () { return 'zero'; }, 1);
        // @codingStandardsIgnoreEnd

        $responses = $this->events->triggerUntil(function ($result) {
            return ($result === 'never found');
        }, 'foo.bar', $this);
        $this->assertInstanceOf('Zend\EventManager\ResponseCollection', $responses);
        $this->assertFalse($responses->stopped());
        $this->assertEquals('zero', $responses->last());
    }

    public function testCallingEventsStopPropagationMethodHaltsEventEmission()
    {
        // @codingStandardsIgnoreStart
        $this->events->attach('foo.bar', function ($e) { return 'bogus'; }, 4);
        $this->events->attach('foo.bar', function ($e) { $e->stopPropagation(true); return 'nada'; }, 3);
        $this->events->attach('foo.bar', function ($e) { return 'found'; }, 2);
        $this->events->attach('foo.bar', function ($e) { return 'zero'; }, 1);
        // @codingStandardsIgnoreEnd

        $responses = $this->events->trigger('foo.bar');
        $this->assertInstanceOf('Zend\EventManager\ResponseCollection', $responses);
        $this->assertTrue($responses->stopped());
        $this->assertEquals('nada', $responses->last());
        $this->assertTrue($responses->contains('bogus'));
        $this->assertFalse($responses->contains('found'));
        $this->assertFalse($responses->contains('zero'));
    }

    public function testCanAlterParametersWithinAEvent()
    {
        // @codingStandardsIgnoreStart
        $this->events->attach('foo.bar', function ($e) { $e->setParam('foo', 'bar'); });
        $this->events->attach('foo.bar', function ($e) { $e->setParam('bar', 'baz'); });
        // @codingStandardsIgnoreEnd
        $this->events->attach('foo.bar', function ($e) {
            $foo = $e->getParam('foo', '__NO_FOO__');
            $bar = $e->getParam('bar', '__NO_BAR__');
            return $foo . ":" . $bar;
        });

        $responses = $this->events->trigger('foo.bar');
        $this->assertEquals('bar:baz', $responses->last());
    }

    public function testParametersArePassedToEventByReference()
    {
        $params = [ 'foo' => 'bar', 'bar' => 'baz'];
        $args   = $this->events->prepareArgs($params);

        // @codingStandardsIgnoreStart
        $this->events->attach('foo.bar', function ($e) { $e->setParam('foo', 'FOO'); });
        $this->events->attach('foo.bar', function ($e) { $e->setParam('bar', 'BAR'); });
        // @codingStandardsIgnoreEnd

        $responses = $this->events->trigger('foo.bar', $this, $args);
        $this->assertEquals('FOO', $args['foo']);
        $this->assertEquals('BAR', $args['bar']);
    }

    public function testCanPassObjectForEventParameters()
    {
        $params = (object) [ 'foo' => 'bar', 'bar' => 'baz'];
        // @codingStandardsIgnoreStart
        $this->events->attach('foo.bar', function ($e) { $e->setParam('foo', 'FOO'); });
        $this->events->attach('foo.bar', function ($e) { $e->setParam('bar', 'BAR'); });
        // @codingStandardsIgnoreEnd

        $responses = $this->events->trigger('foo.bar', $this, $params);
        $this->assertEquals('FOO', $params->foo);
        $this->assertEquals('BAR', $params->bar);
    }

    public function testCanPassEventObjectAsSoleArgumentToTriggerEvent()
    {
        $event = new Event();
        $event->setName(__FUNCTION__);
        $event->setTarget($this);
        $event->setParams(['foo' => 'bar']);
        $this->events->attach(__FUNCTION__, function ($e) {
            return $e;
        });
        $responses = $this->events->triggerEvent($event);
        $this->assertSame($event, $responses->last());
    }

    public function testCanPassEventObjectAndCallbackToTriggerEventUntil()
    {
        $event = new Event();
        $event->setName(__FUNCTION__);
        $event->setTarget($this);
        $event->setParams(['foo' => 'bar']);
        $this->events->attach(__FUNCTION__, function ($e) {
            return $e;
        });
        $responses = $this->events->triggerEventUntil(function ($r) {
            return ($r instanceof EventInterface);
        }, $event);
        $this->assertTrue($responses->stopped());
        $this->assertSame($event, $responses->last());
    }

    public function testIdentifiersAreNotInjectedWhenNoSharedManagerProvided()
    {
        $events = new EventManager(null, [__CLASS__, get_class($this)]);
        $identifiers = $events->getIdentifiers();
        $this->assertInternalType('array', $identifiers);
        $this->assertEmpty($identifiers);
    }

    public function testDuplicateIdentifiersAreNotRegistered()
    {
        $sharedEvents = $this->prophesize(SharedEventManagerInterface::class)->reveal();
        $events = new EventManager($sharedEvents, [__CLASS__, get_class($this)]);
        $identifiers = $events->getIdentifiers();
        $this->assertSame(count($identifiers), 1);
        $this->assertSame($identifiers[0], __CLASS__);
        $events->addIdentifiers([__CLASS__]);
        $this->assertSame(count($identifiers), 1);
        $this->assertSame($identifiers[0], __CLASS__);
    }

    public function testIdentifierGetterSetters()
    {
        $identifiers = ['foo', 'bar'];
        $this->events->setIdentifiers($identifiers);
        $this->assertSame($this->events->getIdentifiers(), $identifiers);
        $identifiers[] = 'baz';
        $this->events->addIdentifiers($identifiers);

        // This is done because the keys do not matter, just the values
        $expectedIdentifiers = $this->events->getIdentifiers();
        sort($expectedIdentifiers);
        sort($identifiers);
        $this->assertSame($expectedIdentifiers, $identifiers);
    }

    public function testListenersAttachedWithWildcardAreTriggeredForAllEvents()
    {
        $test         = new stdClass;
        $test->events = [];
        $callback     = function ($e) use ($test) {
            $test->events[] = $e->getName();
        };

        $this->events->attach('*', $callback);

        foreach (['foo', 'bar', 'baz'] as $event) {
            $this->events->trigger($event);
            $this->assertContains($event, $test->events);
        }
    }

    public function testTriggerSetsStopPropagationFlagToFalse()
    {
        $marker = (object) ['propagationIsStopped' => true];
        $this->events->attach('foo', function ($e) use ($marker) {
            $marker->propagationIsStopped = $e->propagationIsStopped();
        });

        $event = new Event();
        $event->setName('foo');
        $event->stopPropagation(true);
        $this->events->triggerEvent($event);

        $this->assertFalse($marker->propagationIsStopped);
        $this->assertFalse($event->propagationIsStopped());
    }

    public function testTriggerEventUntilSetsStopPropagationFlagToFalse()
    {
        $marker = (object) ['propagationIsStopped' => true];
        $this->events->attach('foo', function ($e) use ($marker) {
            $marker->propagationIsStopped = $e->propagationIsStopped();
        });

        $criteria = function ($r) {
            return false;
        };
        $event = new Event();
        $event->setName('foo');
        $event->stopPropagation(true);
        $this->events->triggerEventUntil($criteria, $event);

        $this->assertFalse($marker->propagationIsStopped);
        $this->assertFalse($event->propagationIsStopped());
    }

    public function testCreatesAnEventPrototypeAtInstantiation()
    {
        $this->assertAttributeInstanceOf(EventInterface::class, 'eventPrototype', $this->events);
    }

    public function testSetEventPrototype()
    {
        $event = $this->prophesize(EventInterface::class)->reveal();
        $this->events->setEventPrototype($event);

        $this->assertAttributeSame($event, 'eventPrototype', $this->events);
    }

    public function testSharedManagerClearListenersReturnsFalse()
    {
        $shared = new SharedEventManager();
        $this->assertFalse($shared->clearListeners('foo'));
    }

    public function testResponseCollectionLastReturnsNull()
    {
        $responses = $this->events->trigger('string.transform', $this, ['string' => ' foo ']);
        $this->assertNull($responses->last());
    }

    public function testCanAddWildcardListenersAfterFirstTrigger()
    {
        $this->events->attach('foo', function ($e) {
            $this->assertEquals('foo', $e->getName());
        });
        $this->events->trigger('foo');

        $triggered = false;
        $this->events->attach('*', function ($e) use (&$triggered) {
            $this->assertEquals('foo', $e->getName());
            $triggered = true;
        });
        $this->events->trigger('foo');
        $this->assertTrue($triggered, 'Wildcard listener was not triggered');
    }

    public function testCanInjectSharedManagerDuringConstruction()
    {
        $shared = $this->prophesize(SharedEventManagerInterface::class)->reveal();
        $events = new EventManager($shared);
        $this->assertSame($shared, $events->getSharedManager());
    }

    public function invalidEventsForAttach()
    {
        return [
            'null'                   => [null],
            'true'                   => [true],
            'false'                  => [false],
            'zero'                   => [0],
            'int'                    => [1],
            'zero-float'             => [0.0],
            'float'                  => [1.1],
            'array'                  => [['test1', 'test2']],
            'non-traversable-object' => [(object) ['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider invalidEventsForAttach
     */
    public function testAttachRaisesExceptionForInvalidEventType($event)
    {
        $callback = function () {
        };
        $this->setExpectedException(Exception\InvalidArgumentException::class, 'string');
        $this->events->attach($event, $callback);
    }

    public function testCanClearAllListenersForAnEvent()
    {
        $events   = ['foo', 'bar', 'baz'];
        $listener = function ($e) {
        };
        foreach ($events as $event) {
            $this->events->attach($event, $listener);
        }

        $this->assertEquals($events, $this->getEventListFromManager($this->events));
        $this->events->clearListeners('foo');
        $this->assertCount(
            0,
            $this->getListenersForEvent('foo', $this->events),
            'Event foo listeners were not cleared'
        );

        foreach (['bar', 'baz'] as $event) {
            $this->assertCount(
                1,
                $this->getListenersForEvent($event, $this->events),
                sprintf(
                    'Event %s listeners were cleared and should not have been',
                    $event
                )
            );
        }
    }

    public function testWillTriggerSharedListeners()
    {
        $name      = __FUNCTION__;
        $triggered = false;

        $shared = new SharedEventManager();
        $shared->attach(__CLASS__, $name, function ($event) use ($name, &$triggered) {
            $this->assertEquals($name, $event->getName());
            $triggered = true;
        });

        $events = new EventManager($shared, [__CLASS__]);

        $events->trigger(__FUNCTION__);
        $this->assertTrue($triggered, 'Shared listener was not triggered');
    }

    public function testWillTriggerSharedWildcardListeners()
    {
        $name      = __FUNCTION__;
        $triggered = false;

        $shared = new SharedEventManager();
        $shared->attach('*', $name, function ($event) use ($name, &$triggered) {
            $this->assertEquals($name, $event->getName());
            $triggered = true;
        });

        $events = new EventManager($shared, [__CLASS__]);

        $events->trigger(__FUNCTION__);
        $this->assertTrue($triggered, 'Shared listener was not triggered');
    }

    /**
     * @depends testAttachShouldAddListenerToEvent
     */
    public function testCanDetachListenerFromNamedEvent($dependencies)
    {
        $event    = $dependencies['event'];
        $events   = $dependencies['events'];
        $listener = $dependencies['listener'];

        $events->detach($listener, $event);

        $listeners = $this->getListenersForEvent($event, $events);
        $this->assertCount(0, $listeners);
        $this->assertNotContains($listener, $listeners);
    }

    public function testDetachDoesNothingIfEventIsNotPresentInManager()
    {
        $callback = function ($e) {
        };
        $this->events->attach('foo', $callback);
        $this->events->detach($callback, 'bar');
        $listeners = $this->getListenersForEvent('foo', $this->events);
        // get first (and only) priority queue from listeners
        $listeners = array_shift($listeners);
        $this->assertContains($callback, $listeners);
    }

    /**
     * @group fail
     */
    public function testCanDetachWildcardListeners()
    {
        $events   = ['foo', 'bar'];
        $listener = function ($e) {
            return 'non-wildcard';
        };
        $wildcardListener = function ($e) {
            return 'wildcard';
        };

        array_walk($events, function ($event) use ($listener) {
            $this->events->attach($event, $listener);
        });
        $this->events->attach('*', $wildcardListener);

        $this->events->detach($wildcardListener, '*'); // Semantically the same as null

        // First, check the wildcard event queue
        $listeners = $this->getListenersForEvent('*', $this->events);
        $this->assertEmpty($listeners);

        // Next, verify it's not in any of the specific event queues
        foreach ($events as $event) {
            $listeners = $this->getListenersForEvent($event, $this->events);
            // Get listeners for first and only priority queue
            $listeners = array_shift($listeners);
            $this->assertCount(1, $listeners);
            $this->assertNotContains($wildcardListener, $listeners);
        }

        return [
            'event_names'  => $events,
            'events'       => $this->events,
            'not_contains' => 'wildcard',
        ];
    }

    /**
     * @depends testCanDetachWildcardListeners
     */
    public function testDetachedWildcardListenerWillNotBeTriggered($dependencies)
    {
        $eventNames  = $dependencies['event_names'];
        $events      = $dependencies['events'];
        $notContains = $dependencies['not_contains'];

        foreach ($eventNames as $event) {
            $results = $events->trigger($event);
            $this->assertFalse($results->contains($notContains), 'Discovered unexpected wildcard value in results');
        }
    }

    /**
     * @depends testAllowsPassingArrayOfEventNamesWhenAttaching
     */
    public function testNotPassingEventNameToDetachDetachesListenerFromAllEvents($dependencies)
    {
        $eventNames = $dependencies['event_names'];
        $events     = $dependencies['events'];
        $listener   = $dependencies['listener'];

        $events->detach($listener);

        foreach ($eventNames as $event) {
            $listeners = $this->getListenersForEvent($event, $events);
            $this->assertCount(0, $listeners);
            $this->assertNotContains($listener, $listeners);
        }
    }

    public function testCanDetachASingleListenerFromAnEventWithMultipleListeners()
    {
        $listener = function ($e) {
        };
        $alternateListener = clone $listener;

        $this->events->attach('foo', $listener);
        $this->events->attach('foo', $alternateListener);

        $listeners = $this->getListenersForEvent('foo', $this->events);
        // Get the listeners for the first priority queue
        $listeners = array_shift($listeners);
        $this->assertCount(
            2,
            $listeners,
            sprintf(
                'Listener count after attaching alternate listener for event %s was unexpected: %s',
                'foo',
                var_export($listeners, 1)
            )
        );
        $this->assertContains($listener, $listeners);
        $this->assertContains($alternateListener, $listeners);

        $this->events->detach($listener, 'foo');

        $listeners = $this->getListenersForEvent('foo', $this->events);
        // Get the listeners for the first priority queue
        $listeners = array_shift($listeners);
        $this->assertCount(
            1,
            $listeners,
            sprintf(
                "Listener count after detaching listener for event %s was unexpected;\nListeners: %s",
                'foo',
                var_export($listeners, 1)
            )
        );
        $this->assertNotContains($listener, $listeners);
        $this->assertContains($alternateListener, $listeners);
    }

    public function invalidEventsForDetach()
    {
        $events = $this->invalidEventsForAttach();
        unset($events['null']);
        return $events;
    }

    /**
     * @dataProvider invalidEventsForDetach
     */
    public function testPassingInvalidEventTypeToDetachRaisesException($event)
    {
        $listener = function ($e) {
        };

        $this->setExpectedException(Exception\InvalidArgumentException::class, 'string');
        $this->events->detach($listener, $event);
    }

    public function testDetachRemovesAllOccurrencesOfListenerForEvent()
    {
        $listener = function ($e) {
        };

        for ($i = 0; $i < 5; $i += 1) {
            $this->events->attach('foo', $listener, $i);
        }

        $listeners = $this->getListenersForEvent('foo', $this->events);
        $this->assertCount(5, $listeners);

        $this->events->detach($listener, 'foo');

        $listeners = $this->getListenersForEvent('foo', $this->events);
        $this->assertCount(0, $listeners);
        $this->assertNotContains($listener, $listeners);
    }

    public function eventsMissingNames()
    {
        $event = $this->prophesize(EventInterface::class);
        $event->getName()->willReturn('');
        $callback = function ($result) {
        };

        // @codingStandardsIgnoreStart
        //                                      [ event,             method to trigger, callback ]
        return [
            'trigger-empty-string'           => ['',               'trigger',           null],
            'trigger-until-empty-string'     => ['',               'triggerUntil',      $callback],
            'trigger-event-empty-name'       => [$event->reveal(), 'triggerEvent',      null],
            'trigger-event-until-empty-name' => [$event->reveal(), 'triggerEventUntil', $callback],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider eventsMissingNames
     */
    public function testTriggeringAnEventWithAnEmptyNameRaisesAnException($event, $method, $callback)
    {
        $this->setExpectedException(Exception\RuntimeException::class, 'missing a name');
        if ($callback) {
            $this->events->$method($callback, $event);
        } else {
            $this->events->$method($event);
        }
    }

    public function testTriggerEventAcceptsEventInstanceAndTriggersListeners()
    {
        $event = $this->prophesize(EventInterface::class);
        $event->getName()->willReturn('test');
        $event->stopPropagation(false)->shouldBeCalled();
        $event->propagationIsStopped()->willReturn(false);

        $triggered = false;
        $this->events->attach('test', function ($e) use ($event, &$triggered) {
            $this->assertSame($event->reveal(), $e);
            $triggered = true;
        });

        $this->events->triggerEvent($event->reveal());
        $this->assertTrue($triggered, 'Listener for event was not triggered');
    }

    public function testTriggerEventUntilAcceptsEventInstanceAndTriggersListenersUntilCallbackEvaluatesTrue()
    {
        $event = $this->prophesize(EventInterface::class);
        $event->getName()->willReturn('test');
        $event->stopPropagation(false)->shouldBeCalled();
        $event->propagationIsStopped()->willReturn(false);

        $callback = function ($result) {
            return ($result === true);
        };

        $triggeredOne = false;
        $this->events->attach('test', function ($e) use ($event, &$triggeredOne) {
            $this->assertSame($event->reveal(), $e);
            $triggeredOne = true;
        });

        $triggeredTwo = false;
        $this->events->attach('test', function ($e) use ($event, &$triggeredTwo) {
            $this->assertSame($event->reveal(), $e);
            $triggeredTwo = true;
            return true;
        });

        $this->events->attach('test', function ($e) {
            $this->fail('Third listener was triggered and should not have been');
        });

        $this->events->triggerEventUntil($callback, $event->reveal());
        $this->assertTrue($triggeredOne, 'First Listener for event was not triggered');
        $this->assertTrue($triggeredTwo, 'First Listener for event was not triggered');
    }
}
