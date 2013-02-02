<?php

use EcomDev_PHPUnit_Helper as Helper;

class EcomDev_PHPUnitTest_Test_Lib_Helper extends PHPUnit_Framework_TestCase
{
    /**
     * Preserved array of already set helpers,
     * to return them back when test case finished its run
     *
     * @var EcomDev_PHPUnit_Helper_Interface[]
     */
    protected $initializedHelpers;


    protected function setUp()
    {
        // Retrieve existing helpers and store them for future revert
        $this->initializedHelpers = EcomDev_Utils_Reflection::getRestrictedPropertyValue(
            'EcomDev_PHPUnit_Helper',
            'helpers'
        );

        $this->resetHelpers();
    }

    /**
     * Resets helpers for test
     */
    protected function resetHelpers()
    {
        EcomDev_Utils_Reflection::setRestrictedPropertyValue('EcomDev_PHPUnit_Helper', 'helpers', array());
    }

    /**
     * Returns amount of helpers for test
     *
     * @param int $count
     * @param bool $setThem
     * @return EcomDev_PHPUnit_Helper_Interface[]|PHPUnit_Framework_MockObject_MockObject[]
     */
    protected function getHelpersForTest($count = 2, $setThem = false)
    {
        $result = array();

        for ($i = 0; $i < $count; $i ++) {
            $result[] = $this->getMockForAbstractClass(
                'EcomDev_PHPUnit_Helper_Interface', array(), 'Test_Helper_Name' . $i
            );
        }

        if ($setThem) {
            EcomDev_Utils_Reflection::setRestrictedPropertyValue('EcomDev_PHPUnit_Helper', 'helpers', $result);
        }

        return $result;
    }

    /**
     * Tests regular helper addition
     *
     */
    public function testAdd()
    {
        $helpers = $this->getHelpersForTest(3);

        Helper::add($helpers[0]);
        Helper::add($helpers[1]);
        Helper::add($helpers[2]);

        $this->assertAttributeSame($helpers, 'helpers', 'EcomDev_PHPUnit_Helper');
    }

    /**
     * Tests addition of helpers to static property
     *
     */
    public function testAddOrdered()
    {
        $helpers = $this->getHelpersForTest(6);

        Helper::add($helpers[0]);
        Helper::add($helpers[1]);
        Helper::add($helpers[2], array('before' => $helpers[1]));

        $this->assertAttributeSame(
            array($helpers[0], $helpers[2], $helpers[1]),
            'helpers', 'EcomDev_PHPUnit_Helper'
        );

        Helper::add($helpers[4], array('after' => $helpers[2]));

        $this->assertAttributeSame(
            array($helpers[0], $helpers[2], $helpers[4], $helpers[1]),
            'helpers', 'EcomDev_PHPUnit_Helper'
        );

        Helper::add($helpers[3], array('before' => 'Test_Helper_Name2'));

        $this->assertAttributeSame(
            array($helpers[0], $helpers[3], $helpers[2], $helpers[4], $helpers[1]),
            'helpers', 'EcomDev_PHPUnit_Helper'
        );

        Helper::add($helpers[5], array('after' => 'Test_Helper_Name3'));

        $this->assertAttributeSame(
            array($helpers[0], $helpers[3], $helpers[5], $helpers[2], $helpers[4], $helpers[1]),
            'helpers', 'EcomDev_PHPUnit_Helper'
        );
    }

    /**
     * Test wrong helper position
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Unknown position specified
     */
    public function testAddWrongPosition()
    {
        $helper = current($this->getHelpersForTest(1));
        Helper::add($helper, array('unknown' => 'position'));
    }

    /**
     * Tests removal of each helper
     */
    public function testRemove()
    {
        $helpers = $this->getHelpersForTest(5, true);
        // Check helpers are exists before editing
        $this->assertAttributeSame($helpers, 'helpers', 'EcomDev_PHPUnit_Helper');

        Helper::remove($helpers[1]);

        $this->assertAttributeSame(
            array($helpers[0], $helpers[2], $helpers[3], $helpers[4]),
            'helpers',
            'EcomDev_PHPUnit_Helper'
        );

        Helper::remove($helpers[0]);

        $this->assertAttributeSame(
            array($helpers[2], $helpers[3], $helpers[4]),
            'helpers',
            'EcomDev_PHPUnit_Helper'
        );

        Helper::remove($helpers[4]);

        $this->assertAttributeSame(
            array($helpers[2], $helpers[3]),
            'helpers',
            'EcomDev_PHPUnit_Helper'
        );

        Helper::remove($helpers[2]);
        Helper::remove($helpers[3]);

        $this->assertAttributeSame(
            array(),
            'helpers',
            'EcomDev_PHPUnit_Helper'
        );
    }

    /**
     * Tests removal of each helper
     *
     */
    public function testRemoveByClassName()
    {
        $helpers = $this->getHelpersForTest(5, true);
        // Check helpers are exists before editing
        $this->assertAttributeSame($helpers, 'helpers', 'EcomDev_PHPUnit_Helper');
        Helper::add($helpers[4]); // Added two times

        Helper::removeByClass('Test_Helper_Name2');

        $this->assertAttributeSame(
            array($helpers[0], $helpers[1], $helpers[3], $helpers[4], $helpers[4]),
            'helpers',
            'EcomDev_PHPUnit_Helper'
        );

        Helper::removeByClass('Test_Helper_Name4');

        $this->assertAttributeSame(
            array($helpers[0], $helpers[1], $helpers[3]),
            'helpers',
            'EcomDev_PHPUnit_Helper'
        );
    }

    /**
     * Tests getting of helper by action
     *
     */
    public function testGetByAction()
    {
        $helpers = $this->getHelpersForTest(3, true);

        // Should be used for firstName
        $helpers[0]->expects($this->any())
            ->method('has')
            ->will($this->returnValueMap(array(
                array('firstName', true),
                array('secondName', false),
                array('thirdName', false),
            )));

        // Should be used for thirdName
        $helpers[1]->expects($this->any())
            ->method('has')
            ->will($this->returnValueMap(array(
                array('firstName', false),
                array('secondName', false),
                array('thirdName', true),
            )));

        // Should be used for secondName
        $helpers[2]->expects($this->any())
            ->method('has')
            ->will($this->returnValueMap(array(
                array('firstName', false),
                array('secondName', true),
                array('thirdName', true),
            )));

        $this->assertSame($helpers[0], Helper::getByAction('firstName'));
        $this->assertSame($helpers[1], Helper::getByAction('thirdName'));
        $this->assertSame($helpers[2], Helper::getByAction('secondName'));
        $this->assertSame(false, Helper::getByAction('uknownName'));
    }


    /**
     * Creates invoke method tests stub
     *
     * @return EcomDev_PHPUnit_Helper_Interface[]|PHPUnit_Framework_MockObject_MockObject[]
     */
    protected function invokeStub()
    {
        $helpers = $this->getHelpersForTest(1, true);

        // Should be used for firstName
        $helpers[0]->expects($this->any())
            ->method('has')
            ->will($this->returnValueMap(array(
                array('firstName', true)
            )));

        // Invocation stub for firstName
        $helpers[0]->expects($this->any())
            ->method('invoke')
            ->will($this->returnValueMap(array(
                array('firstName', array('one'), 'firstName_one'),
                array('firstName', array('one', 'two'), 'firstName_one_two'),
                array('firstName', array('one', 'two', 'three'), 'firstName_one_two_three')
            )));
        return $helpers;
    }

    /**
     * Tests invoking of helper by action
     *
     */
    public function testInvokeArgs()
    {
        $this->invokeStub();

        $this->assertSame('firstName_one', Helper::invokeArgs('firstName', array('one')));
        $this->assertSame('firstName_one_two', Helper::invokeArgs('firstName', array('one', 'two')));
        $this->assertSame('firstName_one_two_three', Helper::invokeArgs('firstName', array('one', 'two', 'three')));

        $this->setExpectedException('RuntimeException', 'Cannot find a helper for action "unknownName"');
        Helper::invokeArgs('unknownName', array('argument'));
    }

    /**
     * Tests invoking of helper by action
     *
     */
    public function testInvoke()
    {
        $this->invokeStub();

        $this->assertSame('firstName_one', Helper::invoke('firstName', 'one'));
        $this->assertSame('firstName_one_two', Helper::invoke('firstName', 'one', 'two'));
        $this->assertSame('firstName_one_two_three', Helper::invoke('firstName', 'one', 'two', 'three'));
    }

    /**
     * Tests method for checking action existence in the helper
     *
     */
    public function testHas()
    {
        $helpers = $this->getHelpersForTest(1, true);

        // Should be used for firstName
        $helpers[0]->expects($this->any())
            ->method('has')
            ->will($this->returnValueMap(array(
                array('firstName', true),
                array('secondName', true)
            )));

        $this->assertTrue(Helper::has('firstName'));
        $this->assertTrue(Helper::has('secondName'));
        $this->assertFalse(Helper::has('unknownName'));
    }

    /**
     * Test that setTestCase method was correctly invoked
     *
     */
    public function testSetTestCase()
    {
        $helpers = $this->getHelpersForTest(4, true);

        // Initialize mock for test
        foreach ($helpers as $helper) {
            $helper->expects($this->once())
                ->method('setTestCase')
                ->with(new PHPUnit_Framework_Constraint_IsIdentical($this))
                ->will($this->returnSelf());
        }

        EcomDev_PHPUnit_Helper::setTestCase($this);
    }

    protected function tearDown()
    {
        // Revert helpers in helper class
        EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            'EcomDev_PHPUnit_Helper',
            'helpers',
            $this->initializedHelpers
        );
    }
}