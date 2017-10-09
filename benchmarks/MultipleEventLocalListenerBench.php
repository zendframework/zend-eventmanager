<?php

namespace ZendBench\EventManager;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Zend\EventManager\EventManager;

/**
 * @BeforeMethods({"setUp"})
 */
class MultipleEventLocalListenerBench
{
    use BenchTrait;

    private $eventsToTrigger;

    public function setUp()
    {
        $this->events = new EventManager();

        $this->eventsToTrigger = array_filter($this->getEventList(), function ($value) {
            return ($value !== '*');
        });
    }

    /**
     * Attach and trigger the event list
     *
     * @Revs(1000)
     * @Iterations(20)
     */
    public function benchTrigger()
    {
        foreach ($this->eventsToTrigger as $event) {
            $this->events->attach($event, $this->generateCallback());
            $this->events->trigger($event);
        }
    }
}
