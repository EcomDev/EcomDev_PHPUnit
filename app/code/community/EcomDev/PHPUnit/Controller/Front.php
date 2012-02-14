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
 * @copyright  Copyright (c) 2012 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Front controller for test suite
 *
 */
class EcomDev_PHPUnit_Controller_Front extends Mage_Core_Controller_Varien_Front
{
    /**
     * Overriden for getting rid
     * of initialization of routers for each test case
     *
     * (non-PHPdoc)
     * @see Mage_Core_Controller_Varien_Front::init()
     */
    public function init()
    {
        if (!$this->_routers) {
            parent::init();
        }

        return $this;
    }
}