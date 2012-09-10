<?php

class EcomDev_PHPUnit_Test_Listener implements PHPUnit_Framework_TestListener
{
    const XML_PATH_UNIT_TEST_APP = 'phpunit/suite/app/class';

    /**
     * First level test suite that is used
     * for running all the tests
     *
     * @var PHPUnit_Framework_TestSuite
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
        $reflectionClass = EcomDev_Utils_Reflection::getRelflection($appClass);

        return $reflectionClass;
    }

    /**
     * A test suite started.
     *
     * @param  PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if ($this->firstLevelTestSuite === null) {
            // Apply app substitution for tests

            if ($this->getAppReflection()->hasMethod('applyTestScope')) {
                $this->getAppReflection()->getMethod('applyTestScope')->invoke(null);
            }

            $this->firstLevelTestSuite = $suite;
        }

        if (EcomDev_Utils_Reflection::getRestrictedPropertyValue($suite, 'testCase')) {

        }
    }

    /**
     * A test suite ended.
     *
     * @param  PHPUnit_Framework_TestSuite $suite
     */
    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if ($this->firstLevelTestSuite === $suite) {
            $this->firstLevelTestSuite = null;
            // Discard test scope app
            if ($this->getAppReflection()->hasMethod('discardTestScope')) {
                $this->getAppReflection()->getMethod('discardTestScope')->invoke(null);
            }
        }
    }

    /**
     * A test started.
     *
     * @param  PHPUnit_Framework_Test $test
     */
    public function startTest(PHPUnit_Framework_Test $test)
    {
        // No action is required for now
    }

    /**
     * A test ended.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  float                  $time
     */
    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        // No action is required for now
    }

    /**
     * An error occurred.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception              $e
     * @param  float                  $time
     */
    public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        // No action is required
    }

    /**
     * A failure occurred.
     *
     * @param  PHPUnit_Framework_Test                 $test
     * @param  PHPUnit_Framework_AssertionFailedError $e
     * @param  float                                  $time
     */
    public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
    {
        // No action is required
    }

    /**
     * Incomplete test.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception              $e
     * @param  float                  $time
     */
    public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        // No action is required
    }

    /**
     * Skipped test.
     *
     * @param  PHPUnit_Framework_Test $test
     * @param  Exception              $e
     * @param  float                  $time
     *
     * @since  Method available since Release 3.0.0
     */
    public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
    {
        // No action is required
    }

}