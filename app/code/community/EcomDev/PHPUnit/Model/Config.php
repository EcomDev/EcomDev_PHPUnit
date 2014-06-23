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
 * @copyright  Copyright (c) 2013 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Configuration model extended to make unit tests to be available
 * at separate configuration scope
 *
 */
class EcomDev_PHPUnit_Model_Config extends Mage_Core_Model_Config
{
    const XML_PATH_SECURE_BASE_URL = 'default/web/secure/base_url';
    const XML_PATH_UNSECURE_BASE_URL = 'default/web/unsecure/base_url';

    const CHANGE_ME = '[change me]';
    /**
     * Scope snapshot with different levels of saving configuration
     *
     * @var Mage_Core_Model_Config_Base
     */
    protected $_scopeSnapshot = array();

    /**
     * Scope snapshot for a particular test case
     *
     * @var Mage_Core_Model_Config_Base
     */
    protected $_localScopeSnapshot = null;

    /**
     * List of replaced instance creation
     *
     * @return array
     */
    protected $_replaceInstanceCreation = array();

    /**
     * No cache sections should be cached,
     * in favor to get rid of buggy config set options
     *
     * @var array
     */
    protected $_cacheSections = array();

    /**
     * Object containing parsed local.xml.phpunit
     *
     * @var null
     */
    protected $_localXmlForTest = null;

	/**
     * Load config data from DB
     *
     * @return Mage_Core_Model_Config
     */
    public function loadDb()
    {
        if ($this->_isLocalConfigLoaded
            && Mage::isInstalled()
            && empty($this->_scopeSnapshot)) {
            $this->saveScopeSnapshot();
        }
        parent::loadDb();
        return $this;
    }

    /**
     * Get events configuration
     *
     * @param   string $area event area
     * @param   string $eventName event name
     * @return  Mage_Core_Model_Config_Element
     */
    public function getEventConfig($area, $eventName)
    {
        return $this->getNode($area)->events->{$eventName};
    }

    /**
     * Replaces creation of some instance by mock object
     *
     *
     * @param string $type
     * @param string $classAlias
     * @param PHPUnit_Framework_MockObject_MockObject|PHPUnit_Framework_MockObject_MockBuilder $mock
     * @return EcomDev_PHPUnit_Model_Config
     */
    public function replaceInstanceCreation($type, $classAlias, $mock)
    {
        $this->_replaceInstanceCreation[$type][$classAlias] = $mock;
        return $this;
    }

    /**
     * Flushes instance creation instruction list
     *
     * @return EcomDev_PHPUnit_Model_Config
     */
    public function flushReplaceInstanceCreation()
    {
        $this->_replaceInstanceCreation = array();
        return $this;
    }

    /**
     * Overridden for test case model instance creation mocking
     *
     * @see Mage_Core_Model_Config::getModelInstance()
     */
    public function getModelInstance($modelClass='', $constructArguments=array())
    {
        if (!isset($this->_replaceInstanceCreation['model'][$modelClass])) {
            return parent::getModelInstance($modelClass, $constructArguments);
        }

        return $this->_replaceInstanceCreation['model'][$modelClass];
    }

    /**
     * Overridden for test case model instance creation mocking
     *
     * (non-PHPdoc)
     * @see Mage_Core_Model_Config::getModelInstance()
     */
    public function getResourceModelInstance($modelClass='', $constructArguments=array())
    {
        if (!isset($this->_replaceInstanceCreation['resource_model'][$modelClass])) {
            return parent::getResourceModelInstance($modelClass, $constructArguments);
        }

        return $this->_replaceInstanceCreation['resource_model'][$modelClass];
    }

    /**
     * Retrieves real resource model class alias
     *
     * @param string $classAlias
     * @return string
     */
    public function getRealResourceModelClassAlias($classAlias)
    {
        list($classAliasPrefix,) = explode('/', $classAlias, 2);

        if (isset($this->_xml->global->models->$classAliasPrefix->resourceModel)) {
            $realClassAliasPrefix = $this->_xml->global->models->$classAliasPrefix->resourceModel;
            $classAlias = $realClassAliasPrefix . substr(
                $classAlias, strlen($classAliasPrefix)
            );
        }

        return $classAlias;
    }

    /**
     * Loads scope snapshot
     *
     * @throws RuntimeException
     * @return EcomDev_PHPUnit_Model_Config
     */
    public function loadScopeSnapshot()
    {
        if (empty($this->_scopeSnapshot)) {
            throw new RuntimeException('Cannot load scope snapshot, because it was not saved before');
        }

        $scope = clone end($this->_scopeSnapshot);

        $this->_xml = $scope;
        return $this;
    }

    /**
     * Flushes current scope snapshot level if it is not the last one
     *
     * @return EcomDev_PHPUnit_Model_Config
     */
    public function flushScopeSnapshot()
    {
        if (count($this->_scopeSnapshot) > 1) {
            array_pop($this->_scopeSnapshot);
            memory_get_usage(); // Memory GC
        }
        return $this;
    }

    /**
     * Saves current configuration snapshot,
     * for pussible restoring in feature
     *
     * @return EcomDev_PHPUnit_Model_Config
     */
    public function saveScopeSnapshot()
    {
        $this->_scopeSnapshot[] = clone $this->_xml;
        return $this;
    }

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
     * Define if module is allowed
     *
     * Magento core allows use of a whitelist of modules supplied via the
     * addAllowedModules method.  EcomDev_PHPUnit extends this to allow a
     * blacklist of modules to be supplied via local.xml.phpunit.
     *
     * @see Mage_Core_Model_Config::_isAllowedModule()
     * @param  string $moduleName
     * @return bool
     */
    protected function _isAllowedModule($moduleName)
    {
        if (!parent::_isAllowedModule($moduleName)) {
            return false;
        }

        $localXml = $this->_loadLocalXmlForTest();
        if ($localXml) {
            $node = $localXml->getNode("phpunit/disable_modules/$moduleName");
            return $node === false;
        }

        return true;
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
        try {
            if ($merge = $this->_loadLocalXmlForTest()) {
                $this->_checkDbCredentialForDuplicate($this, $merge);
                $this->_checkBaseUrl($this, $merge);
                $this->extend($merge);
            } else {
                throw new RuntimeException('Unable to load local.xml.phpunit. Please run ecomdev-phpunit.php with install action.');
            }
        } catch (RuntimeException $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }

        return $this;
    }

    /**
     * Parse the phpunit specific local configuration.  This may be loaded by
     * and used by _isAllowedModule before it's merged into the merged config.
     *
     * @return Mage_Core_Model_Config_Base|null
     */
    protected function _loadLocalXmlForTest() {
        if ($this->_localXmlForTest === null) {
            $this->_localXmlForTest = clone $this->_prototype;
            $this->_localXmlForTest->loadFile($this->_getLocalXmlForTest());
        }
        return $this->_localXmlForTest;
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
        $this->setNode('global/cache/backend', '');
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

        if ($originalDbName == $testDbName && (string)$test->getNode('phpunit/allow_same_db') !== '1') {
            throw new RuntimeException('Test DB cannot be the same as the live one. '
                                       . 'You can change this option by running ecomdev-phpunit.php with'
                                       . ' magento-config action.');
        }
        return $this;
    }

    /**
     * Check base url settings, if not set it rises an exception
     *
     * @param Mage_Core_Model_Config_Base $original
     * @param Mage_Core_Model_Config_Base $test
     * @return EcomDev_PHPUnit_Model_Config
     * @throws RuntimeException
     */
    protected function _checkBaseUrl($original, $test)
    {
        $baseUrlSecure = (string)$test->getNode(self::XML_PATH_SECURE_BASE_URL);
        $baseUrlUnsecure = (string)$test->getNode(self::XML_PATH_UNSECURE_BASE_URL);

        if (empty($baseUrlSecure) || empty($baseUrlUnsecure)
            || $baseUrlSecure == self::CHANGE_ME || $baseUrlUnsecure == self::CHANGE_ME) {
            throw new RuntimeException('The base url is not set for proper controller tests. '
                                        . 'Please run ecomdev-phpunit.php with magento-config action.');
        }
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
            throw new RuntimeException('There is no local.xml.phpunit file. '
                                       . 'Try running ecomdev-phpunit.php with install action.');
        }

        return $filePath;
    }
}
