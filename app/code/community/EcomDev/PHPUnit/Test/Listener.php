<?php

use PHPUnit\Framework\Test;

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

class EcomDev_PHPUnit_Test_Listener implements \PHPUnit\Framework\TestListener
{
    public const XML_PATH_UNIT_TEST_APP = 'phpunit/suite/app/class';

    /**
     * First level test suite that is used
     * for running all the tests
     *
     * @var \PHPUnit\Framework\TestSuite
     */
    protected $firstLevelTestSuite = null;

    /**
     * Returns app reflection instance
     *
     * @return ReflectionClass|ReflectionObject
     */
    protected function getAppReflection()
    {
        $appClass = (string) Mage::getConfig()->getNode(self::XML_PATH_UNIT_TEST_APP);
        $reflectionClass = EcomDev_Utils_Reflection::getReflection($appClass);

        return $reflectionClass;
    }

    /**
     * A test suite started.
     *
     * @param  \PHPUnit\Framework\TestSuite $suite
     */
    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        if ($this->firstLevelTestSuite === null) {
            Mage::dispatchEvent('phpunit_suite_start_before', array(
                'suite' => $suite,
                'listener' => $this
            ));

            // Apply app substitution for tests
            if ($this->getAppReflection()->hasMethod('applyTestScope')) {
                $this->getAppReflection()->getMethod('applyTestScope')->invoke(null);
            }



            $this->firstLevelTestSuite = $suite;
            Mage::dispatchEvent('phpunit_suite_start_after', array(
                'suite' => $suite,
                'listener' => $this
            ));
        }

        if (EcomDev_Utils_Reflection::getRestrictedPropertyValue($suite, 'testCase')) {
            Mage::dispatchEvent('phpunit_test_case_start_before', array(
                'suite' => $suite,
                'listener' => $this
            ));
            EcomDev_PHPUnit_Test_Case_Util::getFixture($suite->getName())
                ->setScope(EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED)
                ->loadForClass($suite->getName());

            $annotations = \PHPUnit\Util\Test::parseTestMethodAnnotations(
                $suite->getName()
            );

            EcomDev_PHPUnit_Test_Case_Util::getFixture($suite->getName())
                ->setOptions($annotations['class'])
                ->apply();
            Mage::dispatchEvent('phpunit_test_case_start_after', array(
                'suite' => $suite,
                'listener' => $this
            ));
        }
    }

    /**
     * A test suite ended.
     *
     * @param  \PHPUnit\Framework\TestSuite $suite
     */
    public function endTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        if (EcomDev_Utils_Reflection::getRestrictedPropertyValue($suite, 'testCase')) {
            Mage::dispatchEvent('phpunit_test_case_end_before', array(
                'suite' => $suite,
                'listener' => $this
            ));
            EcomDev_PHPUnit_Test_Case_Util::getFixture($suite->getName())
                ->setScope(EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED)
                ->discard();
            Mage::dispatchEvent('phpunit_test_case_end_after', array(
                'suite' => $suite,
                'listener' => $this
            ));
        }

        if ($this->firstLevelTestSuite === $suite) {
            Mage::dispatchEvent('phpunit_suite_end_before', array(
                'suite' => $suite,
                'listener' => $this
            ));
            $this->firstLevelTestSuite = null;
            // Discard test scope app
            if ($this->getAppReflection()->hasMethod('discardTestScope')) {
                $this->getAppReflection()->getMethod('discardTestScope')->invoke(null);
            }
            Mage::dispatchEvent('phpunit_suite_end_after', array(
                'suite' => $suite,
                'listener' => $this
            ));
        }
    }

    /**
     * A test started.
     *
     * @param  \PHPUnit\Framework\Test $test
     */
    public function startTest(\PHPUnit\Framework\Test $test): void
    {
        Mage::dispatchEvent('phpunit_test_start_before', array(
            'test' => $test,
            'listener' => $this
        ));
        if ($test instanceof \PHPUnit\Framework\TestCase) {
            EcomDev_PHPUnit_Helper::setTestCase($test);
            EcomDev_PHPUnit_Test_Case_Util::getFixture(get_class($test))
                ->setScope(EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_LOCAL)
                ->loadByTestCase($test);
            $annotations = $test->getAnnotations();
            EcomDev_PHPUnit_Test_Case_Util::getFixture()
                ->setOptions($annotations['method'])
                ->apply();

            EcomDev_PHPUnit_Test_Case_Util::setUp();
            EcomDev_PHPUnit_Helper::setUp();
        }
        Mage::dispatchEvent('phpunit_test_start_after', array(
            'test' => $test,
            'listener' => $this
        ));
    }

    /**
     * A test ended.
     *
     * @param  \PHPUnit\Framework\Test $test
     * @param  float                  $time
     */
    public function endTest(\PHPUnit\Framework\Test $test, $time): void
    {
        Mage::dispatchEvent('phpunit_test_end_before', array(
            'test' => $test,
            'listener' => $this
        ));

        if ($test instanceof \PHPUnit\Framework\TestCase) {
            EcomDev_PHPUnit_Helper::tearDown();
            EcomDev_PHPUnit_Test_Case_Util::tearDown();
            
            EcomDev_PHPUnit_Test_Case_Util::getFixture(get_class($test))
                ->setScope(EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_LOCAL)
                ->discard(); // Clear applied fixture

            if (EcomDev_PHPUnit_Test_Case_Util::getExpectation(get_class($test))->isLoaded()) {
                EcomDev_PHPUnit_Test_Case_Util::getExpectation(get_class($test))->discard();
            }
        }

        Mage::dispatchEvent('phpunit_test_end_after', array(
            'test' => $test,
            'listener' => $this
        ));
    }

    /**
     * An error occurred.
     *
     * @param  \PHPUnit\Framework\Test $test
     * @param  Exception              $e
     * @param  float                  $time
     */
    public function addError(\PHPUnit\Framework\Test $test, Throwable $t, float $time): void
    {
        Mage::dispatchEvent('phpunit_test_error', array(
            'test' => $test,
            'exception' => $t,
            'time' => $time,
            'listener' => $this
        ));
        // No action is required
    }

    /**
     * A failure occurred.
     *
     * @param  \PHPUnit\Framework\Test                 $test
     * @param  \PHPUnit\Framework\AssertionFailedError $e
     * @param  float                                  $time
     */
    public function addFailure(\PHPUnit\Framework\Test $test,\PHPUnit\Framework\AssertionFailedError $e, $time): void
    {
        Mage::dispatchEvent('phpunit_test_failure', array(
            'test' => $test,
            'exception' => $e,
            'time' => $time,
            'listener' => $this
        ));
        // No action is required
    }

    /**
     * Incomplete test.
     *
     * @param  \PHPUnit\Framework\Test $test
     * @param  Exception              $e
     * @param  float                  $time
     */
    public function addIncompleteTest(\PHPUnit\Framework\Test $test, Throwable $t, $time): void
    {
        Mage::dispatchEvent('phpunit_test_incomplete', array(
            'test' => $test,
            'exception' => $t,
            'time' => $time,
            'listener' => $this
        ));
        // No action is required
    }

    /**
     * Skipped test.
     *
     * @param  \PHPUnit\Framework\Test $test
     * @param  Exception              $e
     * @param  float                  $time
     */
    public function addSkippedTest(\PHPUnit\Framework\Test $test, Throwable $t, $time): void
    {
        Mage::dispatchEvent('phpunit_test_skipped', array(
            'test' => $test,
            'exception' => $t,
            'time' => $time,
            'listener' => $this
        ));
        // No action is required
    }


    /**
     * Risky test.
     *
     * @param \PHPUnit\Framework\Test $test
     * @param Exception $e
     * @param float $time
     * @since  Method available since Release 4.0.0
     */
    public function addRiskyTest(\PHPUnit\Framework\Test $test, Throwable $t, $time): void
    {
        Mage::dispatchEvent('phpunit_test_risky', array(
            'test' => $test,
            'exception' => $t,
            'time' => $time,
            'listener' => $this
        ));
        // No action required
    }

    public function addWarning(\PHPUnit\Framework\Test $test, \PHPUnit\Framework\Warning $e, float $time): void
    {
        Mage::dispatchEvent('phpunit_test_warning', array(
            'test' => $test,
            'exception' => $e,
            'time' => $time,
            'listener' => $this
        ));
    }
}
