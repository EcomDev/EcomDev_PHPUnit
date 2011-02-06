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
class EcomDev_PHPUnit_Model_Fixture extends Mage_Core_Model_Abstract
{
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
     * Associative array of configuration values that was changed by fixture,
     * it is used to preserve
     *
     * @var array
     */
    protected $_originalConfiguration = array();

    /**
     * Associative array of configuration nodes xml that was changed by fixture,
     * it is used to preserve
     *
     * @var array
     */
    protected $_originalConfigurationXml = array();

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
        foreach ($configuration as $path => $value) {
            $this->_originalConfiguration[$path] = (string) Mage::getConfig()->getNode($path);
            $this->_setConfigNodeValue($path, $value);
        }

        return $this;
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
                Mage::app()->getWebsite($websiteCode)->setConfig(
                    implode('/', $pathArray), $value
                );
                break;

            default:
                Mage::getConfig()->setNode($path, $value);
                break;
        }

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
        foreach ($this->_originalConfiguration as $path => $value) {
            $this->_setConfigNodeValue($path, $value);
        }

        $this->_originalConfiguration = array();
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
            $this->getResource()->loadTableData($tableEntity, $data);
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
     * @todo Create Implementation for EAV models
     */

    /**
     * @todo Create Implementation for Websites/Stores/Groups
     */
}
