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
 * Base for all layout constraints
 *
 */
abstract class EcomDev_PHPUnit_Constraint_Layout_Abstract extends EcomDev_PHPUnit_Constraint_Abstract
{
    /**
     * Custom failure description for showing layout related errors
     * (non-PHPdoc)
     * @see PHPUnit_Framework_Constraint::customFailureDescription()
     */
    protected function customFailureDescription($other, $description, $not)
    {
        return sprintf(
            'layout %s.',
            $this->toString()
        );
    }

    /**
     * For layout, actual value should be always set
     * (non-PHPdoc)
     * @see EcomDev_PHPUnit_Constraint_Abstract::getActualValue()
     */
    protected function getActualValue($other)
    {
        if ($this->_useActualValue) {
            return parent::getActualValue($other);
        }

        return '';
    }
}
