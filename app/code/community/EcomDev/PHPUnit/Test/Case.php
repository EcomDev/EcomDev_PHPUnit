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
// becuase Symfony component is not working propertly with nested associations
require_once 'Spyc/spyc.php';

/**
 * Basic test case class
 *
 *
 */
abstract class EcomDev_PHPUnit_Test_Case extends PHPUnit_Framework_TestCase
{
    /**
     * The expectations for current test are loaded here
     *
     * @var Varien_Object|null
     */
    protected $_expectations = null;

    /**
     * Original store kept for tearDown,
     * if was set in test method
     *
     * @var Mage_Core_Model_Store
     */
    protected $_originalStore = null;

    /**
     * Loads expectations for current test case
     *
     * @throws RuntimeException if no expectation was found
     * @return Varien_Object
     */
    protected function _getExpectations()
    {
        if ($this->_expectations === null) {
            $annotations = $this->getAnnotations();
            if (isset($annotations['method']['loadExpectation'])) {
                // Load expectation by annotations
                $expectations = array();
                foreach ($annotations['method']['loadExpectation'] as $expectation) {
                    if (empty($expectation)) {
                        $expectation = null;
                    }

                    $expectationFile = $this->_getYamlFilePath('expectations', $expectation);
                    if ($expectationFile) {
                        $expectations = array_merge_recursive(
                            $expectations, Spyc::YAMLLoad($expectationFile)
                        );
                    } else {
                        $text = 'There was no expectation defined for current test case';
                        if ($expectation) {
                            $text = sprintf('Cannot load expectation %s', $expectation);
                        }
                        throw new RuntimeException($test);
                    }
                }
            } else {
                // Load expectations by test name
                $expectationFile = $this->_getYamlFilePath('expectations');
                if ($expectationFile) {
                    $expectations = Spyc::YAMLLoad($expectationFile);
                } else {
                    throw new RuntimeException('There was no expectation defined for current test case');
                }
            }

            $this->_expectations = new Varien_Object($expectations);
        }

        $arguments = func_get_args();
        if ($arguments) {
           if (count($arguments) > 1) {
               $dataKey = call_user_func_array('sprintf', $arguments);
           } else {
               $dataKey = array_shift($arguments);
           }

           $dataPart = $this->_expectations->getData($dataKey);
           if (!is_array($dataPart)) {
               throw new InvalidArgumentException(
                   'Argument values for specifying special scope of expectations should be presented '
                   . ' in expectation file and should be an associative list (dataKey: "' . $dataKey . '")'
               );
           }

           return new Varien_Object($dataPart);
        }

        return $this->_expectations;
    }


    /**
     * Retrieves fixture model singleton
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function _getFixture()
    {
        return Mage::getSingleton('ecomdev_phpunit/fixture');
    }

    /**
     * Loads YAML file from directory inside of the unit test class
     * Enter description here ...
     *
     * @param string $type type of YAML data (fixtures,expectations,dataproviders)
     * @param string|null $name the file name for loading, if equals to null,
     *                          the current test name will be used
     * @return string|boolean
     */
    protected function _getYamlFilePath($type, $name = null)
    {
        if ($name === null) {
            $name = $this->getName(false);
        }

        if (strrpos($name, '.yaml') !== strlen($name) - 5) {
            $name .= '.yaml';
        }

        $classFileObject = new SplFileInfo(
            EcomDev_Utils_Reflection::getRelflection($this)->getFileName()
        );

        $filePath = $classFileObject->getPath() . DS
                  . $classFileObject->getBasename('.php') . DS
                  . $type . DS . $name;

        if (file_exists($filePath)) {
            return $filePath;
        }

        return false;
    }

    /**
     * Initializes a particular test environment
     *
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $annotations = $this->getAnnotations();
        if (isset($annotations['method']['loadFixture'])) {
            foreach ($annotations['method']['loadFixture'] as $fixture) {
                if (empty($fixture)) {
                    $fixture = null;
                }

                $filePath = $this->_getYamlFilePath('fixtures', $fixture);
                if (!$filePath) {
                    throw new RuntimeException('Unable to load fixture for test');
                }

                $this->_getFixture()->loadYaml($filePath);
            }
        }
        // Pass methods for
        $this->_getFixture()->setOptions($annotations['method']);
        $this->_getFixture()->apply();
        parent::setUp();
    }

    /**
     * Implements default data provider functionality,
     * returns array data loaded from Yaml file with the same name as test method
     *
     * @return array
     */
    public function dataProvider($testName)
    {
        $this->setName($testName);
        $filePath = $this->_getYamlFilePath('providers');
        $this->setName(null);

        if (!$filePath) {
            throw new RuntimeException('Unable to load data provider for the current test');
        }

        return Spyc::YAMLLoad($filePath);
    }

    /**
     * Set current store scope for test
     *
     * @param int|string|Mage_Core_Model_Store $store
     * @return EcomDev_PHPUnit_Test_Case
     */
    public function setCurrentStore($store)
    {
        if (!$this->_originalStore) {
            $this->_originalStore = Mage::app()->getStore();
        }

        Mage::app()->setCurrentStore(
            Mage::app()->getStore($store)
        );
        return $this;
    }

    /**
     * Performs a clean up after a particular test was run
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::tearDown()
     */
    protected function tearDown()
    {
        if ($this->_originalStore) { // Remove store scope, that was set in test
            Mage::app()->setCurrentStore($this->_originalStore);
            $this->_originalStore = null;
        }

        $this->_expectations = null; // Clear expectation values
        $this->_getFixture()->discard(); // Clear applied fixture
        parent::tearDown();
    }

}