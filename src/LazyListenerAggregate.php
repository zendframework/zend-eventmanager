<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\EventManager;

use Interop\Container\ContainerInterface;

/**
 * Aggregate listener for attaching lazy listeners.
 *
 * Lazy listeners are listeners where creation is deferred until they are
 * triggered; this removes the most costly mechanism of pulling a listener
 * from a container unless the listener is actually invoked.
 *
 * Usage is:
 *
 * <code>
 * $events->attachAggregate(new LazyListenerAggregate(
 *     $lazyListenersOrLazyListenerDefinitions,
 *     $container
 * ));
 * </code>
 */
class LazyListenerAggregate implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /**
     * @var ContainerInterface Container from which to pull lazy listeners.
     */
    private $container;

    /**
     * @var array Additional environment/option variables to use when creating listener.
     */
    private $env;

    /**
     * Generated LazyListener instances.
     *
     * @var LazyListener[]
     */
    private $lazyListeners = [];

    /**
     * Constructor
     *
     * Accepts the composed $listeners, as well as the $container and $env in
     * order to create a listener aggregate that defers listener creation until
     * the listener is triggered.
     *
     * Listeners may be either LazyListener instances, or lazy listener
     * definitions that can be provided to a LazyListener constructor in order
     * to create a new instance; in the latter case, the $container and $env
     * will be passed at instantiation as well.
     *
     * @var array $listeners LazyListener instances or array structs to pass to
     *     the LazyListener constructor.
     * @var ContainerInterface $container
     * @var array $env
     * @throws Exception\InvalidArgumentException for invalid listener items.
     */
    public function __construct(array $listeners, ContainerInterface $container, array $env = [])
    {
        $this->container = $container;
        $this->env       = $env;

        // This would raise an exception for invalid structs
        foreach ($listeners as $listener) {
            if (is_array($listener)) {
                $listener = new LazyListener($listener, $container, $env);
            }

            if (! $listener instanceof LazyListener) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'All listeners must be LazyListener instances or structs used to instantiate one; received %s',
                    (is_object($listener) ? get_class($listener) : gettype($listener))
                ));
            }

            $this->lazyListeners[] = $listener;
        }
    }

    /**
     * Attach the aggregate to the event manager.
     *
     * Loops through all composed lazy listeners, and attaches them to the
     * event manager.
     *
     * @var EventManagerInterface $events
     * @var int $priority
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        foreach ($this->lazyListeners as $lazyListener) {
            $this->listeners[] = $events->attach(
                $lazyListener->getEvent(),
                $lazyListener,
                $lazyListener->getPriority($priority)
            );
        }
    }
}
