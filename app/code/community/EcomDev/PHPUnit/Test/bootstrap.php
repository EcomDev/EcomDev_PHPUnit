<?php

if (version_compare(PHP_VERSION, '5.3', '<')) {
    exit('Magento Unit Tests can run only on PHP version higher then 5.3');
}


$fileDir = dirname(__FILE__);
// Include Mage file by detecting app root
require_once substr($fileDir, 0, strpos($fileDir, 'app' . DIRECTORY_SEPARATOR)) . 'app/Mage.php';

if (!Mage::isInstalled()) {
    exit('Magento Unit Tests can run only on installed version');
}

/* Replace server variables for proper file naming */
$_SERVER['SCRIPT_NAME'] = dirname(__FILE__) . DS . 'index.php';
$_SERVER['SCRIPT_FILENAME'] = dirname(__FILE__) . DS . 'index.php';

Mage::app('admin');
Mage::getConfig()->init();
