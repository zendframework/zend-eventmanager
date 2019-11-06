<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace Zend\EventManager\ListenerProvider;

use Zend\EventManager\Exception;

class PrioritizedAggregateListenerProvider implements PrioritizedListenerProviderInterface
{
    /**
     * @var ListenerProviderInterface
     */
    private $default;

    /**
     * @var PrioritizedListenerProviderInterface[]
     */
    private $providers;

    public function __construct(array $providers, ListenerProviderInterface $default = null)
    {
        $this->validateProviders($providers);
        $this->providers = $providers;
        $this->default   = $default;
    }

    /**
     * {@inheritDoc}
     * @todo  Use `yield from` once we bump the minimum supported PHP version to 7+.
     * @param string[] $identifiers Any identifiers to use when retrieving
     *     listeners from child providers.
     */
    public function getListenersForEvent($event, array $identifiers = [])
    {
        // @todo `yield from $this->iterateByPriority(...)`
        foreach ($this->iterateByPriority($this->getListenersForEventByPriority($event, $identifiers)) as $listener) {
            yield $listener;
        }

        if (! $this->default) {
            return;
        }

        // @todo `yield from $this->default->getListenersForEvent(...)`
        foreach ($this->default->getListenersForEvent($event, $identifiers) as $listener) {
            yield $listener;
        }
    }

    public function getListenersForEventByPriority($event, array $identifiers = [])
    {
        $prioritizedListeners = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->getListenersForEventByPriority($event, $identifiers) as $priority => $listeners) {
                $prioritizedListeners[$priority] = isset($prioritizedListeners[$priority])
                    ? array_merge($prioritizedListeners[$priority], $listeners)
                    : $listeners;
            }
        }

        return $prioritizedListeners;
    }

    /**
     * @throws Exception\InvalidArgumentException if any provider is not a
     *     PrioritizedListenerProviderInterface instance
     */
    private function validateProviders(array $providers)
    {
        foreach ($providers as $index => $provider) {
            if (! $provider instanceof PrioritizedListenerProviderInterface) {
                throw new Exception\InvalidArgumentException(sprintf(
                    '%s requires all providers be instances of %s; received provider of type "%s" at index %d',
                    __CLASS__,
                    PrioritizedListenerProviderInterface::class,
                    gettype($provider),
                    $index
                ));
            }
        }
    }

    /**
     * @todo   Use `yield from` once we bump the minimum supported PHP version to 7+.
     * @param  array $prioritizedListeners
     * @return iterable
     */
    private function iterateByPriority($prioritizedListeners)
    {
        krsort($prioritizedListeners);
        foreach ($prioritizedListeners as $listeners) {
            // @todo `yield from $listeners`
            foreach ($listeners as $listener) {
                yield $listener;
            }
        }
    }
}
