<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace ZendTest\EventManager;

use Zend\EventManager\Event;

/**
 * @group      Zend_Stdlib
 */
class EventTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructorWithArguments()
    {
        $name = 'foo';
        $target = 'bar';
        $params = ['test','param'];

        $event = new Event($name, $target, $params);

        $this->assertEquals($name, $event->getName());
        $this->assertEquals($target, $event->getTarget());
        $this->assertEquals($params, $event->getParams());
    }

    public function testSetParamsWithInvalidParameter()
    {
        $event = new Event('foo');
        $this->setExpectedException('Zend\EventManager\Exception\InvalidArgumentException');
        $event->setParams('test');
    }

    public function testGetParamReturnsDefault()
    {
        $event = new Event('foo', 'bar', []);
        $default = 1;

        $this->assertEquals($default, $event->getParam('foo', $default));
    }

    public function testGetParamReturnsDefaultForObject()
    {
        $params = new \stdClass();
        $event = new Event('foo', 'bar', $params);
        $default = 1;

        $this->assertEquals($default, $event->getParam('foo', $default));
    }

    public function testGetParamReturnsForObject()
    {
        $key = 'test';
        $value = 'value';
        $params = new \stdClass();
        $params->$key = $value;

        $event = new Event('foo', 'bar', $params);
        $default = 1;

        $this->assertEquals($value, $event->getParam($key));
    }
}
