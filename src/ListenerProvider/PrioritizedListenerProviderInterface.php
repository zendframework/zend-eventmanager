<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace Zend\EventManager\ListenerProvider;

interface PrioritizedListenerProviderInterface extends ListenerProviderInterface
{
    /**
     * @param object $event The event for which to retrieve listeners.
     * @param string[] $identifiers For use with shared listener providers.
     *     This argument is deprecated, and will be removed in version 4.0.
     * @return array<int, callable[]> Returns a hash table of priorities with
     *     the associated listeners for that priority.
     */
    public function getListenersForEventByPriority($event, array $identifiers = []);
}
