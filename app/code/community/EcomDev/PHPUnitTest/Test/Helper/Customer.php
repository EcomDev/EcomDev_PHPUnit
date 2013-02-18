<?php

/**
 * Tests for customer session creation
 *
 * @loadSharedFixture customers
 */
class EcomDev_PHPUnitTest_Test_Helper_Customer extends EcomDev_PHPUnit_Test_Case
{
    /**
     *
     * @dataProvider dataProvider
     */
    public function testCustomerSession($customerId)
    {
        $customerSession = $this->customerSession($customerId);
        $expected = $this->expected('auto');

        $this->assertEquals($expected->getIsLoggedIn(), $customerSession->isLoggedIn());

        if ($expected->getName()) {
            $this->assertEquals($expected->getName(), $customerSession->getCustomer()->getName());
        }
    }
}
