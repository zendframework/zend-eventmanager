<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\EventManager;

use ArrayIterator;
use ReflectionProperty;
use stdClass;
use Traversable;
use Zend\EventManager\Event;
use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;

/**
 * @group      Zend_EventManager
 */
class EventManagerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (isset($this->message)) {
            unset($this->message);
        }
        $this->events = new EventManager;
    }

    public function testAttachShouldAddListenerToEvent()
    {
        $listener  = [$this, __METHOD__];
        $this->events->attach('test', $listener);
        $listeners = $this->events->getListeners('test');
        $this->assertEquals(1, count($listeners));
        $this->assertContains($listener, $listeners);
    }

    public function testAttachShouldAddEventIfItDoesNotExist()
    {
        $events = $this->events->getEvents();
        $this->assertEmpty($events, var_export($events, 1));
        $listener = $this->events->attach('test', [$this, __METHOD__]);
        $events = $this->events->getEvents();
        $this->assertNotEmpty($events);
        $this->assertContains('test', $events);
    }

    public function testAllowsPassingArrayOfEventNamesWhenAttaching()
    {
        $callback = function ($e) {
            return $e->getName();
        };
        $this->events->attach(['foo', 'bar'], $callback);

        foreach (['foo', 'bar'] as $event) {
            $listeners = $this->events->getListeners($event);
            $this->assertNotEmpty($listeners);
            foreach ($listeners as $listener) {
                $this->assertSame($callback, $listener);
            }
        }
    }

    public function testPassingArrayOfEventNamesWhenAttachingReturnsArrayOfCallbackHandlers()
    {
        $callback = function ($e) {
            return $e->getName();
        };
        $this->events->attach(['foo', 'bar'], $callback);

        $events = $this->events->getEvents();
        $this->assertEquals(['foo', 'bar'], $events);

        foreach ($events as $event) {
            $listeners = $this->events->getListeners($event);
            $this->assertInstanceOf(Traversable::class, $listeners);
            foreach ($listeners as $listener) {
                $this->assertSame($callback, $listener);
            }
        }
    }

    public function testRetrievingAttachedListenersShouldReturnEmptyArrayWhenEventDoesNotExist()
    {
        $listeners = $this->events->getListeners('test');
        $this->assertEquals(0, count($listeners));
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
        $responses = $this->events->trigger(
            'foo.bar',
            $this,
            ['string' => 'foo', 'search' => 'f'],
            [$this, 'evaluateStringCallback']
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
        return (!$value);
    }

    public function testTriggerUntilShouldMarkResponseCollectionStoppedWhenConditionMet()
    {
        // @codingStandardsIgnoreStart
        $this->events->attach('foo.bar', function () { return 'bogus'; }, 4);
        $this->events->attach('foo.bar', function () { return 'nada'; }, 3);
        $this->events->attach('foo.bar', function () { return 'found'; }, 2);
        $this->events->attach('foo.bar', function () { return 'zero'; }, 1);
        // @codingStandardsIgnoreEnd
        $responses = $this->events->trigger('foo.bar', $this, [], function ($result) {
            return ($result === 'found');
        });
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
        $responses = $this->events->trigger('foo.bar', $this, [], function ($result) {
            return ($result === 'found');
        });
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
        $responses = $this->events->trigger('foo.bar', $this, [], function ($result) {
            return ($result === 'never found');
        });
        $this->assertInstanceOf('Zend\EventManager\ResponseCollection', $responses);
        $this->assertFalse($responses->stopped());
        $this->assertEquals('zero', $responses->last());
    }

    public function testCanAttachListenerAggregate()
    {
        $aggregate = new TestAsset\MockAggregate();
        $this->events->attachAggregate($aggregate);
        $events = $this->events->getEvents();
        foreach (['foo.bar', 'foo.baz'] as $event) {
            $this->assertContains($event, $events);
        }
    }

    public function testAttachAggregateReturnsAttachOfListenerAggregate()
    {
        $aggregate = new TestAsset\MockAggregate();
        $method    = $this->events->attachAggregate($aggregate);
        $this->assertSame('ZendTest\EventManager\TestAsset\MockAggregate::attach', $method);
    }

    public function testAttachAggregateAcceptsOptionalPriorityValue()
    {
        $aggregate = new TestAsset\MockAggregate();
        $this->events->attachAggregate($aggregate, 1);
        $this->assertEquals(1, $aggregate->priority);
    }

    public function testAttachAggregateAcceptsOptionalPriorityValueViaAttachCallbackArgument()
    {
        $aggregate = new TestAsset\MockAggregate();
        $this->events->attachAggregate($aggregate, 1);
        $this->assertEquals(1, $aggregate->priority);
    }

    public function testCallingEventsStopPropagationMethodHaltsEventEmission()
    {
        // @codingStandardsIgnoreStart
        $this->events->attach('foo.bar', function ($e) { return 'bogus'; }, 4);
        $this->events->attach('foo.bar', function ($e) { $e->stopPropagation(true); return 'nada'; }, 3);
        $this->events->attach('foo.bar', function ($e) { return 'found'; }, 2);
        $this->events->attach('foo.bar', function ($e) { return 'zero'; }, 1);
        // @codingStandardsIgnoreEnd
        $responses = $this->events->trigger('foo.bar', $this, []);
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
        $responses = $this->events->trigger('foo.bar', $this, []);
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

    public function testCanPassEventObjectAsSoleArgumentToTrigger()
    {
        $event = new Event();
        $event->setName(__FUNCTION__);
        $event->setTarget($this);
        $event->setParams(['foo' => 'bar']);
        $this->events->attach(__FUNCTION__, function ($e) {
            return $e;
        });
        $responses = $this->events->trigger($event);
        $this->assertSame($event, $responses->last());
    }

    public function testCanPassEventNameAndEventObjectAsSoleArgumentsToTrigger()
    {
        $event = new Event();
        $event->setTarget($this);
        $event->setParams(['foo' => 'bar']);
        $this->events->attach(__FUNCTION__, function ($e) {
            return $e;
        });
        $responses = $this->events->trigger(__FUNCTION__, $event);
        $this->assertSame($event, $responses->last());
        $this->assertEquals(__FUNCTION__, $event->getName());
    }

    public function testCanPassEventObjectAsArgvToTrigger()
    {
        $event = new Event();
        $event->setParams(['foo' => 'bar']);
        $this->events->attach(__FUNCTION__, function ($e) {
            return $e;
        });
        $responses = $this->events->trigger(__FUNCTION__, $this, $event);
        $this->assertSame($event, $responses->last());
        $this->assertEquals(__FUNCTION__, $event->getName());
        $this->assertSame($this, $event->getTarget());
    }

    public function testCanPassEventObjectAndCallbackAsSoleArgumentsToTriggerUntil()
    {
        $event = new Event();
        $event->setName(__FUNCTION__);
        $event->setTarget($this);
        $event->setParams(['foo' => 'bar']);
        $this->events->attach(__FUNCTION__, function ($e) {
            return $e;
        });
        $responses = $this->events->trigger($event, function ($r) {
            return ($r instanceof EventInterface);
        });
        $this->assertTrue($responses->stopped());
        $this->assertSame($event, $responses->last());
    }

    public function testCanPassEventNameAndEventObjectAndCallbackAsSoleArgumentsToTriggerUntil()
    {
        $event = new Event();
        $event->setTarget($this);
        $event->setParams(['foo' => 'bar']);
        $this->events->attach(__FUNCTION__, function ($e) {
            return $e;
        });
        $responses = $this->events->trigger(__FUNCTION__, $event, function ($r) {
            return ($r instanceof EventInterface);
        });
        $this->assertTrue($responses->stopped());
        $this->assertSame($event, $responses->last());
        $this->assertEquals(__FUNCTION__, $event->getName());
    }

    public function testCanPassEventObjectAsArgvToTriggerUntil()
    {
        $event = new Event();
        $event->setParams(['foo' => 'bar']);
        $this->events->attach(__FUNCTION__, function ($e) {
            return $e;
        });
        $responses = $this->events->trigger(__FUNCTION__, $this, $event, function ($r) {
            return ($r instanceof EventInterface);
        });
        $this->assertTrue($responses->stopped());
        $this->assertSame($event, $responses->last());
        $this->assertEquals(__FUNCTION__, $event->getName());
        $this->assertSame($this, $event->getTarget());
    }

    public function testTriggerCanTakeAnOptionalCallbackArgumentToEmulateTriggerUntil()
    {
        $this->events->attach(__FUNCTION__, function ($e) {
            return $e;
        });

        // Four scenarios:
        // First: normal signature:
        $responses = $this->events->trigger(__FUNCTION__, $this, [], function ($r) {
            return ($r instanceof EventInterface);
        });
        $this->assertTrue($responses->stopped());

        // Second: Event as $argv parameter:
        $event = new Event();
        $responses = $this->events->trigger(__FUNCTION__, $this, $event, function ($r) {
            return ($r instanceof EventInterface);
        });
        $this->assertTrue($responses->stopped());

        // Third: Event as $target parameter:
        $event = new Event();
        $event->setTarget($this);
        $responses = $this->events->trigger(__FUNCTION__, $event, function ($r) {
            return ($r instanceof EventInterface);
        });
        $this->assertTrue($responses->stopped());

        // Fourth: Event as $event parameter:
        $event = new Event();
        $event->setTarget($this);
        $event->setName(__FUNCTION__);
        $responses = $this->events->trigger($event, function ($r) {
            return ($r instanceof EventInterface);
        });
        $this->assertTrue($responses->stopped());
    }

    public function testDuplicateIdentifiersAreNotRegistered()
    {
        $events = new EventManager([__CLASS__, get_class($this)]);
        $identifiers = $events->getIdentifiers();
        $this->assertSame(count($identifiers), 1);
        $this->assertSame($identifiers[0], __CLASS__);
        $events->addIdentifiers(__CLASS__);
        $this->assertSame(count($identifiers), 1);
        $this->assertSame($identifiers[0], __CLASS__);
    }

    public function testIdentifierGetterSettersWorkWithStrings()
    {
        $identifier1 = 'foo';
        $identifiers = [$identifier1];
        $this->assertInstanceOf('Zend\EventManager\EventManager', $this->events->setIdentifiers($identifier1));
        $this->assertSame($this->events->getIdentifiers(), $identifiers);
        $identifier2 = 'baz';
        $identifiers = [$identifier1, $identifier2];
        $this->assertInstanceOf('Zend\EventManager\EventManager', $this->events->addIdentifiers($identifier2));
        $this->assertSame($this->events->getIdentifiers(), $identifiers);
    }

    public function testIdentifierGetterSettersWorkWithArrays()
    {
        $identifiers = ['foo', 'bar'];
        $this->assertInstanceOf('Zend\EventManager\EventManager', $this->events->setIdentifiers($identifiers));
        $this->assertSame($this->events->getIdentifiers(), $identifiers);
        $identifiers[] = 'baz';
        $this->assertInstanceOf('Zend\EventManager\EventManager', $this->events->addIdentifiers($identifiers));

        // This is done because the keys doesn't matter, just the values
        $expectedIdentifiers = $this->events->getIdentifiers();
        sort($expectedIdentifiers);
        sort($identifiers);
        $this->assertSame($expectedIdentifiers, $identifiers);
    }

    public function testIdentifierGetterSettersWorkWithTraversables()
    {
        $identifiers = new ArrayIterator(['foo', 'bar']);
        $this->assertInstanceOf('Zend\EventManager\EventManager', $this->events->setIdentifiers($identifiers));
        $this->assertSame($this->events->getIdentifiers(), (array) $identifiers);
        $identifiers = new ArrayIterator(['foo', 'bar', 'baz']);
        $this->assertInstanceOf('Zend\EventManager\EventManager', $this->events->addIdentifiers($identifiers));

        // This is done because the keys doesn't matter, just the values
        $expectedIdentifiers = $this->events->getIdentifiers();
        sort($expectedIdentifiers);
        $identifiers = (array) $identifiers;
        sort($identifiers);
        $this->assertSame($expectedIdentifiers, $identifiers);
    }

    /**
     * @group fail
     */
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
        $event->stopPropagation(true);
        $this->events->trigger('foo', $event);

        $this->assertFalse($marker->propagationIsStopped);
        $this->assertFalse($event->propagationIsStopped());
    }

    public function testTriggerUntilSetsStopPropagationFlagToFalse()
    {
        $marker = (object) ['propagationIsStopped' => true];
        $this->events->attach('foo', function ($e) use ($marker) {
            $marker->propagationIsStopped = $e->propagationIsStopped();
        });

        $criteria = function ($r) {
            return false;
        };
        $event = new Event();
        $event->stopPropagation(true);
        $this->events->trigger('foo', $event, $criteria);

        $this->assertFalse($marker->propagationIsStopped);
        $this->assertFalse($event->propagationIsStopped());
    }

    public function testSetEventClass()
    {
        $eventClass = 'NewEventClass';
        $this->events->setEventClass($eventClass);

        $property = new ReflectionProperty($this->events, 'eventClass');
        $property->setAccessible(true);

        $this->assertEquals($eventClass, $property->getValue($this->events));
    }

    public function testSharedManagerGetEventsReturnsFalse()
    {
        $shared = new SharedEventManager;
        $this->assertFalse($shared->getEvents('foo'));
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
}
