<?php
/**
 * @link      http://github.com/zendframework/zend-event-manager for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-eventmanager/blob/master/LICENSE.md
 */

/*
 * Set error reporting to the level to which Zend Framework code must comply.
 */
error_reporting(E_ALL | E_STRICT);

if (class_exists('PHPUnit_Runner_Version', true)) {
    $phpUnitVersion = PHPUnit_Runner_Version::id();
    if ('@package_version@' !== $phpUnitVersion && version_compare($phpUnitVersion, '4.0.0', '<')) {
        echo 'This version of PHPUnit (' . PHPUnit_Runner_Version::id() . ') is not supported'
           . ' in the zend-event-manager unit tests. Supported is version 4.0.0 or higher.'
           . ' See also the CONTRIBUTING.md file in the component root.' . PHP_EOL;
        exit(1);
    }
    unset($phpUnitVersion);
}

/**
 * Setup autoloading
 */
require __DIR__ . '/../vendor/autoload.php';

/**
 * Start output buffering, if enabled
 */
if (defined('TESTS_ZEND_OB_ENABLED') && constant('TESTS_ZEND_OB_ENABLED')) {
    ob_start();
}
