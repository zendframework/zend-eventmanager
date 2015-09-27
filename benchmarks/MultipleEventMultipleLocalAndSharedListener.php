<?php

namespace ZendBench\EventManager;

use Zend\EventManager\SharedEventManager;
use Zend\EventManager\EventManager;
use Athletic\AthleticEvent;

class MultipleEventMultipleLocalAndSharedListener extends AthleticEvent
{
    use TraitEventBench;

    private $sharedEvents;

    private $events;

    private $eventsToTrigger;

    public function setUp()
    {
        $identifiers = $this->getIdentifierList();
        $this->sharedEvents = new SharedEventManager();
        foreach ($this->getIdentifierList() as $identifier) {
            foreach ($this->getEventList() as $event) {
                $this->sharedEvents->attach($identifier, $event, $this->generateCallback());
            }
        }
        $this->events = new EventManager($this->sharedEvents, $identifiers);

        $this->eventsToTrigger = array_filter($this->getEventList(), function ($value) {
            return ($value !== '*');
        });
    }

    /**
     * Attach and trigger the event list
     *
     * @iterations 5000
     */
    public function trigger()
    {
        foreach ($this->eventsToTrigger as $event) {
            for ($i = 0; $i < $this->numListeners; $i += 1) {
                $this->events->attach($event, $this->generateCallback());
            }
            $this->events->trigger($event);
        }
    }
}
