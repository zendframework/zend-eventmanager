<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace ZendTest\EventManager;

use Zend\EventManager\EventManagerInterface;

class AbstractListenerAggregateTest extends ListenerAggregateTraitTest
{
    public $aggregateClass = TestAsset\MockAbstractListenerAggregate::class;
}
