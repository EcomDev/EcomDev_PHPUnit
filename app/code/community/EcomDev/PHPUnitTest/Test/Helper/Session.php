<?php

class EcomDev_PHPUnitTest_Test_Helper_Session extends EcomDev_PHPUnit_Test_Case_Controller
{
    /**
     * Tests stubbing of any session
     *
     */
    public function testMockSession()
    {
        $sessionMock = $this->mockSession('admin/session', array('getUserId'));

        $this->assertInstanceOf('EcomDev_PHPUnit_Mock_Proxy', $sessionMock);
        $this->assertInstanceOf('Mage_Admin_Model_Session', $sessionMock->getMockInstance());

        $this->assertSame($sessionMock->getMockInstance(), Mage::getSingleton('admin/session'));
    }

    /**
     * Tests stub of admin session
     *
     */
    public function testAdminSessionAllRights()
    {
        $this->adminSession();
        $this->assertTrue(Mage::getSingleton('admin/session')->isLoggedIn());
        $this->assertTrue(Mage::getSingleton('admin/session')->isAllowed('catalog/products'));
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
        $this->assertTrue(Mage::getSingleton('admin/session')->isLoggedIn());
        $this->assertTrue(Mage::getSingleton('admin/session')->isAllowed('catalog/products'));
        $this->assertTrue(Mage::getSingleton('admin/session')->isAllowed('catalog/categories'));
        $this->assertFalse(Mage::getSingleton('admin/session')->isAllowed('sales/order'));
        $this->assertFalse(Mage::getSingleton('admin/session')->isAllowed('system/config'));
    }

    /**
     * Tests creation of admin session
     *
     */
    public function testAdminSessionOnlyCatalogProduct()
    {
        $this->adminSession(array('catalog/products'));

        $this->assertTrue(Mage::getSingleton('admin/session')->isLoggedIn());
        $this->assertTrue(Mage::getSingleton('admin/session')->isAllowed('catalog/products'));
        $this->assertFalse(Mage::getSingleton('admin/session')->isAllowed('catalog/categories'));
        $this->assertFalse(Mage::getSingleton('admin/session')->isAllowed('sales/order'));
        $this->assertFalse(Mage::getSingleton('admin/session')->isAllowed('system/config'));
    }
}
