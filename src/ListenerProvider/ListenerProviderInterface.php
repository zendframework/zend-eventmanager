<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace Zend\EventManager\ListenerProvider;

/**
 * @deprecated This interface is a forwards-compatibility shim for use until we can
 *     provide full PSR-14 compatibility, and will be removed in version 4.0.
 */
interface ListenerProviderInterface
{
    /**
     * @param object $event The event for which to retrieve listeners.
     * @return callable[] Iterable list of listeners to which to pass the event.
     */
    public function getListenersForEvent($event);
}
