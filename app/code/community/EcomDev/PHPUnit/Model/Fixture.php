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

// Loading Spyc yaml parser,
// becuase Symfony component is not working propertly with nested
require_once 'Spyc/spyc.php';

/**
 * Fixture model for Magento unit tests
 *
 * Created for operations with different fixture types
 *
 */
class EcomDev_PHPUnit_Model_Fixture
    extends Mage_Core_Model_Abstract
    implements EcomDev_PHPUnit_Model_Fixture_Interface
{
    // Configuration path for eav loaders
    const XML_PATH_FIXTURE_EAV_LOADERS = 'phpunit/suite/fixture/eav';

    // Default eav loader class node in loaders configuration
    const DEFAULT_EAV_LOADER_NODE = 'default';

    // Default eav loader class alias
    const DEFAULT_EAV_LOADER_CLASS = 'ecomdev_phpunit/fixture_eav_default';

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
     *        'tablename' => array(
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
     * Fixture options
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Associative array of configuration nodes xml that was changed by fixture,
     * it is used to preserve
     *
     * @var array
     */
    protected $_originalConfigurationXml = array();

    /**
     * Hash of current scope instances (store, website, group)
     *
     * @return array
     */
    protected $_currentScope = array();


    /**
     * Model constuctor, just defines wich resource model to use
     * (non-PHPdoc)
     * @see Varien_Object::_construct()
     */
    protected function _construct()
    {
        $this->_init('ecomdev_phpunit/fixture');
    }

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
     * Loads fixture from test case annotations
     *
     * @param EcomDev_PHPUnit_Test_Case $testCase
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    public function loadByTestCase(EcomDev_PHPUnit_Test_Case $testCase)
    {
        $fixtures = $testCase->getAnnotationByName(
            'loadFixture',
            array('class', 'method')
        );

        foreach ($fixtures as $fixture) {
            if (empty($fixture)) {
                $fixture = null;
            }

            $filePath = $testCase->getYamlFilePath('fixtures', $fixture);

            if (!$filePath) {
                throw new RuntimeException('Unable to load fixture for test');
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
        $data = Spyc::YAMLLoad($filePath);

        if (empty($this->_fixture)) {
            $this->_fixture = $data;
        } else {
            $this->_fixture = array_merge_recursive($this->_fixture, $data);
        }

        return $this;
    }

    /**
     * Applies loaded fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    public function apply()
    {
        $reflection = EcomDev_Utils_Reflection::getRelflection($this);
        foreach ($this->_fixture as $part => $data) {
            $method = '_apply' . uc_words($part, '', '_');
            if ($reflection->hasMethod($method)) {
                $this->$method($data);
            }
        }

        return $this;
    }

    /**
     * Reverts environment to previous state
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    public function discard()
    {
        $reflection = EcomDev_Utils_Reflection::getRelflection($this);
        foreach ($this->_fixture as $part => $data) {
            $method = '_discard' . uc_words($part, '', '_');
            if ($reflection->hasMethod($method)) {
                $this->$method($data);
            }
        }

        $this->_fixture = array();
    }

    /**
     * Applies fixture configuration values into Mage_Core_Model_Config
     *
     * @param array $configuration
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _applyConfig($configuration)
    {
        if (!is_array($configuration)) {
            throw new InvalidArgumentException('Configuration part should be an associative list');
        }
        Mage::getConfig()->loadScopeSnapshot();
        foreach ($configuration as $path => $value) {
            $this->_setConfigNodeValue($path, $value);
        }
        Mage::getConfig()->loadDb();
        Mage::app()->reinitStores();
        return $this;
    }

    /**
     * Applies raw xml data to config node
     *
     * @param array $configuration
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _applyConfigXml($configuration)
    {
        if (!is_array($configuration)) {
            throw new InvalidArgumentException('Configuration part should be an associative list');
        }

        foreach ($configuration as $path => $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException('Configuration value should be a valid xml string');
            }
            try {
                $xmlElement = new Varien_Simplexml_Element($value);
            } catch (Exception $e) {
                throw new InvalidArgumentException('Configuration value should be a valid xml string', 0, $e);
            }

            $node = Mage::getConfig()->getNode($path);

            if (!$node) {
                throw new InvalidArgumentException('Configuration value should be a valid xml string');
            }

            $this->_originalConfigurationXml[$path] = $node->asNiceXml();
            $node->extend($xmlElement, true);
        }

        return $this;
    }

    /**
     * Reverts fixture configuration values in Mage_Core_Model_Config
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _discardConfig()
    {
        Mage::getConfig()->loadScopeSnapshot();
        Mage::getConfig()->loadDb();
        Mage::app()->reinitStores();
        return $this;
    }

    /**
     * Reverts fixture configuration xml values in Mage_Core_Model_Config
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _discardConfigXml()
    {
        foreach ($this->_originalConfigurationXml as $path => $value) {
            $node = Mage::getConfig()->getNode($path);
            $parentNode = $node->getParent();
            unset($parentNode->{$node->getName()});
            $oldXml = new Varien_Simplexml_Element($value);
            $parentNode->appendChild($oldXml);
        }

        $this->_originalConfigurationXml = array();
        return $this;
    }

    /**
     * Applies table data into test database
     *
     * @param array $tables
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _applyTables($tables)
    {
        if (!is_array($tables)) {
            throw new InvalidArgumentException(
                'Tables part should be an associative list with keys as table entity and values as list of associative rows'
            );
        }

        foreach ($tables as $tableEntity => $data) {
            $this->getResource()->cleanTable($tableEntity);
            if (!empty($data)) {
                $this->getResource()->loadTableData($tableEntity, $data);
            }
        }
    }

    /**
     * Removes table data from test data base
     *
     * @param array $tables
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _discardTables($tables)
    {
        if (!is_array($tables)) {
            throw new InvalidArgumentException(
                'Tables part should be an associative list with keys as table entity and values as list of associative rows'
            );
        }

        foreach ($tables as $tableEntity => $data) {
            $this->getResource()->cleanTable($tableEntity);
        }
    }

    /**
     * Setting config value with applying the values to stores and websites
     *
     * @param string $path
     * @param string $value
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _setConfigNodeValue($path, $value)
    {
        $pathArray = explode('/', $path);

        $scope = array_shift($pathArray);

        switch ($scope) {
            case 'stores':
                $storeCode = array_shift($pathArray);
                Mage::app()->getStore($storeCode)->setConfig(
                    implode('/', $pathArray), $value
                );
                break;

            case 'websites':
                $websiteCode = array_shift($pathArray);
                $website = Mage::app()->getWebsite($websiteCode);
                $website->setConfig(implode('/', $pathArray), $value);
                break;

            default:
                Mage::getConfig()->setNode($path, $value);
                break;
        }

        return $this;
    }

    /**
     * Retrieves eav loader for a particular entity type
     *
     * @param string $entityType
     * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_Eav_Abstract
     */
    protected function _getEavLoader($entityType)
    {
        $loaders = Mage::getConfig()->getNode(self::XML_PATH_FIXTURE_EAV_LOADERS);

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
     * @param array $configuration
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _applyEav($entities)
    {
        if (!is_array($entities)) {
            throw new InvalidArgumentException('EAV part should be an associative list with rows as value and entity type as key');
        }

        foreach ($entities as $entityType => $values) {
            $this->_getEavLoader($entityType)
                ->setOptions($this->_options)
                ->loadEntity($entityType, $values);
        }

        return $this;
    }

    /**
     * Clean applied eav data
     *
     * @param array $entities
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _discardEav($entities)
    {
        foreach (array_keys($entities) as $entityType) {
            $this->_getEavLoader($entityType)
                ->cleanEntity($entityType);
        }

        return $this;
    }

    /**
     * Applies scope fixture,
     * i.e., website, store, store group
     *
     * @param array $types
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _applyScope($types)
    {
        $modelByType = array(
            'store' => 'core/store',
            'group' => 'core/store_group',
            'website' => 'core/website'
        );

        Mage::app()->disableEvents();

        foreach ($types as $type => $rows) {
            if (!isset($modelByType[$type])) {
                throw new RuntimeException(sprintf('Unknown "%s" scope type specified', $type));
            }

            foreach ($rows as $row) {
                $scopeModel = Mage::getModel($modelByType[$type]);
                $this->_currentScope[$type][] = $scopeModel;
                $scopeModel->setData($row);
                // Change property for saving new objects with specified ids
                EcomDev_Utils_Reflection::setRestrictedPropertyValue(
                    $scopeModel->getResource(), '_useIsObjectNew', true
                );
                $scopeModel->isObjectNew(true);
                $scopeModel->save();
                // Revert changed property
                EcomDev_Utils_Reflection::setRestrictedPropertyValue(
                    $scopeModel->getResource(), '_useIsObjectNew', false
                );

            }
        }
        Mage::app()->enableEvents();
        Mage::app()->reinitStores();
        return $this;
    }

    /**
     * Removes scope fixture changes,
     * i.e., website, store, store group
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _discardScope()
    {
        Mage::app()->disableEvents();
        $scope = array_reverse($this->_currentScope);
        foreach ($scope as $models) {
            foreach ($models as $model) {
                $model->delete();
            }
        }

        $this->_currentScope = array();
        Mage::app()->getCache()->clean(
            Zend_Cache::CLEANING_MODE_MATCHING_TAG,
            array(Mage_Core_Model_Mysql4_Collection_Abstract::CACHE_TAG)
        );
        Mage::app()->enableEvents();
        Mage::app()->reinitStores();
        return $this;
    }
}
