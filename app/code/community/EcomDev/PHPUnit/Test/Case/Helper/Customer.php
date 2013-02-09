<?php

class EcomDev_PHPUnit_Test_Case_Helper_Customer extends EcomDev_PHPUnit_Helper_Abstract
{
    /**
     * Logs in as a customer by customer id and store id
     *
     * @param int             $customerId
     * @param string|int|null $storeId
     *
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function helperCustomerSession($customerId, $storeId = null)
    {
        $customerSessionMock =  $this->helperMockSession('customer/session', array('renewSession'));

        if ($storeId === null) {
            $storeId = TestUtil::app()->getAnyStoreView()->getCode();
        }

        TestUtil::setCurrentStore($storeId);
        $customerSessionMock->loginById($customerId);
        return $customerSessionMock;
    }
}