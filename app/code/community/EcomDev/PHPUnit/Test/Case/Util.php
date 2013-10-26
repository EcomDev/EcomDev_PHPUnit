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

use EcomDev_PHPUnit_Helper as TestHelper;

class EcomDev_PHPUnit_Test_Case_Util
{
    const XML_PATH_DEFAULT_FIXTURE_MODEL = 'phpunit/suite/fixture/model';
    const XML_PATH_DEFAULT_EXPECTATION_MODEL = 'phpunit/suite/expectation/model';
    const XML_PATH_DEFAULT_YAML_LOADER_MODEL = 'phpunit/suite/yaml/model';

    /**
     * List of replaced registry keys for current test case run
     *
     * @var array
     */
    protected static $replacedRegistry = array();

    /**
     * Original store before first setCurrentStore() call
     *
     * @var int|string|Mage_Core_Model_Store|null
     */
    protected static $originalStore = null;

    /**
     * Current expectation model alias
     *
     * @var string
     */
    protected static $expectationModelAlias = null;

    /**
     * Current fixture model alias
     *
     * @var string
     */
    protected static $fixtureModelAlias = null;

    /**
     * Current yaml loader model alias
     *
     * @var string
     */
    protected static $yamlLoaderModelAlias = null;

    /**
     * Module name by class name
     *
     * @var string[]
     */
    protected static $moduleNameByClassName = array();


    /**
     * Returns app for test case, created for type hinting
     * in the test case code
     *
     * @return EcomDev_PHPUnit_Model_App
     */
    public static function app()
    {
        return Mage::app();
    }

    /**
     * Returns yaml loader model instance
     *
     * @param string|null $testCaseClass
     * @return EcomDev_PHPUnit_Model_Yaml_Loader
     */
    public static function getYamlLoader($testCaseClass = null)
    {
        if ($testCaseClass !== null) {
            self::$yamlLoaderModelAlias = self::getLoadableClassAlias(
                $testCaseClass,
                'yaml',
                self::XML_PATH_DEFAULT_YAML_LOADER_MODEL
            );
        } elseif (self::$yamlLoaderModelAlias === null) {
            self::$yamlLoaderModelAlias = self::getLoadableClassAlias(
                get_called_class(), // Just fallback to current test util
                'yaml',
                self::XML_PATH_DEFAULT_YAML_LOADER_MODEL
            );
        }

        return Mage::getSingleton(self::$yamlLoaderModelAlias);
    }

    /**
     * Loads YAML file based on loaders logic
     *
     * @param string $className class name for looking fixture files
     * @param string $type type of YAML data (fixtures,expectations,providers)
     * @param string $name the file name for loading
     * @return string|boolean
     */
    public static function getYamlFilePath($className, $type, $name)
    {
        return self::getYamlLoader($className)->resolveFilePath($className, $type, $name);
    }


    /**
     * Retrieves fixture model singleton
     *
     * @param string|null $testCaseClass
     * @return EcomDev_PHPUnit_Model_Fixture
     * @throws RuntimeException
     */
    public static function getFixture($testCaseClass = null)
    {
        if ($testCaseClass !== null) {
            self::$fixtureModelAlias = self::getLoadableClassAlias(
                $testCaseClass,
                'fixture',
                self::XML_PATH_DEFAULT_FIXTURE_MODEL
            );
        } elseif (self::$fixtureModelAlias === null) {
            self::$fixtureModelAlias = self::getLoadableClassAlias(
                get_called_class(), // Just fallback to current test util
                'fixture',
                self::XML_PATH_DEFAULT_FIXTURE_MODEL
            );
        }

        $fixture = Mage::getSingleton(self::$fixtureModelAlias);

        if (!$fixture instanceof EcomDev_PHPUnit_Model_FixtureInterface) {
            throw new RuntimeException('Fixture model should implement EcomDev_PHPUnit_Model_FixtureInterface interface');
        }

        $storage = Mage::registry(EcomDev_PHPUnit_Model_App::REGISTRY_PATH_SHARED_STORAGE);

        if (!$storage instanceof Varien_Object) {
            throw new RuntimeException('Fixture storage object was not initialized during test application setup');
        }

        $fixture->setStorage(
            Mage::registry(EcomDev_PHPUnit_Model_App::REGISTRY_PATH_SHARED_STORAGE)
        );

        return $fixture;
    }

    /**
     * Returns expectation model singleton
     *
     * @param string|null $testCaseClass
     * @return EcomDev_PHPUnit_Model_Expectation
     */
    public static function getExpectation($testCaseClass = null)
    {
        if ($testCaseClass !== null) {
            self::$expectationModelAlias = self::getLoadableClassAlias(
                $testCaseClass,
                'expectation',
                self::XML_PATH_DEFAULT_EXPECTATION_MODEL
            );
        } elseif (self::$expectationModelAlias === null) {
            self::$expectationModelAlias = self::getLoadableClassAlias(
                get_called_class(), // Just fallback to current test util
                'expectation',
                self::XML_PATH_DEFAULT_EXPECTATION_MODEL
            );
        }

        return Mage::getSingleton(self::$expectationModelAlias);
    }

    /**
     * Shortcut for expectation data object retrieval
     * Can be called with arguments array or in usual method
     *
     * @param PHPUnit_Framework_TestCase $testCase
     * @param string|array $firstArgument
     * @optional @param mixed $arg1
     * @optional @param mixed $arg2
     * @return Varien_Object
     */
    public static function expected(PHPUnit_Framework_TestCase $testCase, $firstArgument = null)
    {
        if (!self::getExpectation(get_class($testCase))->isLoaded()) {
            self::getExpectation()->loadByTestCase($testCase);
            self::getExpectation()->apply();
        }

        if (!is_array($firstArgument)) {
            $arguments = func_get_args();
            array_shift($arguments); // Remove test case from arguments
        } else {
            $arguments = $firstArgument;
        }

        $pathFormat = null;
        if ($arguments) {
            $pathFormat = array_shift($arguments);
        }

        return self::getExpectation()
            ->getDataObject($pathFormat, $arguments);
    }

    /**
     * Loads data provider based on test class name and test name
     *
     * @param string $className
     * @param string $testName
     *
     * @return array
     * @throws RuntimeException
     */
    public static function dataProvider($className, $testName)
    {
        $dataProviderFiles = array();

        if ($annotations = self::getAnnotationByNameFromClass($className, 'dataProviderFile', array('class', 'method'), $testName)) {
            foreach ($annotations as $name) {
                $filePath = self::getYamlLoader($className)
                    ->resolveFilePath($className, EcomDev_PHPUnit_Model_Yaml_Loader::TYPE_PROVIDER, $name);
                if (!$filePath) {
                    throw new RuntimeException(sprintf('Unable to load data provider for path %s', $name));
                }
                $dataProviderFiles[] = $filePath;
            }
        } else {
            $filePath = self::getYamlLoader($className)
                ->resolveFilePath($className, EcomDev_PHPUnit_Model_Yaml_Loader::TYPE_PROVIDER, $testName);

            if (!$filePath) {
                throw new RuntimeException('Unable to load data provider for the current test');
            }

            $dataProviderFiles[] = $filePath;
        }


        $providerData = array();
        foreach ($dataProviderFiles as $file) {
            $providerData = array_merge_recursive($providerData, self::getYamlLoader()->load($file));
        }
        return $providerData;
    }


    /**
     * Retrieves loadable class alias from annotation or configuration node
     * E.g. class alias for fixture model can be specified via @fixtureModel annotation
     *
     * @param string $className
     * @param string $type
     * @param string $configPath
     * @return string
     */
    public static function getLoadableClassAlias($className, $type, $configPath)
    {
        $annotationValue = self::getAnnotationByNameFromClass(
            $className,
            $type .'Model'
        );

        if (current($annotationValue)) {
            $classAlias = current($annotationValue);
        } else {
            $classAlias = (string) self::app()->getConfig()->getNode($configPath);
        }

        return $classAlias;
    }

    /**
     * Retrieves the module name for current test case
     *
     * @param PHPUnit_Framework_TestCase $testCase
     * @return string
     * @throws RuntimeException if module name was not found for the passed class name
     */
    public static function getModuleName(PHPUnit_Framework_TestCase $testCase)
    {
        return self::getModuleNameByClassName($testCase);
    }

    /**
     * Retrieves annotation by its name from different sources (class, method) based on meta information
     *
     * @param string $className
     * @param string $name annotation name
     * @param array|string $sources
     * @param string $testName test method name
     * @return array
     */
    public static function getAnnotationByNameFromClass($className, $name, $sources = 'class', $testName = '')
    {
        if (is_string($sources)) {
            $sources = array($sources);
        }

        $allAnnotations =  PHPUnit_Util_Test::parseTestMethodAnnotations(
            $className, $testName
        );

        $annotation = array();

        // Iterate over sources for annotation retrieval
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
     * Retrieves module name from call stack objects
     *
     * @return string
     * @throws RuntimeException if assertion is called in not from EcomDev_PHPUnit_Test_Case
     */
    public static function getModuleNameFromCallStack()
    {
        $backTrace = debug_backtrace(true);
        foreach ($backTrace as $call) {
            if (isset($call['object']) && $call['object'] instanceof PHPUnit_Framework_TestCase) {
                return self::getModuleName($call['object']);
            }
        }

        throw new RuntimeException('Unable to retrieve module name from call stack, because assertion is not called from PHPUnit_Framework_Test_Case based class method');
    }

    /**
     * Set current store scope for test
     *
     * @param int|string|Mage_Core_Model_Store $store
     * @return void
     */
    public static function setCurrentStore($store)
    {
        if (!self::$originalStore) {
            self::$originalStore = self::app()->getStore();
        }

        self::app()->setCurrentStore(
            self::app()->getStore($store)
        );
    }

    /**
     * Set associated module name for a class name,
     * Usually used for making possible dependency injection in the test cases
     *
     *
     * @param string $className
     * @param string $moduleName
     * @return array
     */
    public static function setModuleNameForClassName($className, $moduleName)
    {
        self::$moduleNameByClassName[$className] = $moduleName;
    }

    /**
     * Returns module name for a particular object
     *
     * @param string|object $className
     * @throws RuntimeException if module name was not found for the passed class name
     * @return string
     */
    public static function getModuleNameByClassName($className)
    {
        if (is_object($className)) {
            $className = get_class($className);
        }

        if (!isset(self::$moduleNameByClassName[$className])) {
            // Try to find the module name by class name
            $moduleName = false;
            foreach (Mage::getConfig()->getNode('modules')->children() as $module) {
                if (strpos($className, $module->getName()) === 0) {
                    $moduleName = $module->getName();
                    break;
                }
            }

            if (!$moduleName) {
                throw new RuntimeException('Cannot to find the module name for class name: ' . $className);
            }

            self::setModuleNameForClassName($className, $moduleName);
        }

        return self::$moduleNameByClassName[$className];
    }

    /**
     * Replaces Magento resource by mock object
     *
     * @param string $type
     * @param string $classAlias
     * @param PHPUnit_Framework_MockObject_MockObject|PHPUnit_Framework_MockObject_MockBuilder $mock
     * @throws PHPUnit_Framework_Exception
     * @return void
     */
    public static function replaceByMock($type, $classAlias, $mock)
    {
        if ($mock instanceof EcomDev_PHPUnit_Mock_Proxy) {
            $mock = $mock->getMockInstance();
        } elseif ($mock instanceof PHPUnit_Framework_MockObject_MockBuilder) {
            $mock = $mock->getMock();
        } elseif (!$mock instanceof PHPUnit_Framework_MockObject_MockObject) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1, 'PHPUnit_Framework_MockObject_MockObject'
            );
        }

        // Remove addition of /data suffix if version is more than 1.6.x
        if (version_compare(Mage::getVersion(), '1.6.0.0', '<') && $type == 'helper' && strpos($classAlias, '/') === false) {
            $classAlias .= '/data';
        }

        if (in_array($type, array('model', 'resource_model'))) {
            self::app()->getConfig()->replaceInstanceCreation($type, $classAlias, $mock);
            $type = str_replace('model', 'singleton', $type);
        } elseif ($type == 'block') {
            self::app()->getLayout()->replaceBlockCreation($classAlias, $mock);
        }

        if (in_array($type, array('singleton', 'resource_singleton', 'helper'))) {
            $registryPath = '_' . $type . '/' . $classAlias;
            self::replaceRegistry($registryPath, $mock);
        }
    }

    /**
     * Replaces value in Magento system registry
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function replaceRegistry($key, $value)
    {
        $oldValue = Mage::registry($key);
        self::app()->replaceRegistry($key, $value);
        self::$replacedRegistry[$key] = $oldValue;
    }

    /**
     * Returns class name by grouped class alias
     *
     * @param string $type block/model/helper/resource_model
     * @param string $classAlias
     * @return string
     */
    public static function getGroupedClassName($type, $classAlias)
    {
        if ($type === 'resource_model') {
            return self::app()->getConfig()->getResourceModelClassName($classAlias);
        } elseif ($type === 'helper') {
            return self::app()->getConfig()->getHelperClassName($classAlias);
        }

        return self::app()->getConfig()->getGroupedClassName($type, $classAlias);
    }

    /**
     * Retrieve mock builder for grouped class alias
     *
     * @param PHPUnit_Framework_TestCase $testCase
     * @param string                     $type block|model|helper
     * @param string                     $classAlias
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public static function getGroupedClassMockBuilder(PHPUnit_Framework_TestCase $testCase, $type, $classAlias)
    {
        $className = self::getGroupedClassName($type, $classAlias);
        return new EcomDev_PHPUnit_Mock_Proxy($testCase, $className);
    }

    /**
     * Called for each test case
     *
     */
    public static function setUp()
    {
        self::app()->resetDispatchedEvents();
    }

    /**
     * Called for each test case
     *
     */
    public static function tearDown()
    {
        if (self::$originalStore) {
            self::app()->setCurrentStore(self::$originalStore);
            self::$originalStore = null;
        }

        self::app()->getConfig()->flushReplaceInstanceCreation();
        self::app()->getLayout()->flushReplaceBlockCreation();
        foreach (self::$replacedRegistry as $registryPath => $originalValue) {
            self::app()->replaceRegistry($registryPath, $originalValue);
        }
    }

    /**
     * Implementation of __call method functionality that can be used from a test case
     *
     * @param string                     $method
     * @param array                      $args
     *
     * @throws ErrorException
     * @return bool
     */
    public static function call($method, $args)
    {
        if (TestHelper::has($method)) {
            return TestHelper::invokeArgs($method, $args);
        }

        if (version_compare(PHP_VERSION, '5.4', '>=')) {
            $backTraceCalls = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        } else {
            // We cannot limit number of arguments on php before 5.4, php rises an exception :(
            $backTraceCalls = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
        }

        $previousCall = $backTraceCalls[2];

        throw new ErrorException(
            sprintf(
                'Call to undefined function %s%s%s()',
                $previousCall['class'],
                $previousCall['type'],
                $previousCall['function']
            ),
            0,
            E_USER_ERROR,
            $previousCall['file'],
            $previousCall['line']
        );
    }
}
