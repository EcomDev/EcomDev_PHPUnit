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
     */
    protected $_originalConfiguration = array();

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
            $this->_originalConfiguration[$path] = Mage::getConfig()->getNode($path);
            Mage::getConfig()->setNode($path, $value);
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
            Mage::getConfig()->setNode($path, $value);
        }

        $this->_originalConfiguration = array();
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
