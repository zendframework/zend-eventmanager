<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace Zend\EventManager;

/**
 * Provides logic to easily create aggregate listeners, without worrying about
 * manually detaching events
 *
 * @deprecated since 3.3.0. This trait will be removed in version 4.0.0, in
 *     favor of the ListenerProvider\ListenerSubscriberTrait. In most cases,
 *     subscribers should fully implement ListenerSubscriberInterface on their
 *     own, however.
 */
trait ListenerAggregateTrait
{
    /**
     * @var callable[]
     */
    protected $listeners = [];

    /**
     * {@inheritDoc}
     */
    public function detach(EventManagerInterface $events)
    {
        foreach ($this->listeners as $index => $callback) {
            $events->detach($callback);
            unset($this->listeners[$index]);
        }
    }
}
