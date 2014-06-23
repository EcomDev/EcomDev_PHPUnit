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
 * Abstract class for constraints based on configuration
 *
 */
abstract class EcomDev_PHPUnit_Constraint_AbstractConfig
    extends EcomDev_PHPUnit_AbstractConstraint
    implements EcomDev_PHPUnit_Constraint_ConfigInterface
{
    /**
     * Config node path defined in the constructor
     *
     * @var string
     */
    protected $_nodePath = null;

    /**
     * Constraint constructor
     *
     * @param string $nodePath
     * @param string $type
     * @param mixed $expectedValue
     * @throws PHPUnit_Framework_Exception
     */
    public function __construct($nodePath, $type, $expectedValue = null)
    {
        if (empty($nodePath) || !is_string($nodePath)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string', $type);
        }

        $this->_nodePath = $nodePath;
        parent::__construct($type, $expectedValue);
    }

    /**
     * Returns node path for checking
     *
     * (non-PHPdoc)
     * @see EcomDev_PHPUnit_Constraint_ConfigInterface::getNodePath()
     */
    public function getNodePath()
    {
        return $this->_nodePath;
    }

    /**
     * Automatically evaluate to false if the node was not found
     *
     * (non-PHPdoc)
     * @see EcomDev_PHPUnit_ConstraintAbstract::evaluate()
     */
    public function evaluate($other, $description = '', $returnResult = false)
    {
        if ($other === false) {
            // If node was not found, than evaluation fails
            return false;
        }

        return parent::evaluate($other, $description, $returnResult);
    }


    /**
     * Returns a scalar representation of actual value,
     * Returns $other if internal acutal value is not set
     *
     * @param Varien_Simplexml_Element $other
     * @return scalar
     */
    protected function getActualValue($other = null)
    {
        if (!$this->_useActualValue && $other->hasChildren()) {
            return $this->getXmlAsDom($other);
        } elseif (!$this->_useActualValue) {
            return (string) $other;
        }

        return parent::getActualValue($other);
    }

    /**
     * Returns a scalar representation of expected value
     *
     * @return string
     */
    protected function getExpectedValue()
    {
        if ($this->_expectedValue instanceof Varien_Simplexml_Element) {
            return $this->getXmlAsDom($this->_expectedValue);
        }

        return parent::getExpectedValue();
    }

    /**
     * Converts xml to dom object
     *
     * @param $xmlValue
     * @return DOMDocument
     */
    protected function getXmlAsDom($xmlValue)
    {
        if ($xmlValue instanceof SimpleXMLElement) {
            $xmlValue = $xmlValue->asXML();
        }

        $domValue = new DOMDocument;
        $domValue->preserveWhiteSpace = FALSE;
        $domValue->loadXML($xmlValue);

        return $domValue;
    }
}