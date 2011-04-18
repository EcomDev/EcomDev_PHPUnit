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

    const XML_PATH_DEFAULT_FIXTURE_MODEL = 'phpunit/suite/fixture/model';
    const XML_PATH_DEFAULT_EXPECTATION_MODEL = 'phpunit/suite/expectatio/model';


    /**
     * List of system registry values replaced by test case
     *
     * @var array
     */
    protected $_replacedRegistry = array();

    /**
     * The expectations for current test are loaded here
     *
     * @var Varien_Object|null
     * @deprecated since 0.2.0
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
     * Retrieves annotation by its name from different sources (class, method)
     *
     *
     * @param string $name
     * @param array|string $sources
     * @return array
     */
    public function getAnnotationByName($name, $sources = 'method')
    {
        if (is_string($sources)) {
            $source = array($sources);
        }

        $allAnnotations = $this->getAnnotations();
        $annotation = array();

        // Walkthrough sources for annotation retrieval
        foreach ($sources as $source) {
            if (isset($allAnnotations[$source][$name])) {
                $annotation = array_merge(
                    $allAnnotations[$source][$name],
                    $annotation
                );
            }
        }

        return $annotation;
    }

    /**
     * Loads expectations for current test case
     *
     * @throws RuntimeException if no expectation was found
     * @return Varien_Object
     * @deprecated since 0.2.0, use self::expected() instead.
     */
    protected function _getExpectations()
    {
        $arguments = func_get_args();

        return $this->expected($arguments);
    }

    /**
     * Replaces Magento resource by mock object
     *
     *
     * @param string $type
     * @param string $classAlias
     * @param PHPUnit_Framework_MockObject_MockObject|PHPUnit_Framework_MockObject_MockBuilder $mock
     * @return EcomDev_PHPUnit_Test_Case
     */
    protected function replaceByMock($type, $classAlias, $mock)
    {
        if ($mock instanceof PHPUnit_Framework_MockObject_MockBuilder) {
            $mock = $mock->getMock();
        } elseif (!$mock instanceof PHPUnit_Framework_MockObject_MockObject) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
              1, 'PHPUnit_Framework_MockObject_MockObject'
            );
        }


        if ($type == 'helper' && strpos($classAlias, '/') === false) {
            $classAlias .= '/data';
        }

        if (in_array($type, array('model', 'resource_model'))) {
            Mage::getConfig()->replaceInstanceCreation($type, $classAlias, $mock);
            $type = str_replace('model', 'singleton', $type);
        } elseif ($type == 'block') {
            Mage::app()->getLayout()->replaceBlockCreation($classAlias, $mock);
        }

        if (in_array($type, array('singleton', 'resource_singleton', 'helper'))) {
            $registryPath = '_' . $type . '/' . $classAlias;
            $this->replaceRegistry($registryPath, $mock);
        }

        return $this;
    }

    /**
     * Replaces value in Magento system registry
     *
     * @param string $key
     * @param mixed $value
     */
    protected function replaceRegistry($key, $value)
    {
        $oldValue = Mage::registry($key);
        Mage::register($key, $value, true);

        $this->_replacedRegistry[$key] = $oldValue;
        return $this;
    }

    /**
     * Shortcut for expectation data object retrieval
     * Can be called with arguments array or in usual method
     *
     * @param string|array $pathFormat
     * @param mixed $arg1
     * @param mixed $arg2 ...
     * @return Varien_Object
     */
    protected function expected($firstArgument = null)
    {
        if (!$this->getExpectation()->isLoaded()) {
            $this->getExpectation()->loadByTestCase($this);
            $this->getExpectation()->apply();
        }

        if (!is_array($firstArgument)) {
            $arguments = func_get_args();
        } else {
            $arguments = $firstArgument;
        }

        $pathFormat = null;
        if ($arguments) {
            $pathFormat = array_shift($arguments);
        }

        return $this->getExpectation()
            ->getDataObject($pathFormat, $arguments);
    }

    /**
     * Retrieve mock builder for grouped class alias
     *
     * @param string $type block|model|helper
     * @param string $classAlias
     * @return PHPUnit_Framework_MockObject_MockBuilder
     */
    public function getGroupedClassMockBuilder($type, $classAlias)
    {
        $className = $this->getGroupedClassName($type, $classAlias);

        return $this->getMockBuilder($className);
    }

    /**
     * Retrieves a mock builder for a block class alias
     *
     * @param string $classAlias
     * @return PHPUnit_Framework_MockObject_MockBuilder
     */
    public function getBlockMockBuilder($classAlias)
    {
        return $this->getGroupedClassMockBuilder('block', $classAlias);
    }

    /**
     * Retrieves a mock builder for a model class alias
     *
     * @param string $classAlias
     * @return PHPUnit_Framework_MockObject_MockBuilder
     */
    public function getModelMockBuilder($classAlias)
    {
        return $this->getGroupedClassMockBuilder('model', $classAlias);
    }

    /**
     * Retrieves a mock builder for a resource model class alias
     *
     * @param string $classAlias
     * @return PHPUnit_Framework_MockObject_MockBuilder
     */
    public function getResourceModelMockBuilder($classAlias)
    {
        return $this->getGroupedClassMockBuilder('resource_model', $classAlias);
    }

    /**
     * Retrieves a mock builder for a helper class alias
     *
     * @param string $classAlias
     * @return PHPUnit_Framework_MockObject_MockBuilder
     */
    public function getHelperMockBuilder($classAlias)
    {
        return $this->getGroupedClassMockBuilder('helper', $classAlias);
    }

    /**
     * Retrieves a mock object for the specified model class alias.
     *
     * @param  string  $classAlias
     * @param  array   $methods
     * @param  boolean $isAbstract
     * @param  array   $constructorArguments
     * @param  string  $mockClassAlias
     * @param  boolean $callOriginalConstructor
     * @param  boolean $callOriginalClone
     * @param  boolean $callAutoload
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function getModelMock($classAlias, $methods = array(), $isAbstract = false,
                                 array $constructorArguments = array(),
                                 $mockClassAlias = '',  $callOriginalConstructor = true,
                                 $callOriginalClone = true, $callAutoload = true)
    {
        return $this->getGroupedClassMock('model', $methods, $isAbstract,
                                   $constructorArguments, $mockClassAlias,
                                   $callOriginalConstructor, $callOriginalClone,
                                   $callAutoload);
    }

	/**
     * Retrieves a mock object for the specified resource model class alias.
     *
     * @param  string  $classAlias
     * @param  array   $methods
     * @param  boolean $isAbstract
     * @param  array   $constructorArguments
     * @param  string  $mockClassAlias
     * @param  boolean $callOriginalConstructor
     * @param  boolean $callOriginalClone
     * @param  boolean $callAutoload
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function getResourceModelMock($classAlias, $methods = array(), $isAbstract = false,
                                 array $constructorArguments = array(),
                                 $mockClassAlias = '',  $callOriginalConstructor = true,
                                 $callOriginalClone = true, $callAutoload = true)
    {
        return $this->getGroupedClassMock('resource_model', $methods, $isAbstract,
                                   $constructorArguments, $mockClassAlias,
                                   $callOriginalConstructor, $callOriginalClone,
                                   $callAutoload);
    }

    /**
     * Retrieves a mock object for the specified helper class alias.
     *
     * @param  string  $classAlias
     * @param  array   $methods
     * @param  boolean $isAbstract
     * @param  array   $constructorArguments
     * @param  string  $mockClassAlias
     * @param  boolean $callOriginalConstructor
     * @param  boolean $callOriginalClone
     * @param  boolean $callAutoload
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function getHelperMock($classAlias, $methods = array(), $isAbstract = false,
                                 array $constructorArguments = array(),
                                 $mockClassAlias = '',  $callOriginalConstructor = true,
                                 $callOriginalClone = true, $callAutoload = true)
    {
        return $this->getGroupedClassMock('helper', $methods, $isAbstract,
                                   $constructorArguments, $mockClassAlias,
                                   $callOriginalConstructor, $callOriginalClone,
                                   $callAutoload);
    }

    /**
     * Retrieves a mock object for the specified helper class alias.
     *
     * @param  string  $classAlias
     * @param  array   $methods
     * @param  boolean $isAbstract
     * @param  array   $constructorArguments
     * @param  string  $mockClassAlias
     * @param  boolean $callOriginalConstructor
     * @param  boolean $callOriginalClone
     * @param  boolean $callAutoload
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function getBlockMock($classAlias, $methods = array(), $isAbstract = false,
                                 array $constructorArguments = array(),
                                 $mockClassAlias = '',  $callOriginalConstructor = true,
                                 $callOriginalClone = true, $callAutoload = true)
    {
        return $this->getGroupedClassMock('block', $methods, $isAbstract,
                                   $constructorArguments, $mockClassAlias,
                                   $callOriginalConstructor, $callOriginalClone,
                                   $callAutoload);
    }

    /**
     * Returns class name by grouped class alias
     *
     * @param string $type block/model/helper/resource_model
     * @param string $classAlias
     */
    protected function getGroupedClassName($type, $classAlias)
    {
        if ($type === 'resource_model') {
            return Mage::getConfig()->getResourceModelClassName($classAlias);
        }

        return Mage::getConfig()->getGroupedClassName($type, $classAlias);
    }

    /**
     * Retrieves a mock object for the specified grouped class alias.
     *
     * @param  string  $type
     * @param  string  $classAlias
     * @param  array   $methods
     * @param  boolean $isAbstract
     * @param  array   $constructorArguments
     * @param  string  $mockClassAlias
     * @param  boolean $callOriginalConstructor
     * @param  boolean $callOriginalClone
     * @param  boolean $callAutoload
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function getGroupedClassMock($type, $classAlias, $methods = array(), $isAbstract = false,
                                        array $constructorArguments = array(),
                                        $mockClassAlias = '',  $callOriginalConstructor = true,
                                        $callOriginalClone = true, $callAutoload = true)
    {
        if (!empty($mockClassAlias)) {
            $mockClassName = $this->getGroupedClassName($type, $mockClassAlias);
        } else {
            $mockClassName = '';
        }

        $mockBuilder = $this->getGroupedClassMockBuilder($type, $classAlias);

        if ($callOriginalConstructor === false) {
            $mockBuilder->disableOriginalConstructor();
        }

        if ($callOriginalClone === false) {
            $mockBuilder->disableOriginalClone();
        }

        if ($callAutoload === false) {
            $mockBuilder->disableAutoload();
        }

        $mockBuilder->setMethods($methods);
        $mockBuilder->setConstructorArgs($arguments);
        $mockBuilder->setMockClassName($mockClassName);

        if ($isAbstract) {
            return $mockBuilder->getMockForAbstractClass();
        }

        return $mockBuilder->getMock();
    }

	/**
     * Retrieves fixture model singleton
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     * @deprecated since 0.2.0 use getFixture() method instead
     */
    protected function _getFixture()
    {
        return $this->getFixture();
    }

	/**
     * Retrieves fixture model singleton
     *
     * @return EcomDev_PHPUnit_Model_Fixture
     */
    protected function getFixture()
    {
        return Mage::getSingleton($this->getLoadableClassAlias(
            'fixture',
            self::XML_PATH_DEFAULT_FIXTURE_MODEL
        ));;
    }

    /**
     * Returns expectation model singleton
     *
     * @return EcomDev_PHPUnit_Model_Expectation
     */
    protected function getExpectation()
    {
        return Mage::getSingleton($this->getLoadableClassAlias(
            'expectation',
            self::XML_PATH_DEFAULT_EXPECTATION_MODEL
        ));
    }


    /**
     * Retrieves loadable class alias from annotation or configuration node
     * E.g. class alias for fixture model can be specified via @fixtureModel annotation
     *
     * @param string $type
     * @param string $configPath
     */
    protected function getLoadableClassAlias($type, $configPath)
    {
        $annotationValue = $this->getAnnotationByName($type .'Model' , 'class');

        if (current($annotationValue)) {
            $classAlias = current($annotationValue);
        } else {
            $classAlias = Mage::getConfig()->getNode($configPath);
        }

        return $classAlias;
    }

    /**
     * Protected wrapper for _getYamlFilePath method. Backward campatibility.
     *
     * @see EcomDev_PHPUnit_Test_Case::getYamlFilePath()
     *
     * @param string $type type of YAML data (fixtures,expectations,dataproviders)
     * @param string|null $name the file name for loading, if equals to null,
     *                          the current test name will be used
     * @return string|boolean
     * @deprecated since 0.2.0
     */
    protected function _getYamlFilePath($type, $name = null)
    {
        return $this->getYamlFilePath($type, $name);
    }

    /**
     * Loads YAML file from directory inside of the unit test class
     *
     * @param string $type type of YAML data (fixtures,expectations,dataproviders)
     * @param string|null $name the file name for loading, if equals to null,
     *                          the current test name will be used
     * @return string|boolean
     */
    public function getYamlFilePath($type, $name = null)
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
        $this->getFixture()->loadByTestCase($this);

        $annotations = $this->getAnnotations();
        $this->getFixture()->setOptions($annotations['method']);
        $this->getFixture()->apply();
        parent::setUp();
    }

    /**
     * Implements default data provider functionality,
     * returns array data loaded from Yaml file with the same name as test method
     *
     * @param string $testName
     * @return array
     */
    public function dataProvider($testName)
    {
        $this->setName($testName);
        $filePath = $this->getYamlFilePath('providers');
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

        if ($this->getExpectation()->isLoaded()) {
            $this->getExpectation()->discard();
        }

        Mage::getConfig()->flushReplaceInstanceCreation();

        Mage::app()->getLayout()->reset();

        foreach ($this->_replacedRegistry as $registryPath => $originalValue) {
            Mage::register($registryPath, $originalValue, true);
        }

        $this->getFixture()->discard(); // Clear applied fixture
        parent::tearDown();
    }

}