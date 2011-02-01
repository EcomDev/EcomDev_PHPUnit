<?php
/**
 * PHP Unit test suite for Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_PHPUnit
 * @copyright  Copyright (c) 2011 Ecommerce Developers (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Configution model extended to make unit tests to be available
 * at separate configuration scope
 *
 */
class EcomDev_PHPUnit_Model_Config extends Mage_Core_Model_Config
{
    /**
     * Loads additional configuration for unit tests
     * (non-PHPdoc)
     * @see Mage_Core_Model_Config::loadBase()
     */
    public function loadBase()
    {
        parent::loadBase();
        $this->_loadTestCacheConfig();
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Mage_Core_Model_Config::loadModules()
     */
    public function loadModules()
    {
        parent::loadModules();
        $this->_loadTestConfig();
        $this->_loadTestCacheConfig();
        return $this;
    }

    /**
     * Loads local.xml.phpunit file
     * for overriding DB credentials
     *
     * @return EcomDev_PHPUnit_Model_Config
     */
    protected function _loadTestConfig()
    {
        $merge = clone $this->_prototype;
        if ($merge->loadFile($this->_getLocalXmlForTest())) {
            $this->_checkDbCredentialForDuplicate($this, $merge);
            $this->extend($merge);
        } else {
            throw new Exception('Unable to load local.xml.phpunit');
        }
        return $this;
    }

    /**
     * Loads cache configuration for PHPUnit tests scope
     *
     * @return EcomDev_PHPUnit_Model_Config
     */
    protected function _loadTestCacheConfig()
    {
        // Cache beckend initialization for unit tests,
        // because it should be separate from live one
        $this->setNode('global/cache/backend', 'file');
        $this->getOptions()->setData('cache_dir', $this->getVarDir() . DS . 'phpunit.cache');
        $this->getOptions()->setData('session_dir', $this->getVarDir() . DS . 'phpunit.session');
        return $this;
    }

    /**
     * Checks DB credentials for phpunit test case.
     * They shouldn't be the same as live ones.
     *
     * @param Mage_Core_Model_Config_Base $original
     * @param Mage_Core_Model_Config_Base $test
     * @return EcomDev_PHPUnit_Model_Config
     * @throws RuntimeException
     */
    protected function _checkDbCredentialForDuplicate($original, $test)
    {
        $originalDbName = (string) $original->getNode('global/resources/default_setup/connection/dbname');
        $testDbName = (string) $test->getNode('global/resources/default_setup/connection/dbname');

        if ($originalDbName == $testDbName) {
            throw new RuntimeException('Test DB cannot be the same as the live one');
        }
        return $this;
    }

    /**
     * Retrieves local.xml file path for tests,
     * If it is not found, method will rise an exception
     *
     * @return string
     * @throws RuntimeException
     */
    protected function _getLocalXmlForTest()
    {
        $filePath = $this->getOptions()->getEtcDir() . DS . 'local.xml.phpunit';
        if (!file_exists($filePath)) {
            throw new RuntimeException('There is no local.xml.phpunit file');
        }

        return $filePath;
    }
}
