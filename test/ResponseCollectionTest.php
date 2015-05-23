<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\EventManager;

use Zend\EventManager\ResponseCollection;
use Traversable;

class ResponseCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testResponseCollection()
    {
        $arrayCollection = [
            'arbitraryResponseOne',
            'arbitraryResponseTwo',
            'arbitraryResponseThree',
        ];

        $emptyResponseCollection = new ResponseCollection();
        $this->assertNull($emptyResponseCollection->first());
        $this->assertNull($emptyResponseCollection->last());
        $this->assertFalse($emptyResponseCollection->contains('anything'));
        $this->assertSame(0, $emptyResponseCollection->count());

        $responseCollection = new ResponseCollection($arrayCollection);
        $this->assertSame($arrayCollection[0], $responseCollection->first());
        $this->assertSame($arrayCollection[2], $responseCollection->last());
        $this->assertTrue($responseCollection->contains('arbitraryResponseOne'));
        $this->assertFalse($responseCollection->contains('foobar'));
        $this->assertSame(3, $responseCollection->count());

        $iterator = $responseCollection->getIterator();
        $this->assertInstanceOf(Traversable::class, $iterator);
        $this->assertContains('arbitraryResponseThree', $iterator);
    }
}
