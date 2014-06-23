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
 * @author     Mike Pretzlaw <pretzlaw@gmail.com>
 */

use EcomDev_PHPUnit_Helper as TestHelper;
use EcomDev_PHPUnit_Test_Case_Util as TestUtil;

/**
 * Helper for stubbing customer session
 *
 *
 */
class EcomDev_PHPUnit_Test_Case_Helper_Guest extends EcomDev_PHPUnit_AbstractHelper
{
    /**
     * Start session as guest.
     *
     * @param string|int|null $storeId
     *
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function helperGuestSession($storeId = null)
    {
        $guestSessionMock = TestHelper::invoke(
            'mockSession',
            'core/session',
            array('renewSession')
        );

        /** @var Mage_Core_Model_Session $session */
        $session                                  = $guestSessionMock->getMock();
        $_GET[$session->getSessionIdQueryParam()] = $session->getSessionId(); // some action need that (loginPost, ...)

        if ($storeId === null)
        {
            $storeId = TestUtil::app()->getAnyStoreView()->getCode();
        }

        TestUtil::setCurrentStore($storeId);

        return $guestSessionMock;
    }
}
