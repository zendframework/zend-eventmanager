<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace ZendTest\EventManager\TestAsset;

use Zend\EventManager\EventManagerAwareTrait;

/**
 * @group      Zend_EventManager
 */
class MockEventManagerAwareTrait
{
    use EventManagerAwareTrait;

    protected $eventIdentifier = 'foo.bar';
    protected $defaultEventListenersCalled = false;

    public function getEventIdentifier()
    {
        return $this->eventIdentifier;
    }

    public function setEventIdentifier($eventIdentifier)
    {
        $this->eventIdentifier = $eventIdentifier;
        return $this;
    }

    public function attachDefaultListeners()
    {
        $this->defaultEventListenersCalled = true;
    }

    public function defaultEventListenersCalled()
    {
        return $this->defaultEventListenersCalled;
    }
}
