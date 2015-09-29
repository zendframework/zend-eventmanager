<?php
namespace ZendBench\EventManager;

trait TraitEventBench
{
    private $numListeners = 50;

    private function generateCallback()
    {
        return function ($e) {
        };
    }

    private function getEventList()
    {
        return [
            'dispatch',
            'dispatch.post',
            '*',
        ];
    }

    private function getIdentifierList()
    {
        return [
            'Zend\Stdlib\DispatchableInterface',
            'Zend\Mvc\Controller\AbstractController',
            'Zend\Mvc\Controller\AbstractActionController',
            'Zend\Mvc\Controller\AbstractRestfulController',
            'ZF\Rest\RestController',
            'CustomRestController',
        ];
    }
}
