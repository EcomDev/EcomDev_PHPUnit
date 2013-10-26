<?php

if (version_compare(PHP_VERSION, '5.3', '<')) {
    echo 'Magento Unit Tests can run only on PHP version higher then 5.3';
    exit(1);
}

$_baseDir = getcwd();

// Include Mage file by detecting app root
require_once $_baseDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';

if (!Mage::isInstalled()) {
    echo 'Magento Unit Tests can run only on installed version';
    exit(1);
}

/* Replace server variables for proper file naming */
$_SERVER['SCRIPT_NAME'] = $_baseDir . DS . 'index.php';
$_SERVER['SCRIPT_FILENAME'] = $_baseDir . DS . 'index.php';

// This fix allows running Magento Unit Tests
// from remote PHPUnit execution in vagrant box over PHPStorm
if (!empty($_GET)) {
    $_GET = array();
}

Mage::app('admin');
Mage::getConfig()->init();

// Removing Varien Autoload, to prevent errors with PHPUnit components
spl_autoload_unregister(array(\Varien_Autoload::instance(), 'autoload'));

// It is possible to include custom bootstrap file by specifying env variable shell
// or in server
if (isset($_SERVER['ECOMDEV_PHPUNIT_CUSTOM_BOOTSTRAP'])) {
    include $_SERVER['ECOMDEV_PHPUNIT_CUSTOM_BOOTSTRAP'];
}

if (!defined('ECOMDEV_PHPUNIT_NO_AUTOLOADER')) {
    spl_autoload_register(function ($className) {
        $filePath = strtr(
            ltrim($className, '\\'),
            array(
                '\\' => '/',
                '_'  => '/'
            )
        );

        @include $filePath . '.php';
    });
}
