<?php

namespace ZendBench\EventManager;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Zend\EventManager\EventManager;

/**
 * @BeforeMethods({"setUp"})
 */
class SingleEventSingleListenerBench
{
    use BenchTrait;

    private $events;

    public function setUp()
    {
        $this->events = new EventManager();
        $this->events->attach('dispatch', $this->generateCallback());
    }

    /**
     * Trigger the dispatch event
     *
     * @Revs(1000)
     * @Iterations(20)
     */
    public function benchTrigger()
    {
        $this->events->trigger('dispatch');
    }
}
