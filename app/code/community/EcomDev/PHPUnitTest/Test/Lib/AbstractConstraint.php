<?php

class EcomDev_PHPUnitTest_Test_Lib_AbstractConstraint extends PHPUnit_Framework_TestCase
{
    /**
     * Test compare values functionality for constraint
     *
     * @param mixed $expectedValue
     * @param mixed $actualValue
     * @param bool $expectedResult
     *
     * @dataProvider dataProviderForCompareValues
     */
    public function testCompareValues($expectedValue, $actualValue, $expectedResult)
    {
        /**
         * @var $constraint EcomDev_PHPUnit_AbstractConstraint
         */
        $constraint = $this->getMockForAbstractClass('EcomDev_PHPUnit_AbstractConstraint', array(), '', false);
        $this->assertSame(
            $expectedResult,
            $constraint->compareValues($expectedValue, $actualValue)
        );

        if (!$expectedResult) {
            $this->assertAttributeInstanceOf('PHPUnit_Framework_ComparisonFailure', '_comparisonFailure', $constraint);
        }
    }

    /**
     * Data provider for checking compare values functionality
     *
     * @return array
     */
    public function dataProviderForCompareValues()
    {
        return array(
            array(
                array('value1', 'value2', 'value3'),
                array('value1', 'value2', 'value3'),
                true
            ),
            array(
                array('value1', 'value2', 'value3'),
                array('value1', 'value1', 'value3'),
                false
            ),

            array(
                array('value1', 0, 'value3'),
                array('value1', 'value1', 'value3'),
                false
            ),

            array(
                '1',
                1,
                true
            ),
            array(
                '0',
                0,
                true
            ),
            array(
                '1',
                0,
                false
            ),
            array(
                '',
                0,
                false
            )
        );
    }
}