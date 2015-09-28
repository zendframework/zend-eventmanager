<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace ZendTest\EventManager;

use SplQueue;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;
use Zend\EventManager\SharedEventManager;

class EventManagerPriorityTest extends TestCase
{
    public function setUp()
    {
        $this->identifiers  = [__CLASS__];
        $this->sharedEvents = new SharedEventManager();
        $this->events = new EventManager($this->sharedEvents, $this->identifiers);
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

    public function testTriggersListenersOfDifferentPrioritiesInPriorityOrder()
    {
        for ($i = -1; $i < 5; $i += 1) {
            $this->events->attach('test', $this->createListener($i), $i);
        }

        $event = $this->createEvent();
        $this->events->triggerEvent($event);
        $values = iterator_to_array($event->getParam('accumulator'));
        $this->assertEquals(
            [4, 3, 2, 1, 0, -1],
            $values,
            sprintf("Did not receive values in priority order: %s\n", var_export($values, 1))
        );
    }

    public function testTriggersListenersOfSamePriorityInAttachmentOrder()
    {
        for ($i = -1; $i < 5; $i += 1) {
            $this->events->attach('test', $this->createListener($i));
        }

        $event = $this->createEvent();
        $this->events->triggerEvent($event);
        $values = iterator_to_array($event->getParam('accumulator'));
        $this->assertEquals(
            [-1, 0, 1, 2, 3, 4],
            $values,
            sprintf("Did not receive values in attachment order: %s\n", var_export($values, 1))
        );
    }

    public function testTriggersWildcardListenersAfterExplicitListenersOfSamePriority()
    {
        $this->events->attach('*', $this->createListener(2), 5);
        $this->events->attach('test', $this->createListener(1), 5);
        $this->events->attach('*', $this->createListener(3), 5);

        $event = $this->createEvent();
        $this->events->triggerEvent($event);
        $values = iterator_to_array($event->getParam('accumulator'));
        $this->assertEquals(
            [1, 2, 3],
            $values,
            sprintf("Did not receive wildcard values after explicit listeners: %s\n", var_export($values, 1))
        );
    }

    public function testTriggersSharedListenersAfterWildcardListenersOfSamePriority()
    {
        $this->sharedEvents->attach(__CLASS__, 'test', $this->createListener(2), 5);
        $this->events->attach('*', $this->createListener(1), 5);
        $this->sharedEvents->attach(__CLASS__, 'test', $this->createListener(3), 5);

        $event = $this->createEvent();
        $this->events->triggerEvent($event);
        $values = iterator_to_array($event->getParam('accumulator'));
        $this->assertEquals(
            [1, 2, 3],
            $values,
            sprintf("Did not receive shared listener values after wildcard listeners: %s\n", var_export($values, 1))
        );
    }

    public function testTriggersSharedWildcardListenersAfterSharedListenersOfSamePriority()
    {
        $this->sharedEvents->attach(__CLASS__, '*', $this->createListener(2), 5);
        $this->sharedEvents->attach(__CLASS__, 'test', $this->createListener(1), 5);
        $this->sharedEvents->attach(__CLASS__, '*', $this->createListener(3), 5);

        $event = $this->createEvent();
        $this->events->triggerEvent($event);
        $values = iterator_to_array($event->getParam('accumulator'));
        $this->assertEquals(
            [1, 2, 3],
            $values,
            sprintf(
                "Did not receive shared wildcard listener values after shared listeners: %s\n",
                var_export($values, 1)
            )
        );
    }

    public function testTriggersSharedWildcardIdentifierListenersAfterWildcardSharedListenersOfSamePriority()
    {
        $this->sharedEvents->attach('*', 'test', $this->createListener(2), 5);
        $this->sharedEvents->attach(__CLASS__, '*', $this->createListener(1), 5);
        $this->sharedEvents->attach('*', 'test', $this->createListener(3), 5);

        $event = $this->createEvent();
        $this->events->triggerEvent($event);
        $values = iterator_to_array($event->getParam('accumulator'));
        $this->assertEquals(
            [1, 2, 3],
            $values,
            sprintf(
                "Did not receive wildcard identifier listener values after shared wildcard listeners: %s\n",
                var_export($values, 1)
            )
        );
    }

    public function testTriggersFullyWildcardSharedListenersAfterWildcardIdentifierListenersOfSamePriority()
    {
        $this->sharedEvents->attach('*', '*', $this->createListener(2), 5);
        $this->sharedEvents->attach('*', 'test', $this->createListener(1), 5);
        $this->sharedEvents->attach('*', '*', $this->createListener(3), 5);

        $event = $this->createEvent();
        $this->events->triggerEvent($event);
        $values = iterator_to_array($event->getParam('accumulator'));
        $this->assertEquals(
            [1, 2, 3],
            $values,
            sprintf(
                "Did not receive fully wildcard shared listener values after shared wildcard listeners: %s\n",
                var_export($values, 1)
            )
        );
    }

    public function testTriggeringMixOfLocalAndSharedAndWildcardListenersWorksAsExpected()
    {
        $this->sharedEvents->attach('*', '*', $this->createListener(1024), 1024);
        $this->sharedEvents->attach('*', '*', $this->createListener(1023), 1024);
        $this->events->attach('*', $this->createListener(1025), 1024);
        $this->events->attach('test', $this->createListener(1026), 1024);

        $this->sharedEvents->attach('*', 'test', $this->createListener(512), 512);
        $this->sharedEvents->attach('*', '*', $this->createListener(510), 512);
        $this->sharedEvents->attach('*', 'test', $this->createListener(511), 512);
        $this->events->attach('*', $this->createListener(513), 512);
        $this->events->attach('test', $this->createListener(514), 512);

        $this->sharedEvents->attach(__CLASS__, '*', $this->createListener(256), 256);
        $this->sharedEvents->attach('*', '*', $this->createListener(253), 256);
        $this->sharedEvents->attach('*', 'test', $this->createListener(254), 256);
        $this->sharedEvents->attach(__CLASS__, '*', $this->createListener(255), 256);
        $this->events->attach('*', $this->createListener(257), 256);
        $this->events->attach('test', $this->createListener(258), 256);

        $this->sharedEvents->attach(__CLASS__, 'test', $this->createListener(128), 128);
        $this->sharedEvents->attach(__CLASS__, '*', $this->createListener(126), 128);
        $this->sharedEvents->attach('*', '*', $this->createListener(123), 128);
        $this->sharedEvents->attach('*', 'test', $this->createListener(124), 128);
        $this->sharedEvents->attach(__CLASS__, '*', $this->createListener(125), 128);
        $this->sharedEvents->attach(__CLASS__, 'test', $this->createListener(127), 128);
        $this->events->attach('*', $this->createListener(129), 128);
        $this->events->attach('test', $this->createListener(130), 128);

        $this->events->attach('*', $this->createListener(64), 64);
        $this->events->attach('*', $this->createListener(63), 64);
        $this->events->attach('test', $this->createListener(32), 32);
        $this->events->attach('*', $this->createListener(30), 32);
        $this->events->attach('test', $this->createListener(31), 32);

        $event = $this->createEvent();
        $this->events->triggerEvent($event);

        $values = $report = iterator_to_array($event->getParam('accumulator'));
        $this->assertCount(28, $values);
        $original = array_shift($values);
        do {
            $compare = array_shift($values);
            $this->assertLessThan(
                $original,
                $compare,
                sprintf("Did not receive values in expected order: %s\n", var_export($report, 1))
            );
            $original = $compare;
        } while (count($values));
    }
}
