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

interface EcomDev_PHPUnit_Model_Fixture_Processor_Interface
{
    /**
     * Applies data from fixture file
     *
     * @param array[] $data
     * @param string $key
     * @param EcomDev_PHPUnit_Model_Fixture_Interface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Interface
     */
    public function apply(array $data, $key, EcomDev_PHPUnit_Model_Fixture_Interface $fixture);

    /**
     * Discards data from fixture file
     *
     * @param array[] $data
     * @param string $key
     * @param EcomDev_PHPUnit_Model_Fixture_Interface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Interface
     */
    public function discard(array $data, $key, EcomDev_PHPUnit_Model_Fixture_Interface $fixture);

    /**
     * Initializes fixture processor before applying data
     *
     * @param EcomDev_PHPUnit_Model_Fixture_Interface $fixture
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Interface
     */
    public function initialize(EcomDev_PHPUnit_Model_Fixture_Interface $fixture);
}
