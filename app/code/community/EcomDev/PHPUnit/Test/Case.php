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

use EcomDev_PHPUnit_Test_Case_Util as TestUtil;
use EcomDev_PHPUnit_Helper as TestHelper;

/**
 * Basic test case class, implements test helpers for easy working with Magento
 *
 * @method EcomDev_PHPUnit_Mock_Proxy mockClassAlias(string $type, $classAlias, array $methods = array(), array $constructorArgs = array())
 * @method EcomDev_PHPUnit_Mock_Proxy mockModel($classAlias, array $methods = array(), array $constructorArgs = array())
 * @method EcomDev_PHPUnit_Mock_Proxy mockBlock($classAlias, array $methods = array(), array $constructorArgs = array())
 * @method EcomDev_PHPUnit_Mock_Proxy mockHelper($classAlias, array $methods = array(), array $constructorArgs = array())
 * @method EcomDev_PHPUnit_Mock_Proxy mockSession($classAlias, array $methods = array())
 * @method EcomDev_PHPUnit_Mock_Proxy adminSession(array $resources = array())
 * @method EcomDev_PHPUnit_Mock_Proxy customerSession(int $customerId)
 * @method EcomDev_PHPUnit_Mock_Proxy guestSession()
 * @method Varien_Event_Observer generateObserver(array $eventData, string $eventName = null)
 */
abstract class EcomDev_PHPUnit_Test_Case extends PHPUnit_Framework_TestCase
{

    /**
     * @deprecated since 0.3.0
     **/
    const XML_PATH_DEFAULT_FIXTURE_MODEL = TestUtil::XML_PATH_DEFAULT_FIXTURE_MODEL;
    /**
     * @deprecated since 0.3.0
     **/
    const XML_PATH_DEFAULT_EXPECTATION_MODEL = TestUtil::XML_PATH_DEFAULT_EXPECTATION_MODEL;


    /**
     * List of system registry values replaced by test case
     *
     * @var array
     * @deprecated since 0.3.0
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
     * @deprecated since 0.3.0
     */
    protected $_originalStore = null;

    /**
     * Returns app for test case, created for type hinting
     * in the test case code
     *
     * @return EcomDev_PHPUnit_Model_App
     */
    public static function app()
    {
        return TestUtil::app();
    }

    /**
     * Returns a EcomDev_PHPUnit_Constraint_Or matcher object.
     *
     * @return EcomDev_PHPUnit_Constraint_Or
     */
    public static function logicalOr()
    {
        $constraints = func_get_args();

        $constraint = new EcomDev_PHPUnit_Constraint_Or;
        $constraint->setConstraints($constraints);

        return $constraint;
    }

    /**
     * Asserts that event was dispatched at least once
     *
     * @param array|string $eventName
     */
    public static function assertEventDispatched($eventName)
    {
        if (is_array($eventName)) {
            foreach ($eventName as $singleEventName) {
                self::assertEventDispatched($singleEventName);
            }
            return;
        }

        $actual = self::app()->getDispatchedEventCount($eventName);
        $message = sprintf('%s event was not dispatched', $eventName);
        self::assertGreaterThanOrEqual(1, $actual, $message);
    }

    /**
     * Asserts that event was not dispatched
     *
     * @param string|array $eventName
     */
    public static function assertEventNotDispatched($eventName)
    {
        if (is_array($eventName)) {
            foreach ($eventName as $singleEventName) {
                self::assertEventNotDispatched($singleEventName);
            }
            return;
        }

        $actual = self::app()->getDispatchedEventCount($eventName);
        $message = sprintf('%s event was dispatched', $eventName);
        self::assertEquals(0, $actual, $message);
    }

    /**
     * Assert that event was dispatched exactly $times
     *
     * @param string $eventName
     * @param int
     */
    public static function assertEventDispatchedExactly($eventName, $times)
    {
        $actual = self::app()->getDispatchedEventCount($eventName);
        $message = sprintf(
            '%s event was dispatched only %d times, but expected to be dispatched %d times',
            $eventName, $actual, $times
        );

        self::assertEquals($times, $actual, $message);
    }

    /**
     * Assert that event was dispatched at least $times
     *
     * @param string $eventName
     * @param int
     */
    public static function assertEventDispatchedAtLeast($eventName, $times)
    {
        $actual = self::app()->getDispatchedEventCount($eventName);
        $message = sprintf(
            '%s event was dispatched only %d times, but expected to be dispatched at least %d times',
            $eventName, $actual, $times
        );

        self::assertGreaterThanOrEqual($times, $actual, $message);
    }

    /**
     * Creates a constraint for checking that string is valid JSON
     *
     * @return EcomDev_PHPUnit_Constraint_Json
     */
    public static function isJson()
    {
        return new EcomDev_PHPUnit_Constraint_Json(
            EcomDev_PHPUnit_Constraint_Json::TYPE_VALID
        );
    }

    /**
     * Creates a constraint for checking that string
     * is matched expected JSON structure
     *
     * @param array $expectedValue
     * @param string $matchType
     * @return EcomDev_PHPUnit_Constraint_Json
     */
    public static function matchesJson(array $expectedValue, $matchType = EcomDev_PHPUnit_Constraint_Json::MATCH_AND)
    {
        return new EcomDev_PHPUnit_Constraint_Json(
            EcomDev_PHPUnit_Constraint_Json::TYPE_MATCH,
            $expectedValue,
            $matchType
        );
    }

    /**
     * Assert that string is a valid JSON
     *
     * @param string $string
     * @param string $message
     */
    public static function assertJson($string, $message = '')
    {
        self::assertThat($string, self::isJson(), $message);
    }

    /**
     * Assert that string is not a valid JSON
     *
     * @param string $string
     * @param string $message
     */
    public static function assertNotJson($string, $message = '')
    {
        self::assertThat($string, self::logicalNot(self::isJson()), $message);
    }

    /**
     * Assert that JSON string matches expected value,
     * Can accept different match type for matching logic.
     *
     * @param string $string
     * @param array $expectedValue
     * @param string $message
     * @param string $matchType
     */
    public static function assertJsonMatch($string, array $expectedValue, $message = '',
        $matchType = EcomDev_PHPUnit_Constraint_Json::MATCH_AND)
    {
        self::assertThat(
            $string,
            self::matchesJson($expectedValue, $matchType),
            $message
        );
    }

    /**
     * Assert that JSON string does not matches expected value,
     * Can accept different match type for matching logic.
     *
     * @param string $string
     * @param array $expectedValue
     * @param string $message
     * @param string $matchType
     */
    public static function assertJsonNotMatch($string, array $expectedValue, $message = '',
        $matchType = EcomDev_PHPUnit_Constraint_Json::MATCH_AND)
    {
        self::assertThat(
            $string,
            self::logicalNot(
                self::matchesJson($expectedValue, $matchType)
            ),
            $message
        );
    }


    /**
     * Retrieves the module name for current test case
     *
     * @return string
     * @throws RuntimeException if module name was not found for the passed class name
     * @deprecated since 0.3.0
     */
    public function getModuleName()
    {
        return TestUtil::getModuleName($this);
    }

    /**
     * Retrieves module name from call stack objects
     *
     * @return string
     * @throws RuntimeException if assertion is called in not from EcomDev_PHPUnit_Test_Case
     */
    protected static function getModuleNameFromCallStack()
    {
        return TestUtil::getModuleNameFromCallStack();
    }


    /**
     * Retrieves annotation by its name from different sources (class, method)
     *
     *
     * @param string $name
     * @param array|string $sources
     * @return array
     * @deprecated since 0.3.0
     */
    public function getAnnotationByName($name, $sources = 'method')
    {
        return TestUtil::getAnnotationByNameFromClass(get_class($this), $name, $sources, $this->getName(false));
    }

    /**
     * Retrieves annotation by its name from different sources (class, method) based on meta information
     *
     * @param string $className
     * @param string $name annotation name
     * @param array|string $sources
     * @param string $testName test method name
     * @return array
     * @deprecated since 0.3.0
     */
    public static function getAnnotationByNameFromClass($className, $name, $sources = 'class', $testName = '')
    {
        return TestUtil::getAnnotationByNameFromClass($className, $name, $sources, $testName);
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
     * @return $this
     */
    protected function replaceByMock($type, $classAlias, $mock)
    {
        TestUtil::replaceByMock($type, $classAlias, $mock);
        return $this;
    }

    /**
     * Replaces value in Magento system registry
     *
     * @param string $key
     * @param mixed $value
     * @return EcomDev_PHPUnit_Test_Case
     */
    protected function replaceRegistry($key, $value)
    {
        TestUtil::replaceRegistry($key, $value);
        return $this;
    }

    /**
     * Shortcut for expectation data object retrieval
     * Can be called with arguments array or in usual method
     *
     * @param string|array $firstArgument
     * @optional @param mixed $arg1
     * @optional @param mixed $arg2 ...
     * @return Varien_Object
     */
    protected function expected($firstArgument = null)
    {
        if ($firstArgument === 'auto' && $this->readAttribute($this, 'dataName')) {
            $arguments = $this->readAttribute($this, 'dataName');
        } elseif (!is_array($firstArgument)) {
            $arguments = func_get_args();
        } else {
            $arguments = $firstArgument;
        }

        return TestUtil::expected($this, $arguments);
    }

    /**
     * Retrieve mock builder for grouped class alias
     *
     * @param string $type block|model|helper
     * @param string $classAlias
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function getGroupedClassMockBuilder($type, $classAlias)
    {
        return TestUtil::getGroupedClassMockBuilder($this, $type, $classAlias);
    }

    /**
     * Retrieves a mock builder for a block class alias
     *
     * @param string $classAlias
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function getBlockMockBuilder($classAlias)
    {
        return $this->getGroupedClassMockBuilder('block', $classAlias);
    }

    /**
     * Retrieves a mock builder for a model class alias
     *
     * @param string $classAlias
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function getModelMockBuilder($classAlias)
    {
        return $this->getGroupedClassMockBuilder('model', $classAlias);
    }

    /**
     * Retrieves a mock builder for a resource model class alias
     *
     * @param string $classAlias
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function getResourceModelMockBuilder($classAlias)
    {
        return $this->getGroupedClassMockBuilder('resource_model', $classAlias);
    }

    /**
     * Retrieves a mock builder for a helper class alias
     *
     * @param string $classAlias
     * @return EcomDev_PHPUnit_Mock_Proxy
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
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function getModelMock($classAlias, $methods = array(), $isAbstract = false,
                                 array $constructorArguments = array(),
                                 $mockClassAlias = '',  $callOriginalConstructor = true,
                                 $callOriginalClone = true, $callAutoload = true)
    {
        return $this->getGroupedClassMock('model', $classAlias, $methods, $isAbstract,
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
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function getResourceModelMock($classAlias, $methods = array(), $isAbstract = false,
                                 array $constructorArguments = array(),
                                 $mockClassAlias = '',  $callOriginalConstructor = true,
                                 $callOriginalClone = true, $callAutoload = true)
    {
        return $this->getGroupedClassMock('resource_model', $classAlias, $methods, $isAbstract,
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
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function getHelperMock($classAlias, $methods = array(), $isAbstract = false,
                                 array $constructorArguments = array(),
                                 $mockClassAlias = '',  $callOriginalConstructor = true,
                                 $callOriginalClone = true, $callAutoload = true)
    {
        return $this->getGroupedClassMock('helper', $classAlias, $methods, $isAbstract,
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
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function getBlockMock($classAlias, $methods = array(), $isAbstract = false,
                                 array $constructorArguments = array(),
                                 $mockClassAlias = '',  $callOriginalConstructor = true,
                                 $callOriginalClone = true, $callAutoload = true)
    {
        return $this->getGroupedClassMock('block', $classAlias, $methods, $isAbstract,
                                   $constructorArguments, $mockClassAlias,
                                   $callOriginalConstructor, $callOriginalClone,
                                   $callAutoload);
    }

    /**
     * Returns class name by grouped class alias
     *
     * @param string $type block/model/helper/resource_model
     * @param string $classAlias
     * @return string
     */
    protected function getGroupedClassName($type, $classAlias)
    {
        return TestUtil::getGroupedClassName($type, $classAlias);
    }

    /**
     * Retrieves a mock object for the specified grouped class alias.
     *
     * @param  string  $type
     * @param  string  $classAlias
     * @param  array|null  $methods
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
        $mockBuilder->setConstructorArgs($constructorArguments);
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
    protected static function getFixture()
    {
        return TestUtil::getFixture(get_called_class());
    }

    /**
     * Returns expectation model singleton
     *
     * @return EcomDev_PHPUnit_Model_Expectation
     * @deprecated since 0.3.0
     */
    protected function getExpectation()
    {
        return TestUtil::getExpectation(get_class($this));
    }


    /**
     * Retrieves loadable class alias from annotation or configuration node
     * E.g. class alias for fixture model can be specified via @fixtureModel annotation
     *
     * @param string $type
     * @param string $configPath
     * @return string
     * @deprecated since 0.3.0
     */
    protected static function getLoadableClassAlias($type, $configPath)
    {
        return TestUtil::getLoadableClassAlias(get_called_class(), $type, $configPath);
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

        return TestUtil::getYamlFilePath(get_called_class(), $type, $name);
    }

    /**
     * Loads YAML file from directory inside of the unit test class or
     * the directory inside the module directory if name is prefixed with ~/
     * or from another module if name is prefixed with ~My_Module/
     *
     * @param string $className class name for looking fixture files
     * @param string $type type of YAML data (fixtures,expectations,dataproviders)
     * @param string $name the file name for loading
     * @return string|boolean
     * @deprecated since 0.3.0
     */
    public static function getYamlFilePathByClass($className, $type, $name)
    {
        return TestUtil::getYamlFilePath($className, $type, $name);
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
        return TestUtil::dataProvider(get_called_class(), $testName);
    }

    /**
     * Set current store scope for test
     *
     * @param int|string|Mage_Core_Model_Store $store
     * @return EcomDev_PHPUnit_Test_Case
     */
    public function setCurrentStore($store)
    {
        TestUtil::setCurrentStore($store);
        return $this;
    }

    /**
     * Calling of the helper method
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, array $args)
    {
        return TestUtil::call($method, $args);
    }
}
