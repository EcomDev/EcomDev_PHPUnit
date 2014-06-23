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

/**
 * Interface for loadable test environment data
 *
 */
interface EcomDev_PHPUnit_Model_LoadableInterface
{
    /**
     * Loads external data by test case instance
     *
     * @param PHPUnit_Framework_TestCase $testCase
     * @return $this
     */
    public function loadByTestCase(PHPUnit_Framework_TestCase $testCase);

    /**
     * Applies external data
     *
     * @return $this
     */
    public function apply();

    /**
     * Reverts applied data
     *
     * @return $this
     */
    public function discard();
}
