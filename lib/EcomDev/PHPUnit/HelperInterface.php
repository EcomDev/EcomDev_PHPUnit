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
 * Interface for PHPUnit Test Helpers
 */
interface EcomDev_PHPUnit_HelperInterface
{
    /**
     * Checks if helper has action for invocation
     *
     * @param string $action
     * @return bool
     */
    public function has($action);

    /**
     * Invokes helper action
     *
     * @param string $action
     * @param array $args
     *
     * @return mixed
     */
    public function invoke($action, array $args);

    /**
     * Sets test case for usage in helper
     *
     * @param PHPUnit_Framework_TestCase $testCase
     * @return $this
     */
    public function setTestCase(PHPUnit_Framework_TestCase $testCase);
}
