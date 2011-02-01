<?php

require 'app/Mage.php';

if (version_compare(PHP_VERSION, '5.3', '<')) {
    exit('Magento Unit Tests can be runned only on PHP version over 5.3');
}

if (!Mage::isInstalled()) {
    exit('Magento Unit Tests can be runned only on installed version');
}

Mage::app('admin');
Mage::getConfig()->init();

class UnitTests extends EcomDev_PHPUnit_Test_Suite
{

}
