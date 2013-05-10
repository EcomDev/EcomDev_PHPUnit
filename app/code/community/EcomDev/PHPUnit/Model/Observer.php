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

class EcomDev_PHPUnit_Model_Observer
{
    const XML_PATH_TEST_HELPERS = 'phpunit/suite/helpers';

    /**
     * Registers default test helpers
     *
     */
    public function registerDefaultTestHelpers()
    {
        foreach (Mage::getConfig()->getNode(self::XML_PATH_TEST_HELPERS)->children() as $helperNode) {
            $helperClass = (string)$helperNode;

            if ($helperClass && class_exists($helperClass)) {
                $helper = new $helperClass();

                if (!$helper instanceof EcomDev_PHPUnit_Helper_Interface) {
                    throw new RuntimeException(
                        sprintf(
                            'Test helpers should implement %s, but %s is not implementing it.',
                            'EcomDev_PHPUnit_Helper_Interface',
                            $helperClass
                        )
                    );
                }

                EcomDev_PHPUnit_Helper::add($helper);
            }

        }
    }
}