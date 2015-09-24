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
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Zend\EventManager\Exception;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\SharedListenerAggregateInterface;

class SharedEventManagerTest extends TestCase
{
    public function setUp()
    {
        $this->callback = function ($e) {
        };
        $this->manager = new SharedEventManager();
    }

    public function invalidIdentifiers()
    {
        return [
            'null'                   => [null],
            'true'                   => [true],
            'false'                  => [false],
            'zero'                   => [0],
            'int'                    => [1],
            'zero-float'             => [0.0],
            'float'                  => [1.1],
            'empty-string'           => [''],
            'array'                  => [['test', 'foo']],
            'non-traversable-object' => [(object) ['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider invalidIdentifiers
     */
    public function testAttachRaisesExceptionForInvalidIdentifer($identifier)
    {
        $this->setExpectedException(Exception\InvalidArgumentException::class, 'identifier');
        $this->manager->attach($identifier, 'foo', $this->callback);
    }

    public function invalidEventNames()
    {
        return [
            'null'                   => [null],
            'true'                   => [true],
            'false'                  => [false],
            'zero'                   => [0],
            'int'                    => [1],
            'zero-float'             => [0.0],
            'float'                  => [1.1],
            'empty-string'           => [''],
            'array'                  => [['foo', 'bar']],
            'non-traversable-object' => [(object) ['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider invalidEventNames
     */
    public function testAttachRaisesExceptionForInvalidEvent($event)
    {
        $this->setExpectedException(Exception\InvalidArgumentException::class, 'event');
        $this->manager->attach('foo', $event, $this->callback);
    }

    public function testCanAttachToSharedManager()
    {
        $this->manager->attach('IDENTIFIER', 'EVENT', $this->callback);

        $this->assertSame([[
            'listener' => $this->callback,
            'priority' => 1,
        ]], $this->manager->getListeners('IDENTIFIER', 'EVENT'));
    }

    public function detachIdentifierAndEvent()
    {
        return [
            'null-identifier-and-null-event' => [null, null],
            'same-identifier-and-null-event' => ['IDENTIFIER', null],
            'null-identifier-and-same-event' => [null, 'EVENT'],
            'same-identifier-and-same-event' => ['IDENTIFIER', 'EVENT'],
        ];
    }

    /**
     * @dataProvider detachIdentifierAndEvent
     * @group fail
     */
    public function testCanDetachFromSharedManagerUsingIdentifierAndEvent($identifier, $event)
    {
        $this->manager->attach('IDENTIFIER', 'EVENT', $this->callback);
        $this->manager->detach($this->callback, $identifier, $event);
        $this->assertSame([], $this->manager->getListeners('IDENTIFIER', 'EVENT'));
    }

    public function testGetEventsReturnsEmptyListIfIdentifierDoesNotExist()
    {
        $this->assertEquals([], $this->manager->getEvents('IDENTIFIER'));
    }

    public function testGetEventsReturnsAllEventsWithSharedListeners()
    {
        $this->manager->attach('IDENTIFIER', 'EVENT', $this->callback);
        $this->manager->attach('IDENTIFIER', __FUNCTION__, $this->callback);
        $this->manager->attach('IDENTIFIER', __METHOD__, $this->callback);
        $this->manager->attach('ALTERNATE', 'SHOULD NOT BE FOUND', $this->callback);

        $this->assertEquals([
            'EVENT',
            __FUNCTION__,
            __METHOD__,
        ], $this->manager->getEvents('IDENTIFIER'));
    }

    public function testGetEventsOmitsWildcardEvent()
    {
        $this->manager->attach('IDENTIFIER', 'EVENT', $this->callback);
        $this->manager->attach('IDENTIFIER', '*', $this->callback);

        $this->assertEquals([
            'EVENT',
        ], $this->manager->getEvents('IDENTIFIER'));
    }

    public function testGetEventsIncludesListenersOnWildcardIdentifiers()
    {
        $this->manager->attach('IDENTIFIER', 'EVENT', $this->callback);
        $this->manager->attach('IDENTIFIER', __FUNCTION__, $this->callback);
        $this->manager->attach('IDENTIFIER', __METHOD__, $this->callback);
        $this->manager->attach('*', 'WILDCARD', $this->callback);

        $this->assertEquals([
            'EVENT',
            __FUNCTION__,
            __METHOD__,
            'WILDCARD'
        ], $this->manager->getEvents('IDENTIFIER'));
    }

    public function testWhenEventIsNullAndNoListenersFoundForIdentiferGetListenersWillReturnEmptyList()
    {
        $test = $this->manager->getListeners('IDENTIFIER');
        $this->assertInternalType('array', $test);
        $this->assertCount(0, $test);
    }

    public function testWhenEventIsNullGetListenersReturnsAllListenersForAllEventsIncludingWildcardEventOnIdentifier()
    {
        $callback1 = clone $this->callback;
        $callback2 = clone $this->callback;
        $callback3 = clone $this->callback;
        $callback4 = clone $this->callback;

        $this->manager->attach('IDENTIFIER', 'EVENT', $callback1);
        $this->manager->attach('IDENTIFIER', 'ALTERNATE', $callback2);
        $this->manager->attach('IDENTIFIER', 'EVENT', $callback3);
        $this->manager->attach('IDENTIFIER', '*', $callback4);

        $test = $this->manager->getListeners('IDENTIFIER');
        $this->assertEquals([
            'EVENT' => [
                [
                    'listener' => $callback1,
                    'priority' => 1,
                ],
                [
                    'listener' => $callback3,
                    'priority' => 1,
                ],
            ],
            'ALTERNATE' => [
                [
                    'listener' => $callback2,
                    'priority' => 1,
                ],
            ],
            '*' => [
                [
                    'listener' => $callback4,
                    'priority' => 1,
                ],
            ],
        ], $test);
    }

    public function testWhenEventIsNullAndWildcardIdentifierProvidedGetListenersReturnsWildcardIdentifiedListeners()
    {
        $callback1 = clone $this->callback;
        $callback2 = clone $this->callback;
        $callback3 = clone $this->callback;
        $callback4 = clone $this->callback;

        $this->manager->attach('*', 'EVENT', $callback1);
        $this->manager->attach('*', 'ALTERNATE', $callback2);
        $this->manager->attach('*', 'EVENT', $callback3);
        $this->manager->attach('*', '*', $callback4);

        $test = $this->manager->getListeners('*');
        $this->assertEquals([
            'EVENT' => [
                [
                    'listener' => $callback1,
                    'priority' => 1,
                ],
                [
                    'listener' => $callback3,
                    'priority' => 1,
                ],
            ],
            'ALTERNATE' => [
                [
                    'listener' => $callback2,
                    'priority' => 1,
                ],
            ],
            '*' => [
                [
                    'listener' => $callback4,
                    'priority' => 1,
                ],
            ],
        ], $test);
    }

    public function testWhenEventIsProvidedAndNoListenersFoundForIdentiferGetListenersWillReturnEmptyList()
    {
        $test = $this->manager->getListeners('IDENTIFIER', 'EVENT');
        $this->assertInternalType('array', $test);
        $this->assertCount(0, $test);
    }

    public function testWhenEventIsProvidedGetListenersReturnsAllListenersIncludingWildcardListeners()
    {
        $callback1 = clone $this->callback;
        $callback2 = clone $this->callback;
        $callback3 = clone $this->callback;
        $callback4 = clone $this->callback;

        $this->manager->attach('IDENTIFIER', 'EVENT', $callback1);
        $this->manager->attach('IDENTIFIER', '*', $callback2);
        $this->manager->attach('*', 'EVENT', $callback3);
        $this->manager->attach('IDENTIFIER', 'EVENT', $callback4);

        $test = $this->manager->getListeners('IDENTIFIER', 'EVENT');
        $this->assertEquals([
            [
                'listener' => $callback1,
                'priority' => 1,
            ],
            [
                'listener' => $callback4,
                'priority' => 1,
            ],
            [
                'listener' => $callback2,
                'priority' => 1,
            ],
        ], $test);
    }

    public function testClearListenersWhenNoEventIsProvidedRemovesAllListenersForTheIdentifier()
    {
        $this->manager->attach('IDENTIFIER', 'EVENT', $this->callback);
        $this->manager->attach('IDENTIFIER', '*', $this->callback);
        $this->manager->attach('*', 'EVENT', $this->callback);
        $this->manager->attach('IDENTIFIER', 'EVENT', $this->callback);

        $this->manager->clearListeners('IDENTIFIER');

        $listeners = $this->manager->getListeners('IDENTIFIER');
        $this->assertInternalType('array', $listeners, 'Unexpected return value from getListeners()');
        $this->assertCount(0, $listeners, sprintf('Listener list is non-empty: %s', var_export($listeners, 1)));

        $this->assertCount(
            1,
            $this->manager->getListeners('*', 'EVENT'),
            'Expected listener on * identifier not found'
        );
    }

    public function testClearListenersRemovesAllListenersForGivenIdentifierAndEvent()
    {
        $this->manager->attach('IDENTIFIER', 'EVENT', $this->callback);
        $this->manager->attach('IDENTIFIER', 'ALTERNATE', $this->callback);
        $this->manager->attach('*', 'EVENT', $this->callback);
        $this->manager->attach('IDENTIFIER', 'EVENT', $this->callback);

        $this->manager->clearListeners('IDENTIFIER', 'EVENT');

        $listeners = $this->manager->getListeners('IDENTIFIER', 'EVENT');
        $this->assertInternalType('array', $listeners, 'Unexpected return value from getListeners() for event EVENT');
        $this->assertCount(
            0,
            $listeners,
            sprintf('Listener list for EVENT is non-empty: %s', var_export($listeners, 1))
        );

        $listeners = $this->manager->getListeners('IDENTIFIER', 'ALTERNATE');
        $this->assertInternalType(
            'array',
            $listeners,
            'Unexpected return value from getListeners() for event ALTERNATE'
        );
        $this->assertCount(1, $listeners, 'Unexpected listener list for event ALTERNATE');

        $this->assertCount(
            1,
            $this->manager->getListeners('*', 'EVENT'),
            'Expected listener on * identifier not found'
        );
    }

    public function testClearListenersDoesNotRemoveWildcardListenersWhenEventIsProvided()
    {
        $callback = clone $this->callback;
        $this->manager->attach('IDENTIFIER', 'EVENT', $this->callback);
        $this->manager->attach('IDENTIFIER', '*', $callback);
        $this->manager->attach('*', 'EVENT', $this->callback);
        $this->manager->attach('IDENTIFIER', 'EVENT', $this->callback);

        $this->manager->clearListeners('IDENTIFIER', 'EVENT');

        $listeners = $this->manager->getListeners('IDENTIFIER', 'EVENT');
        $expected  = [
            [
                'listener' => $callback,
                'priority' => 1,
            ]
        ];
        $this->assertEquals(
            $expected,
            $listeners,
            'Listener list after clear operation does not include wildcard listener'
        );

        $listeners = $this->manager->getListeners('IDENTIFIER', '*');
        $this->assertEquals(
            $expected,
            $listeners,
            'Wildcard Listener after clear operation does not match wildcard listener'
        );

        $this->assertCount(
            1,
            $this->manager->getListeners('*', 'EVENT'),
            'Expected listener on * identifier not found'
        );
    }

    public function testClearListenersDoesNothingIfNoEventsRegisteredForIdentifier()
    {
        $callback = clone $this->callback;
        $this->manager->attach('IDENTIFIER', 'NOTEVENT', $this->callback);
        $this->manager->attach('*', 'EVENT', $this->callback);

        $this->manager->clearListeners('IDENTIFIER', 'EVENT');
        $this->assertEquals([], $this->manager->getListeners('IDENTIFIER', 'EVENT'));
    }
}
