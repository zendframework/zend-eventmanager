<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace ZendTest\EventManager\TestAsset;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;

/**
 * @group      Zend_EventManager
 */
class ClassWithEvents
{
    protected $events;

    public function getEventManager(EventManagerInterface $events = null)
    {
        if (null !== $events) {
            $this->events = $events;
        }
        if (null === $this->events) {
            $this->events = new EventManager(__CLASS__);
        }
        return $this->events;
    }

    public function foo()
    {
        $this->getEventManager()->trigger(__FUNCTION__, $this, []);
    }
}
