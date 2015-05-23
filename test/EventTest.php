<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\EventManager;

use Zend\EventManager\Event;

class EventTest extends \PHPUnit_Framework_TestCase
{
    public function testParams()
    {
        $event = new Event();

        $this->assertSame([], $event->getParams());

        $event->setParam('foo', 'bar');
        $this->assertSame('bar', $event->getParam('foo'));

        $params = ['bat' => 'baz'];
        $event->setParams($params);
        $this->assertSame($params, $event->getParams());
        
        $this->assertSame('defaultValue', $event->getParam('nonExistentParam', 'defaultValue'));
    }

    public function testStopPropogation()
    {
        $event = new Event();

        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }
}
