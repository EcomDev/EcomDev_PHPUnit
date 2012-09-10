<?php

class EcomDev_PHPUnit_Test_Case_Util
{
    const XML_PATH_DEFAULT_FIXTURE_MODEL = 'phpunit/suite/fixture/model';
    const XML_PATH_DEFAULT_EXPECTATION_MODEL = 'phpunit/suite/expectation/model';

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
     * Retrieves the module name for current test case
     *
     * @param PHPUnit_Framework_TestCase $testCase
     * @return string
     * @throws RuntimeException if module name was not found for the passed class name
     */
    public static function getModuleName(PHPUnit_Framework_TestCase $testCase)
    {
        return self::app()->getModuleNameByClassName($testCase);
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

        throw new RuntimeException('Unable to retrieve module name from call stack, because assertion is not called from EcomDev_PHPUnit_Test_Case based class method');
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

        // Walk-through sources for annotation retrieval
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
     * Retrieves loadable class alias from annotation or configuration node
     * E.g. class alias for fixture model can be specified via @fixtureModel annotation
     *
     * @param string $className
     * @param string $type
     * @param string $configPath
     * @return string
     */
    protected static function getLoadableClassAlias($className, $type, $configPath)
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
     * Returns expectation model singleton
     *
     * @param string $testCaseClassName
     * @return EcomDev_PHPUnit_Model_Expectation
     */
    public static function getExpectation($testCaseClassName)
    {
        return Mage::getSingleton(
            self::getLoadableClassAlias(
                $testCaseClassName,
                'expectation',
                self::XML_PATH_DEFAULT_EXPECTATION_MODEL
            )
        );
    }

    /**
     *
     * @param PHPUnit_Framework_TestCase $testCase
     *
     */
    public static function loadExpectation(PHPUnit_Framework_TestCase $testCase)
    {

    }


    /**
     * Retrieves fixture model singleton
     *
     * @param string $testCaseClassName
     * @return EcomDev_PHPUnit_Model_Fixture
     * @throws RuntimeException
     */
    public static function getFixture($testCaseClassName)
    {
        $fixture = Mage::getSingleton(
            self::getLoadableClassAlias(
                $testCaseClassName,
                'fixture',
                self::XML_PATH_DEFAULT_FIXTURE_MODEL
            )
        );

        if (!$fixture instanceof EcomDev_PHPUnit_Model_Fixture_Interface) {
            throw new RuntimeException('Fixture model should implement EcomDev_PHPUnit_Model_Fixture_Interface interface');
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
     * Loads YAML file from directory inside of the unit test class or
     * the directory inside the module directory if name is prefixed with ~/
     * or from another module if name is prefixed with ~My_Module/
     *
     * @param string $className class name for looking fixture files
     * @param string $type type of YAML data (fixtures,expectations,dataproviders)
     * @param string $name the file name for loading
     * @return string|boolean
     */
    public static function getYamlFilePath($className, $type, $name)
    {
        if (strrpos($name, '.yaml') !== strlen($name) - 5) {
            $name .= '.yaml';
        }

        $classFileObject = new SplFileInfo(
            EcomDev_Utils_Reflection::getRelflection($className)->getFileName()
        );

        // When prefixed with ~/ or ~My_Module/, load from the module's Test/<type> directory
        if (preg_match('#^~(?<module>[^/]*)/(?<path>.*)$#', $name, $matches)) {
            $name = $matches['path'];
            if( ! empty($matches['module'])) {
                $moduleName = $matches['module'];
            } else {
                $moduleName = substr($className, 0, strpos($className, '_Test_'));;
            }
            $filePath = Mage::getModuleDir('', $moduleName) . DS . 'Test' . DS;
        }
        // Otherwise load from the Class/<type> directory
        else {
            $filePath = $classFileObject->getPath() . DS
                . $classFileObject->getBasename('.php') . DS;
        }
        $filePath .= $type . DS . $name;

        if (file_exists($filePath)) {
            return $filePath;
        }

        return false;
    }



}