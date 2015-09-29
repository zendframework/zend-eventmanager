<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace ZendTest\EventManager\TestAsset;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;

/**
 * @group      Zend_EventManager
 */
class MockAggregate implements ListenerAggregateInterface
{

    protected $listeners = [];
    public $priority;

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->priority = $priority;

        $listeners = [];
        $listeners[] = $events->attach('foo.bar', [ $this, 'fooBar' ]);
        $listeners[] = $events->attach('foo.baz', [ $this, 'fooBaz' ]);

        $this->listeners[ \spl_object_hash($events) ] = $listeners;

        return __METHOD__;
    }

    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners[ \spl_object_hash($events) ] as $listener) {
            $events->detach($listener);
        }

        return __METHOD__;
    }

    public function fooBar()
    {
        return __METHOD__;
    }

    public function fooBaz()
    {
        return __METHOD__;
    }
}
