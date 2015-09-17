<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\EventManager;

use Zend\EventManager\Filter\FilterIterator;

/**
 * @group      Zend_Stdlib
 */
class FilterIteratorTest extends \PHPUnit_Framework_TestCase
{

    public function testNextReturnsNullOnEmptyChain()
    {
        $filterIterator = new FilterIterator();
        $this->assertNull($filterIterator->next([]));
    }

    public function testNextReturnsNullWithEmptyHeap()
    {
        $filterIterator = new FilterIterator();
        $this->assertNull($filterIterator->next([0, 1, 2]));
    }

    public function testContainsReturnsFalseForInvalidElement()
    {
        $filterIterator = new FilterIterator();
        $this->assertFalse($filterIterator->contains('foo'));
    }

    public function testContainsReturnsTrueForValidElement()
    {
        $callback = function () {
        };
        $filterIterator = new FilterIterator();
        $filterIterator->insert($callback, 1);
        $this->assertTrue($filterIterator->contains($callback));
    }

    public function testRemoveFromEmptyQueueReturnsFalse()
    {
        $filterIterator = new FilterIterator();

        $this->assertFalse($filterIterator->remove('foo'));
    }

    public function testRemoveUnrecognizedItemFromQueueReturnsFalse()
    {
        $callback = function () {
        };
        $filterIterator = new FilterIterator();
        $filterIterator->insert($callback, 1);

        $this->assertFalse($filterIterator->remove(clone $callback));
    }

    public function testRemoveValidItemFromQueueReturnsTrue()
    {
        $callback = function () {
        };
        $filterIterator = new FilterIterator();
        $filterIterator->insert($callback, 1);

        $this->assertTrue($filterIterator->remove($callback));
    }

    public function testNextReturnsNullWhenFilterChainIsEmpty()
    {
        $filterIterator = new FilterIterator();

        $chain = new FilterIterator();

        $this->assertNull($filterIterator->next([0, 1, 2], ['foo', 'bar'], $chain));
    }
}
