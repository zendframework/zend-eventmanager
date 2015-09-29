<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

namespace ZendTest\EventManager\TestAsset;

use Interop\Container\ContainerInterface;

/**
 * Mimic the ServiceManager v3 ServiceLocatorInterface in order to test
 * lazy listener creation.
 */
interface BuilderInterface extends ContainerInterface
{
    public function build($service, array $opts = []);
}
