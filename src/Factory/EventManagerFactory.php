<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Zend\EventManager\Factory;

use Zend\EventManager\EventManager;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerPluginManager;
use Zend\EventManager\RuntimeException;
use Zend\ServiceManager\FactoryInterface;

class EventManagerFactory implements FactoryInterface
{
    /**
     * Override config key in an extending class to point to more specific key if needed
     *
     * @var string
     */
    protected $configKey = 'event_manager';

    /**
     * {@inheritDoc}
     */
    public function __invoke(ServiceLocatorInterface $serviceLocator, $requestedName, array $options = [])
    {
        // @TODO: ZF3 should have a Config accessible through FQCN
        $config = $serviceLocator->get('Config');
        $config = isset($config[$this->configKey]) ? $config[$this->configKey] : [];

        /** @var ListenerPluginManager $listenerPluginManager */
        $listenerPluginManager = $serviceLocator->get(ListenerPluginManager::class);
        $eventManager          = new EventManager(function($requestedName) use ($listenerPluginManager) {
            return $listenerPluginManager->get($requestedName);
        });

        /*
         * @example
         *
         * [
         *   'listener_aggregates' => [
         *     My\EventListenerAggregate1::class,
         *     My\EventListenerAggregate2::class,
         *   ]
         * ]
         *
         */
        $listenerAggregates = isset($config['listener_aggregates']) ? $config['listener_aggregates'] : [];

        foreach ($listenerAggregates as $aggregateClass) {
            if (!$aggregateClass instanceof ListenerAggregateInterface) {
                throw new RuntimeException(sprintf(
                    'Listener aggregates must implement "Zend\EventManager\ListenerAggregateInterface", "%s" given',
                    is_object($aggregateClass) ? get_class($aggregateClass) : gettype($aggregateClass)
                ));
            }

            $aggregateClass::attachAggregate($eventManager);
        }

        /*
         * @example
         *
         * [
         *   'listeners' => [
         *     'my.event.name' => [
         *        [[My\Listener1::class, 'onMyEventName'], 100],
         *        [[My\Listener2::class, 'onMyEventName'], 100],
         *     ]
         *   ]
         * ]
         *
         */
        $listeners = isset($config['listeners']) ? $config['listeners'] : [];

        foreach ($listeners as $eventName => $listenerSpecs) {
            foreach ($listenerSpecs as list($listenerSpec, $priority)) {
                $eventManager->attach($eventName, $listenerSpec, $priority);
            }
        }

        return $eventManager;
    }
}
