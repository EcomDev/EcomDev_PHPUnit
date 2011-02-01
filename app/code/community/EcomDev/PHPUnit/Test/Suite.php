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

/**
 * Test suite for Magento
 *
 * It discovers all test cases in modules
 * if they were added to 'phpunit/suite/modules' configuration node
 *
 */
class EcomDev_PHPUnit_Test_Suite extends PHPUnit_Framework_TestSuite
{
    // Configuration path constants
    const XML_PATH_UNIT_TEST_GROUPS = 'phpunit/suite/groups';
    const XML_PATH_UNIT_TEST_MODULES = 'phpunit/suite/modules';
    const XML_PATH_UNIT_TEST_APP = 'phpunit/suite/app';

    /**
     * Setting up test scope for Magento
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestSuite::setUp()
     */
    protected function setUp()
    {
        $appClass = (string) Mage::getConfig()->getNode(self::XML_PATH_UNIT_TEST_APP);
        $reflectionClass = EcomDev_Utils_Reflection::getRelflection($appClass);

        if ($reflectionClass->hasMethod('applyTestScope')) {
            $reflectionClass->getMethod('applyTestScope')->invoke(null);
        }
    }

    /**
     * Returning Magento to the state before suite was run
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestSuite::tearDown()
     */
    protected function tearDown()
    {
        $appClass = (string) Mage::getConfig()->getNode(self::XML_PATH_UNIT_TEST_APP);
        $reflectionClass = EcomDev_Utils_Reflection::getRelflection($appClass);

        if ($reflectionClass->hasMethod('discardTestScope')) {
            $reflectionClass->getMethod('discardTestScope')->invoke(null);
        }
    }

    /**
     * This method loads all available test suites for PHPUnit
     *
     * @return PHPUnit_Framework_TestSuite
     */
    public static function suite()
    {
        $groups = Mage::getConfig()->getNode(self::XML_PATH_UNIT_TEST_GROUPS);
        $modules = Mage::getConfig()->getNode(self::XML_PATH_UNIT_TEST_MODULES);
        $suite = new self('Magento Test Suite');
        // Walk through different groups in modules for finding test cases
        foreach ($groups->children() as $group) {
            foreach ($modules->children() as $module) {
                $realModule = Mage::getConfig()->getNode('modules/' . $module->getName());
                if (!$realModule || !$realModule->is('active')) {
                    $suite->addTest(self::warning('There is no module with name: ' . $module->getName()));
                    continue;
                }

                $moduleCodeDir = Mage::getBaseDir('code') . DS . (string) $realModule->codePool;
                $searchPath = Mage::getModuleDir('', $module->getName()) . DS . 'Test' . DS . (string) $group;

                if (!is_dir($searchPath)) {
                    continue;
                }

                $directoryIterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($searchPath)
                );

                $currentGroups = array(
                    $group->getName(),
                    $module->getName()
                );

                foreach ($directoryIterator as $fileObject) {
                    /* @var $fileObject SplFileObject */
                    // Skip entry if it is not a php file
                    if (!$fileObject->isFile() || $fileObject->getBasename('.php') === $fileObject->getBasename()) {
                        continue;
                    }


                    $classPath = substr($fileObject->getPath() . DS . $fileObject->getBasename('.php'), strlen($moduleCodeDir));
                    $className = uc_words(ltrim($classPath, DS), '_', DS);

                    // Add unit test case only
                    // if it is a valid class extended from EcomDev_PHPUnit_Test_Case
                    if (class_exists($className, true)) {

                        $reflectionClass = EcomDev_Utils_Reflection::getRelflection($className);
                        if (!$reflectionClass->isSubclassOf('EcomDev_PHPUnit_Test_Case')) {
                            continue;
                        }

                        $suite->addTest(new PHPUnit_Framework_TestSuite($reflectionClass), $currentGroups);
                    }
                }

            }
        }

        if (!$suite->count()) {
            $suite->addTest(self::warning('There were no test cases for the current run'));
        }

        return $suite;
    }
}
