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
 * Block property constraint
 *
 */
class EcomDev_PHPUnit_Constraint_Layout_Block_Property
    extends EcomDev_PHPUnit_Constraint_AbstractLayout
{
    const TYPE_CONSTRAINT = 'constraint';

    /**
     * Block name for constraint
     *
     * @var string
     */
    protected $_blockName = null;

    /**
     * Block property for constraint
     *
     * @var string
     */
    protected $_propertyName = null;

    /**
     * Block property constraint
     *
     * @param string $blockName
     * @param mixed|null $propertyName
     * @param \PHPUnit\Framework\Constraint\Constraint $constraint
     * @param string $type
     * @throws \PHPUnit\Framework\Exception
     */
    public function __construct($blockName, $propertyName, \PHPUnit\Framework\Constraint\Constraint $constraint,
        $type = self::TYPE_CONSTRAINT)
    {
        if (empty($blockName) || !is_string($blockName)) {
            throw EcomDev_PHPUnit_Helper::createInvalidArgumentException(1, 'string', $blockName);
        }

        if (empty($propertyName) || !is_string($propertyName)) {
            throw EcomDev_PHPUnit_Helper::createInvalidArgumentException(2, 'string', $propertyName);
        }

        parent::__construct($type, $constraint);

        $this->_blockName = $blockName;
        $this->_propertyName = $propertyName;
    }

    /**
     * Retuns number of constraint assertions
     *
     * (non-PHPdoc)
     * @see \PHPUnit\Framework\Constraint\Constraint::count()
     */
    public function count()
    {
        return $this->_expectedValue->count();
    }

    /**
     * Returning user friendly actual value
     * (non-PHPdoc)
     * @see EcomDev_PHPUnit_ConstraintAbstract::getActualValue()
     */
    protected function getActualValue($other = null)
    {
        if ($this->_useActualValue) {
            if ($this->_actualValue instanceof Varien_Object) {
                $value = $this->_actualValue->debug();
            } else {
                $value = $this->_actualValue;
            }

            return $value;
        }

        return '';
    }

    /**
     * Evaluates a property constraint
     *
     * @param EcomDev_PHPUnit_Constraint_Layout_LoggerInterface $other
     * @return boolean
     */
    protected function evaluateConstraint($other)
    {
        $this->setActualValue(
            $other->getBlockProperty($this->_blockName, $this->_propertyName)
        );

        return $this->_expectedValue->evaluate($this->_actualValue);
    }

    /**
     * Text representation of block property constraint
     *
     * @return string
     */
    protected function textConstraint()
    {
        return sprintf('block "%s" property "%s" %s',
                      $this->_blockName, $this->_propertyName,
                      $this->_expectedValue->toString());
    }
}
