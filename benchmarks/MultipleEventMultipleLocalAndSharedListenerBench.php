<?php

namespace ZendBench\EventManager;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\EventManager;

/**
 * @Revs(1000)
 * @Iterations(10)
 * @Warmup(2)
 */
class MultipleEventMultipleLocalAndSharedListenerBench
{
    use BenchTrait;

    /** @var EventManager */
    private $events;

    /** @var array */
    private $eventsToTrigger;

    public function __construct()
    {
        $identifiers = $this->getIdentifierList();
        $sharedEvents = new SharedEventManager();
        foreach ($this->getIdentifierList() as $identifier) {
            foreach ($this->getEventList() as $event) {
                $sharedEvents->attach($identifier, $event, $this->generateCallback());
            }
        }
        $this->events = new EventManager($sharedEvents, $identifiers);

        $this->eventsToTrigger = array_filter($this->getEventList(), function ($value) {
            return ($value !== '*');
        });
    }

    public function benchTrigger()
    {
        foreach ($this->eventsToTrigger as $event) {
            for ($i = 0; $i < $this->numListeners; $i += 1) {
                $this->events->attach($event, $this->generateCallback());
            }
            $this->events->trigger($event);
        }
    }
}
