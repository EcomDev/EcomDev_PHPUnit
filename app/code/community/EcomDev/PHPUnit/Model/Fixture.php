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
 * Fixture model for Magento unit tests
 *
 * Created for operations with different fixture types
 *
 */
class EcomDev_PHPUnit_Model_Fixture
    extends Varien_Object
    implements EcomDev_PHPUnit_Model_FixtureInterface
{
    // Configuration path for eav loaders
    /* @deprecated since 0.3.0 */
    const XML_PATH_FIXTURE_EAV_LOADERS = EcomDev_PHPUnit_Model_Fixture_Processor_Eav::XML_PATH_FIXTURE_EAV_LOADERS;

    // Processors configuration path
    const XML_PATH_FIXTURE_PROCESSORS = 'phpunit/suite/fixture/processors';

	// Configuration path for attribute loaders
    const XML_PATH_FIXTURE_ATTRIBUTE_LOADERS = 'phpunit/suite/fixture/attribute';
    // Default attribute loader class alias
    const DEFAULT_ATTRIBUTE_LOADER_CLASS = 'ecomdev_phpunit/fixture_attribute_default';

    // Default eav loader class node in loaders configuration
    /* @deprecated since 0.3.0 */
    const DEFAULT_EAV_LOADER_NODE = EcomDev_PHPUnit_Model_Fixture_Processor_Eav::DEFAULT_EAV_LOADER_NODE;

    // Default shared fixture name
    const DEFAULT_SHARED_FIXTURE_NAME = 'default';

    // Default eav loader class alias
    /* @deprecated since 0.3.0 */
    const DEFAULT_EAV_LOADER_CLASS = EcomDev_PHPUnit_Model_Fixture_Processor_Eav::DEFAULT_EAV_LOADER_CLASS;

    // Key for storing fixture data into storage
    const STORAGE_KEY_FIXTURE = 'fixture';

    // Key for loaded tables into database
    /* @deprecated since 0.3.0 */
    const STORAGE_KEY_TABLES = EcomDev_PHPUnit_Model_Fixture_Processor_Tables::STORAGE_KEY;

    // Key for loaded entities by EAV loaders
    /* @deprecated since 0.3.0 */
    const STORAGE_KEY_ENTITIES = EcomDev_PHPUnit_Model_Fixture_Processor_Eav::STORAGE_KEY;

    // Key for loaded cache options
    /* @deprecated since 0.3.0 */
    const STORAGE_KEY_CACHE_OPTIONS = EcomDev_PHPUnit_Model_Fixture_Processor_Cache::STORAGE_KEY;

    // Key for created scope models
    /* @deprecated since 0.3.0 */
    const STORAGE_KEY_SCOPE = EcomDev_PHPUnit_Model_Fixture_Processor_Scope::STORAGE_KEY;

    /**
     * Fixtures array, contains config,
     * table and eav keys.
     * Each of them loads data into its area.
     *
     * @example
     * array(
     *    'config' => array(
     *        'node/path' => 'value'
     *    ),
     *    'table' => array(
     *        'table/name' => array(
     *            array(
     *                'column1' => 'value'
     *                'column2' => 'value'
     *                'column3' => 'value'
     *            ), // row 1
     *           array(
     *                'column1' => 'value'
     *                'column2' => 'value'
     *                'column3' => 'value'
     *            ) // row 2
     *        )
     *    )
     *
     * )
     *
     * @var array
     */
    protected $_fixture = array();

    /**
     * Storage object, for storing data between tests
     *
     * @var Varien_Object
     */
    protected $_storage = null;

    /**
     * Scope of the fixture,
     * used for different logic depending on
     *
     * @var string
     */
    protected $_scope = self::SCOPE_LOCAL;

    /**
     * Fixture options
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Processors list
     *
     * @var EcomDev_PHPUnit_Model_Fixture_ProcessorInterface[]
     */
    protected $_processors = array();

    /**
     * List of scope model aliases by scope type
     *
     * @var array
     * @deprecated since 0.3.0
     */
    protected static $_scopeModelByType = array();

    /**
     * Associative array of configuration nodes xml that was changed by fixture,
     * it is used to preserve
     * @deprecated since 0.2.1
     *
     * @var array
     */
    protected $_originalConfigurationXml = array();

    /**
     * Hash of current scope instances (store, website, group)
     *
     * @deprecated since 0.2.1
     * @return array
     */
    protected $_currentScope = array();


    /**
     * Set fixture options
     *
     * @param array $options
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;
        return $this;
    }

    /**
     * Retrieve fixture options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Sets storage for fixtures
     *
     * @param Varien_Object $storage
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    public function setStorage(Varien_Object $storage)
    {
        $this->_storage = $storage;
        return $this;
    }

    /**
     * Retrieve fixture storage
     *
     * @return Varien_Object
     */
    public function getStorage()
    {
        return $this->_storage;
    }

    /**
     * Retrieves storage data for a particular fixture scope
     *
     * @param string $key
     * @param string|null $scope
     * @return mixed
     */
    public function getStorageData($key, $scope = null)
    {
        if ($scope === null) {
            $scope = $this->getScope();
        }

        $dataKey = sprintf('%s_%s', $scope, $key);

        return $this->getStorage()->getData($dataKey);
    }


    /**
     * Sets storage data for a particular fixture scope
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $scope
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    public function setStorageData($key, $value, $scope = null)
    {
        if ($scope === null) {
            $scope = $this->getScope();
        }

        $dataKey = sprintf('%s_%s', $scope, $key);

        $this->getStorage()->setData($dataKey, $value);

        return $this;
    }


    /**
     * Returns current fixture scope
     *
     * @return string
     */
    public function getScope()
    {
        return $this->_scope;
    }

    /**
     * Sets current fixture scope
     *
     * @param string $scope EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_LOCAL|EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    public function setScope($scope)
    {
        $this->_scope = $scope;
        return $this;
    }

    /**
     * Check that current fixture scope is equal to SCOPE_SHARED
     *
     * @return boolean
     */
    public function isScopeShared()
    {
        return $this->getScope() === self::SCOPE_SHARED;
    }

    /**
     * Check that current fixture scope is equal to SCOPE_LOCAL
     *
     * @return boolean
     */
    public function isScopeLocal()
    {
        return $this->getScope() === self::SCOPE_LOCAL;
    }

    /**
     * Check that current fixture scope is equal to SCOPE_DEFAULT
     *
     * @return boolean
     */
    public function isScopeDefault()
    {
        return $this->getScope() === self::SCOPE_DEFAULT;
    }

    /**
     * Loads fixture files from test case annotations
     *
     * @param PHPUnit_Framework_TestCase $testCase
     * @return PHPUnit_Framework_TestCase
     */
    public function loadByTestCase(PHPUnit_Framework_TestCase $testCase)
    {
        $fixtures = EcomDev_PHPUnit_Test_Case_Util::getAnnotationByNameFromClass(
            get_class($testCase), 'loadFixture', array('class', 'method'), $testCase->getName(false)
        );

        $this->_loadFixtureFiles($fixtures, $testCase);
        return $this;
    }

    /**
     * Loads fixture files from test class annotations
     *
     * @param string $className
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    public function loadForClass($className)
    {
        $fixtures = EcomDev_PHPUnit_Test_Case_Util::getAnnotationByNameFromClass(
            $className, 'loadSharedFixture', 'class'
        );

        $this->_loadFixtureFiles($fixtures, $className);
        return $this;
    }

    /**
     * Loads test case cache on off annotations
     *
     * @param array $annotations
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _parseCacheOptions($annotations)
    {
        return $this;
    }

    /**
     * Sets fixture value
     *
     * @param string $key
     * @param array[] $value
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    public function setFixtureValue($key, $value)
    {
        $this->_fixture[$key] = $value;
        return $this;
    }

    /**
     * Returns value from fixture
     *
     * @param $key
     * @return array[]
     */
    public function getFixtureValue($key)
    {
        if (isset($this->_fixture[$key])) {
            return $this->_fixture[$key];
        }

        return array();
    }

    /**
     * Loads fixture files
     *
     * @param array                            $fixtures
     * @param string|EcomDev_PHPUnit_Test_Case $classOrInstance
     *
     * @throws RuntimeException
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _loadFixtureFiles(array $fixtures, $classOrInstance)
    {
        $isShared = ($this->isScopeShared() || !$classOrInstance instanceof PHPUnit_Framework_TestCase);
        foreach ($fixtures as $fixture) {
            if (empty($fixture) && $isShared) {
                $fixture = self::DEFAULT_SHARED_FIXTURE_NAME;
            } elseif (empty($fixture)) {
                $fixture = $classOrInstance->getName(false);
            }

            $className = (is_string($classOrInstance) ? $classOrInstance : get_class($classOrInstance));
            $filePath = EcomDev_PHPUnit_Test_Case_Util::getYamlLoader()
                ->resolveFilePath($className, EcomDev_PHPUnit_Model_Yaml_Loader::TYPE_FIXTURE, $fixture);

            if (!$filePath) {
                throw new RuntimeException('Unable to load fixture for test: '.$fixture);
            }

            $this->loadYaml($filePath);
        }

        return $this;
    }

    /**
     * Load YAML file
     *
     * @param string $filePath
     * @return EcomDev_PHPUnit_Model_Fixture
     * @throws InvalidArgumentException if file is not a valid YAML file
     */
    public function loadYaml($filePath)
    {
        $data = EcomDev_PHPUnit_Test_Case_Util::getYamlLoader()->load($filePath);

        if (empty($this->_fixture)) {
            $this->_fixture = $data;
        } else {
            $this->_fixture = array_merge_recursive($this->_fixture, $data);
        }

        return $this;
    }

    /**
     * Returns list of available processors for fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_ProcessorInterface[]
     */
    public function getProcessors()
    {
        if (empty($this->_processors)) {
            $processorsNode = Mage::getConfig()->getNode(self::XML_PATH_FIXTURE_PROCESSORS);
            foreach ($processorsNode->children() as $code => $processorAlias) {
                $processor = Mage::getSingleton((string)$processorAlias);
                if ($processor instanceof EcomDev_PHPUnit_Model_Fixture_ProcessorInterface) {
                    $this->_processors[$code] = $processor;
                }
            }
        }

        return $this->_processors;
    }

    /**
     * Applies loaded fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    public function apply()
    {
        $processors = $this->getProcessors();
        // Initialize fixture processors
        foreach ($processors as $processor) {
            $processor->initialize($this);
        }

        $this->setStorageData(self::STORAGE_KEY_FIXTURE, $this->_fixture);

        foreach ($this->_fixture as $part => $data) {
            if (isset($processors[$part])) {
                $processors[$part]->apply($data, $part, $this);
            }
        }

        // Clear fixture for getting rid of double processing
        $this->_fixture = array();
        return $this;
    }

    /**
     * Reverts environment to previous state
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    public function discard()
    {
        $fixture = $this->getStorageData(self::STORAGE_KEY_FIXTURE);

        if (!is_array($fixture)) {
            $fixture = array();
        }

        $this->_fixture = $fixture;
        $this->setStorageData(self::STORAGE_KEY_FIXTURE, null);

        $processors = $this->getProcessors();
        foreach ($this->_fixture as $part => $data) {
            if (isset($processors[$part])) {
                $processors[$part]->discard($data, $part, $this);
            }
        }

        $this->_fixture = array();
    }

    /**
     * Applies cache options for current test or test case
     *
     * @param array $options
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _applyCacheOptions($options)
    {
        return $this;
    }

    /**
     * Discards changes that were made to Magento cache
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _discardCacheOptions()
    {
        return $this;
    }

    /**
     * Applies fixture configuration values into Mage_Core_Model_Config
     *
     * @param array $configuration
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     * @throws InvalidArgumentException in case if wrong configuration array supplied
     */
    protected function _applyConfig($configuration)
    {
        return $this;
    }

    /**
     * Applies raw xml data to config node
     *
     * @param array $configuration
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     * @throws InvalidArgumentException in case of wrong configuration data passed
     */
    protected function _applyConfigXml($configuration)
    {
        return $this;
    }

    /**
     * Restores config to a previous configuration scope
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _restoreConfig()
    {
        return $this;
    }

    /**
     * Reverts fixture configuration values in Mage_Core_Model_Config
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _discardConfig()
    {
        return $this;
    }

    /**
     * Reverts fixture configuration xml values in Mage_Core_Model_Config
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _discardConfigXml()
    {
        return $this;
    }

    /**
     * Applies table data into test database
     *
     * @param array $tables
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _applyTables($tables)
    {
        return $this;
    }

    /**
     * Removes table data from test data base
     *
     * @param array $tables
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _discardTables($tables)
    {
        return $this;
    }

    /**
     * Setting config value with applying the values to stores and websites
     *
     * @param string $path
     * @param string $value
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _setConfigNodeValue($path, $value)
    {
        return $this;
    }

    /**
     * Retrieves eav loader for a particular entity type
     *
     * @param string $entityType
     * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_AbstractEav
     * @deprecated since 0.3.0
     */
    protected function _getEavLoader($entityType)
    {
		return $this->_getComplexLoader($entityType, 'EAV');
	}

    /**
     * Retrieves the loader for a particular entity type and data type
     *
     * @throws InvalidArgumentException
     * @param string $entityType
     * @param string $dataType
     * @return EcomDev_PHPUnit_Model_Mysql4_Fixture
     * @deprecated since 0.3.0
     */
    protected function _getComplexLoader($entityType, $dataType)
    {
	    if(!$dataType) {
		    throw new InvalidArgumentException('Must specify a data type for the loader');
	    }

	    $reflection = EcomDev_Utils_Reflection::getReflection($this);

        $loaders = Mage::getConfig()->getNode($reflection->getConstant("XML_PATH_FIXTURE_{$dataType}_LOADERS"));

        if (isset($loaders->$entityType)) {
            $classAlias = (string)$loaders->$entityType;
        } elseif (isset($loaders->{self::DEFAULT_EAV_LOADER_NODE})) {
            $classAlias = (string)$loaders->{self::DEFAULT_EAV_LOADER_NODE};
        } else {
            $classAlias = self::DEFAULT_EAV_LOADER_CLASS;
        }

        return Mage::getResourceSingleton($classAlias);
    }

    /**
     * Applies fixture EAV values
     *
     * @param array $entities
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _applyEav($entities)
    {
        return $this;
    }

    /**
     * Clean applied eav data
     *
     * @param array $entities
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _discardEav($entities)
    {
        return $this;
    }

    /**
     * Applies scope fixture,
     * i.e., website, store, store group
     *
     * @param array $types
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _applyScope($types)
    {
        return $this;
    }

    /**
     * Handle scope row data
     *
     * @param string $type
     * @param array $row
     * @return boolean|Mage_Core_Model_Abstract
     * @deprecated since 0.3.0
     */
    protected function _handleScopeRow($type, $row)
    {
        return false;
   }

    /**
     * Validate scope data
     *
     * @param array $types
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _validateScope($types)
    {
        return $this;
    }

    /**
     * Removes scope fixture changes,
     * i.e., website, store, store group
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _discardScope()
    {
        return $this;
    }

    /**
     * Returns VFS wrapper instance
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Vfs
     * @throws PHPUnit_Framework_SkippedTestError
     */
    public function getVfs()
    {
        if ($this->_vfs !== null) {
            return $this->_vfs;
        }

        if (!class_exists('\org\bovigo\vfs\vfsStream')
            && is_dir(Mage::getBaseDir('lib')  . DS . 'vfsStream' . DS . 'src')) {
            spl_autoload_register(array($this, 'vfsAutoload'), true, true);
        }

        if( class_exists('\org\bovigo\vfs\vfsStream') ){
            $this->_vfs = Mage::getModel('ecomdev_phpunit/fixture_vfs');
            return $this->_vfs;
        }

        throw new PHPUnit_Framework_SkippedTestError(
            'The test was skipped, since vfsStream component is not installed. '
            . 'Try install submodules required for this functionality'
        );
    }

    /**
     * Autoloader for vfs
     *
     * @param string $className
     * @return bool
     */
    public function vfsAutoload($className)
    {
        if (strpos($className, 'org\\bovigo\\vfs') === false) {
            return false;
        }

        $fileName = 'vfsStream' . DS . 'src' . DS . 'main' . DS . 'php' . DS
            . strtr(trim($className, '\\'), '\\', DS) . '.php';

        return include $fileName;
    }

    /**
     * Applies VFS structure fixture
     *
     * @param array $data
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _applyVfs($data)
    {
        return $this;
    }

    /**
     * Discards VFS structure fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.3.0
     */
    protected function _discardVfs()
    {
        return $this;
    }
}
