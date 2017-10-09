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
class SingleEventMultipleListenerBench
{
    use BenchTrait;

    /** @var EventManager */
    private $events;

    public function __construct()
    {
        $this->events = new EventManager();
        for ($i = 0; $i < $this->numListeners; $i++) {
            $this->events->attach('dispatch', $this->generateCallback());
        }
    }

    public function benchTrigger()
    {
        $this->events->trigger('dispatch');
    }
}
