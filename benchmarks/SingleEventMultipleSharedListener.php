<?php

namespace ZendBench\EventManager;

use Zend\EventManager\SharedEventManager;
use Zend\EventManager\EventManager;
use Athletic\AthleticEvent;

class SingleEventMultipleSharedListener extends AthleticEvent
{
    use TraitEventBench;

    private $sharedEvents;

    private $events;

    public function setUp()
    {
        $identifiers = $this->getIdentifierList();
        $this->sharedEvents = new SharedEventManager();
        for ($i = 0; $i < $this->numListeners; $i += 1) {
            $this->sharedEvents->attach($identifiers[0], 'dispatch', $this->generateCallback());
        }
        $this->events = new EventManager($this->sharedEvents, [$identifiers[0]]);
    }

    /**
     * Trigger the dispatch event
     *
     * @iterations 5000
     */
    public function trigger()
    {
        $this->events->trigger('dispatch');
    }
}
