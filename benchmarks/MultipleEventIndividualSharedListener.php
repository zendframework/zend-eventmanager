<?php

namespace ZendBench\EventManager;

use Zend\EventManager\SharedEventManager;
use Zend\EventManager\EventManager;
use Athletic\AthleticEvent;

class MultipleEventIndividualSharedListener extends AthleticEvent
{
    use TraitEventBench;

    private $sharedEvents;

    private $events;

    private $eventsToTrigger;

    public function setUp()
    {
        $identifiers = $this->getIdentifierList();
        $this->sharedEvents = new SharedEventManager();
        foreach ($this->getEventList() as $event) {
            $this->sharedEvents->attach($identifiers[0], $event, $this->generateCallback());
        }
        $this->events = new EventManager($this->sharedEvents, [$identifiers[0]]);

        $this->eventsToTrigger = array_filter($this->getEventList(), function ($value) {
            return ($value !== '*');
        });
    }

    /**
     * Trigger the event list
     *
     * @iterations 5000
     */
    public function trigger()
    {
        foreach ($this->eventsToTrigger as $event) {
            $this->events->trigger($event);
        }
    }
}
