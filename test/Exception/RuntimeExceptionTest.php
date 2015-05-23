<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\EventManager\Exception;

use Zend\EventManager\Exception\RuntimeException;

class RuntimeExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testMissingInstantiatorExceptionWithString()
    {
        $this->setExpectedException(
            RuntimeException::class,
            'Trying to create a lazy listener for "foo", but no instantiator was specified in the event manager'
        );

        RuntimeException::missingInstantiatorException('foo');
    }

    public function testMissingInstantiatorExceptionWithArray()
    {
        $this->setExpectedException(
            RuntimeException::class,
            'Trying to create a lazy listener for "foo, bar", but no instantiator was specified in the event manager'
        );

        RuntimeException::missingInstantiatorException(['foo', 'bar']);
    }
}
