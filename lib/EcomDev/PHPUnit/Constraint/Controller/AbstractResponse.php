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
 * Abstract constraint for controller response assertions
 *
 */
abstract class EcomDev_PHPUnit_Constraint_Controller_AbstractResponse
    extends EcomDev_PHPUnit_AbstractConstraint
{
    /**
     * Custom failure description for showing response related errors
     * (non-PHPdoc)
     * @see \PHPUnit\Framework\Constraint\Constraint::customFailureDescription()
     */
    protected function customFailureDescription($other)
    {
        return sprintf(
            'request %s.',
            $this->toString()
        );
    }
}
