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

class EcomDev_PHPUnitTest_Test_Helper_Mock extends EcomDev_PHPUnit_Test_Case
{
    public function testMockClassAlias()
    {
        $mock = $this->mockClassAlias('model', 'catalog/product',
            array('getId'),
            array(array('entity_id' => 1))
        );

        $this->assertInstanceOf('EcomDev_PHPUnit_Mock_Proxy', $mock);
        $this->assertAttributeEquals($this->getGroupedClassName('model', 'catalog/product'), 'type', $mock);
        $this->assertAttributeContains('getId', 'methods', $mock);
        $this->assertAttributeContains(array('entity_id' => 1), 'constructorArgs', $mock);
    }

    public function testModelMock()
    {
        $mock = $this->mockModel('catalog/product',
            array('getId'),
            array(array('entity_id' => 1))
        );

        $this->assertInstanceOf('EcomDev_PHPUnit_Mock_Proxy', $mock);
        $this->assertAttributeEquals($this->getGroupedClassName('model', 'catalog/product'), 'type', $mock);
        $this->assertAttributeContains('getId', 'methods', $mock);
        $this->assertAttributeContains(array('entity_id' => 1), 'constructorArgs', $mock);
    }

    public function testBlockMock()
    {
        $mock = $this->mockBlock('catalog/product_view',
            array('getTemplate'),
            array(array('product_id' => 1))
        );

        $this->assertInstanceOf('EcomDev_PHPUnit_Mock_Proxy', $mock);
        $this->assertAttributeEquals($this->getGroupedClassName('block', 'catalog/product_view'), 'type', $mock);
        $this->assertAttributeContains('getTemplate', 'methods', $mock);
        $this->assertAttributeContains(array('product_id' => 1), 'constructorArgs', $mock);
    }

    public function testHelperMock()
    {
        $mock = $this->mockBlock('catalog/category',
            array('getStoreCategories'),
            array('some_value')
        );

        $this->assertInstanceOf('EcomDev_PHPUnit_Mock_Proxy', $mock);
        $this->assertAttributeEquals($this->getGroupedClassName('block', 'catalog/category'), 'type', $mock);
        $this->assertAttributeContains('getStoreCategories', 'methods', $mock);
        $this->assertAttributeContains('some_value', 'constructorArgs', $mock);
    }


}