<?php

namespace ZendBench\EventManager;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use Zend\EventManager\EventManager;

/**
 * @Revs(1000)
 * @Iterations(10)
 * @Warmup(2)
 */
class MultipleEventLocalListenerBench
{
    use BenchTrait;

    /** @var EventManager */
    private $events;

    /** @var array */
    private $eventsToTrigger;

    public function __construct()
    {
        $this->events = new EventManager();

        $this->eventsToTrigger = array_filter($this->getEventList(), function ($value) {
            return ($value !== '*');
        });
    }

    public function benchTrigger()
    {
        foreach ($this->eventsToTrigger as $event) {
            $this->events->attach($event, $this->generateCallback());
            $this->events->trigger($event);
        }
    }
}
