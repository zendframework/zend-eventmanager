<?php
/**
 * @see       https://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\EventManager\ListenerProvider\TestAsset;

class MultipleListener
{
    public function __invoke($e)
    {
        $e->value = __FUNCTION__;
    }

    public function run($e)
    {
        $e->value = __FUNCTION__;
    }

    public function onEvent($e)
    {
        $e->value = __FUNCTION__;
    }
}
