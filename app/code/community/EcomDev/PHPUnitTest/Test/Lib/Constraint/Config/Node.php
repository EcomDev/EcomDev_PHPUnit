<?php

class EcomDev_PHPUnitTest_Test_Lib_Constraint_Config_Node extends EcomDev_PHPUnit_Test_Case
{

    /**
     * Creates constraint instance
     *
     * @param $nodePath
     * @param $type
     * @param $value
     *
     * @return EcomDev_PHPUnit_Constraint_Config_Node
     */
    protected function _getConstraint($nodePath, $type, $value)
    {
        return new EcomDev_PHPUnit_Constraint_Config_Node($nodePath, $type, $value);
    }

    /**
     * Test constructor of the node,
     *
     * @param mixed $expectedValue
     * @param string $type
     *
     * @dataProvider dataProvider
     */
    public function testConstructorAccepts($expectedValue, $type)
    {
        $constraint = $this->_getConstraint('some/dummy/path', $type, $expectedValue);
        $this->assertAttributeEquals($expectedValue, '_expectedValue', $constraint);
    }

    /**
     * Tests that particular value equals xml
     *
     * @param string $actualValue
     * @param string $expectedValue
     * @dataProvider dataProvider
     */
    public function testEqualsXml($actualValue, $expectedValue)
    {
        $actualValue = new SimpleXMLElement($actualValue);
        $expectedValue = new SimpleXMLElement($expectedValue);

        $constraint = $this->_getConstraint(
            'some/dummy/path',
            EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_XML,
            $expectedValue
        );

        $this->assertTrue($constraint->evaluate($actualValue, '', true));
        $this->assertAttributeEmpty('_comparisonFailure', $constraint);
    }

    /**
     * Tests that particular value equals xml
     *
     * @param string $actualValue
     * @param string $expectedValue
     * @dataProvider dataProvider
     */
    public function testEqualsXmlFailure($actualValue, $expectedValue)
    {
        $actualValue = new SimpleXMLElement($actualValue);
        $expectedValue = new SimpleXMLElement($expectedValue);

        $constraint = $this->_getConstraint(
            'some/dummy/path',
            EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_XML,
            $expectedValue
        );

        $this->assertFalse($constraint->evaluate($actualValue, '', true));
        $this->assertAttributeNotEmpty('_comparisonFailure', $constraint);
        $this->assertAttributeInstanceOf('\SebastianBergmann\Comparator\ComparisonFailure', '_comparisonFailure', $constraint);
    }
}