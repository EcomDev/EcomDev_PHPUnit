<?php

class EcomDev_PHPUnitTest_Test_Helper_Session extends EcomDev_PHPUnit_Test_Case_Controller
{
    public function testMockSession()
    {
        $sessionMock = $this->mockSession('admin/session', array('getUserId'));

        $this->assertInstanceOf('EcomDev_PHPUnit_Mock_Proxy', $sessionMock);
        $this->assertInstanceOf('Mage_Admin_Model_Session', $sessionMock->getMockInstance());

        $this->assertSame($sessionMock->getMockInstance(), Mage::getSingleton('admin/session'));
    }

    /**
     * Tests stubing of admin session
     *
     */
    public function testAdminSessionAllRights()
    {
        $this->adminSession();
        $this->assertTrue(Mage::getSingleton('admin/session')->isAllowed('catalog/product'));
        $this->assertTrue(Mage::getSingleton('admin/session')->isAllowed('sales/order'));
        $this->assertTrue(Mage::getSingleton('admin/session')->isAllowed('system/config'));
    }

    /**
     * Tests creation of admin session
     *
     */
    public function testAdminSessionOnlyCatalog()
    {
        $this->adminSession(array('catalog'));

        $this->assertTrue(Mage::getSingleton('admin/session')->isAllowed('catalog/product'));
        $this->assertTrue(Mage::getSingleton('admin/session')->isAllowed('catalog/category'));
        $this->assertFalse(Mage::getSingleton('admin/session')->isAllowed('sales/order'));
        $this->assertFalse(Mage::getSingleton('admin/session')->isAllowed('system/config'));
    }

    /**
     * Tests creation of admin session
     *
     */
    public function testAdminSessionOnlyCatalogProduct()
    {
        $this->adminSession(array('catalog/product'));

        $this->assertTrue(Mage::getSingleton('admin/session')->isAllowed('catalog/product'));
        $this->assertFalse(Mage::getSingleton('admin/session')->isAllowed('catalog/category'));
        $this->assertFalse(Mage::getSingleton('admin/session')->isAllowed('sales/order'));
        $this->assertFalse(Mage::getSingleton('admin/session')->isAllowed('system/config'));
    }
}
