<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\EventManager;

class LazilyInstantiatedListener
{
    private $hasBeenTriggered;

    public function __construct()
    {
        $this->hasBeenTriggered = false;
    }

    public function listenerMethod()
    {
        $this->hasBeenTriggered = true;
    }

    public function hasBeenTriggered()
    {
        return $this->hasBeenTriggered;
    }
}