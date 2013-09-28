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

spl_autoload_unregister(array(\Varien_Autoload::instance(), 'autoload'));

spl_autoload_register(function ($classname) {
    $classname = ltrim($classname, "\\");
    preg_match('/^(.+)?([^\\\\]+)$/U', $classname, $match);
    $classname = str_replace("\\", "/", $match[1])
        . str_replace(array("\\", "_"), "/", $match[2])
        . ".php";
    @include_once $classname;
});
