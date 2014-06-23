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

class EcomDev_PHPUnitTest_Test_Lib_AbstractHelper extends PHPUnit_Framework_TestCase
{
    /**
     *
     *
     * @var EcomDev_PHPUnit_AbstractHelper|PHPUnit_Framework_MockObject_MockObject
     */
    protected $helper = null;

    protected function setUp()
    {
        $this->helper = $this->getMockBuilder('EcomDev_PHPUnit_AbstractHelper')
            ->setMethods(array('hasMethod', 'callMethod'))
            ->enableArgumentCloning()
            ->getMockForAbstractClass();
    }

    /**
     * Creates stub for phpunit method
     *
     * @param $map
     * @return $this
     */
    protected function hasMethodStub($map)
    {
        $stubMap = array();
        $stubResult = array();
        foreach ($map as $method => $result) {
            $stubMap[] = array($method, $result !== false);

            if ($result instanceof PHPUnit_Framework_MockObject_Stub) {
                $stubResult[$method] = $result;
            }
        }

        $this->helper->expects($this->any())
            ->method('hasMethod')
            ->will($this->returnValueMap($stubMap));

        $helper = $this->helper;
        $this->helper->expects($this->any())
            ->method('callMethod')
            ->will($this->returnCallback(function ($method, array $args) use ($helper, $stubResult) {
                $invocation = new PHPUnit_Framework_MockObject_Invocation_Object(
                    get_class($helper), $method, $args, $helper
                );
                return $stubResult[$method]->invoke($invocation);
            }));

        return $this;
    }

    public function testHasAction()
    {
        $this->hasMethodStub(array(
            'helperName' => true,
            'helperCamelName' => true,
            'helperUnknownName' => false
        ));

        $this->assertTrue($this->helper->has('name'));
        $this->assertTrue($this->helper->has('camelName'));
        $this->assertFalse($this->helper->has('unknownName'));
    }

    public function testInvokeAction()
    {
        $this->hasMethodStub(array(
            'helperName' => $this->returnArgument(0),
            'helperCamelName' => $this->returnArgument(1),
            'helperUnknownName' => false
        ));

        $this->assertSame('value1', $this->helper->invoke('name', array('value1', 'value2')));
        $this->assertSame('value2', $this->helper->invoke('camelName', array('value1', 'value2')));

        $this->setExpectedException('RuntimeException', 'Helper "unknownName" is not invokable.');
        $this->helper->invoke('unknownName', array());
    }

    public function testSetTestCase()
    {
        $this->assertObjectHasAttribute('testCase', $this->helper);
        $this->helper->setTestCase($this);
        $this->assertAttributeSame($this, 'testCase', $this->helper);
    }

}
