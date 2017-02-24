<?php
/**
 * @link      http://github.com/zendframework/zend-eventmanager for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

use PHPUnit\Framework\ExpectationFailedException;

if (class_exists('PHPUnit_Framework_ExpectationFailedException')) {
    class_alias('PHPUnit_Framework_ExpectationFailedException', ExpectationFailedException::class);
}
