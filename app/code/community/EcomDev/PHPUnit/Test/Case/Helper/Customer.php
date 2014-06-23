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

use EcomDev_PHPUnit_Helper as TestHelper;
use EcomDev_PHPUnit_Test_Case_Util as TestUtil;

/**
 * Helper for stubbing customer session
 *
 *
 */
class EcomDev_PHPUnit_Test_Case_Helper_Customer extends EcomDev_PHPUnit_AbstractHelper
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
        $customerSessionMock =  TestHelper::invoke(
            'mockSession',
            'customer/session',
            array('renewSession')
        );

        if ($storeId === null) {
            $storeId = TestUtil::app()->getAnyStoreView()->getCode();
        }

        TestUtil::setCurrentStore($storeId);
        $customerSessionMock->loginById($customerId);
        return $customerSessionMock;
    }
}
