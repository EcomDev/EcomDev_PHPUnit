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
 * @copyright  Copyright (c) 2011 Ecommerce Developers (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Interface for fixture model
 * Can be used for creation of
 * absolutely different implementation of fixture,
 * then current one.
 *
 */
interface EcomDev_PHPUnit_Model_Fixture_Interface extends EcomDev_PHPUnit_Model_Test_Loadable_Interface
{
    /**
     * Sets fixture options
     *
     * @param array $options
     * @return EcomDev_PHPUnit_Model_Fixture_Interface
     */
    public function setOptions(array $options);
}