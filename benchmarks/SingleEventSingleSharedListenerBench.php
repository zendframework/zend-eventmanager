<?php

namespace ZendBench\EventManager;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use Zend\EventManager\SharedEventManager;
use Zend\EventManager\EventManager;

/**
 * @BeforeMethods({"setUp"})
 */
class SingleEventSingleSharedListenerBench
{
    use BenchTrait;

    private $sharedEvents;

    private $events;

    public function setUp()
    {
        $identifiers = $this->getIdentifierList();
        $this->sharedEvents = new SharedEventManager();
        $this->sharedEvents->attach($identifiers[0], 'dispatch', $this->generateCallback());
        $this->events = new EventManager($this->sharedEvents, [$identifiers[0]]);
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
