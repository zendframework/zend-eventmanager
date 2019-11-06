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
 * Interface indicating that an object composes an EventManagerInterface instance.
 *
 * @deprecated since 3.3.0. This interface will be removed in version 4.0, in
 *     favor of the EventDispatcherInterface introduced in version 3.3.0.
 */
interface EventsCapableInterface
{
    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager();
}
