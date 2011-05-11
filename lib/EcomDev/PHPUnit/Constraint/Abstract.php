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
 * Abstract constraint for EcomDev_PHPUnit constraints
 * Contains flexible constaint types implementation
 *
 */
abstract class EcomDev_PHPUnit_Constraint_Abstract
    extends PHPUnit_Framework_Constraint
{
    /**
     * List of valiadation rules for expected value
     * It is an associative array with key as type and value
     * as an array of rules.
     *
     * First item of the rule array is mandatory indicator,
     * second is function name for checking the type,
     * third one is the type that will be displayed in invalid argument expception
     * each of them can be ommited or if it between other ones just by specifying null value
     *
     * @var array
     */
    protected $_expectedValueValidation = array();

    /**
     * List of types that will use diff for displaying fail result
     *
     * @var array
     */
    protected $_typesWithDiff = array();

    /**
     * Comparisment type defined in the constructor
     *
     * @var string
     */
    protected $_type = null;

    /**
     * Expected value defined in the constructor
     *
     * @var mixed
     */
    protected $_expectedValue = null;

    /**
     * Custom actual value
     *
     * @var mixed
     */
    protected $_actualValue = null;

    /**
     * Flag for using of actual value in failure description
     *
     * @var boolean
     */
    protected $_useActualValue = false;

    /**
     * Abstract cnstraint constructor,
     * provides unified interface for working with multiple types of evalation
     *
     * @param string $type
     * @param mixed $expectedValue
     */
    public function __construct($type, $expectedValue = null)
    {
        $reflection = EcomDev_Utils_Reflection::getRelflection(get_class($this));
        $types = array();
        foreach ($reflection->getConstants() as $name => $constant) {
            if (strpos($name, 'TYPE_') === 0) {
                $types[] = $constant;
            }
        }

        if (empty($type) || !is_string($type) || !in_array($type, $types)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string', $type);
        }


        if (isset($this->_expectedValueValidation[$type])) {
            $expectedValueType = (isset($this->_expectedValueValidation[$type][2]) ?
                                  isset($this->_expectedValueValidation[$type][2]) :
                                  '');

            // Mandatory check
            if (isset($this->_expectedValueValidation[$type][0])
                && $this->_expectedValueValidation[$type][0]
                && $expectedValue === null) {
                throw PHPUnit_Util_InvalidArgumentHelper::factory(2, $expectedValueType, $expectedValue);
            }

            // Type check
            if (isset($this->_expectedValueValidation[$type][1])
                && !$this->_expectedValueValidation[$type][1]($expectedValue)) {
                throw PHPUnit_Util_InvalidArgumentHelper::factory(2, $expectedValueType, $expectedValue);
            }
        }

        $this->_type = $type;
        $this->_expectedValue = $expectedValue;
    }

    /**
     * Set actual value that will be used in the fail message
     *
     * @param mixed $actual
     * @return EcomDev_PHPUnit_Constraint_Abstract
     */
    protected function setActualValue($actual)
    {
        $this->_useActualValue = true;
        $this->_actualValue = $actual;
        return $this;
    }


    /**
     * Calls internal protected method by defined constraint type
     * Also can be passed a single argument
     *
     * @param string $prefix
     * @return mixed
     */
    protected function callProtectedByType($prefix, $argument = null)
    {
        $camelizedType = uc_words($this->_type, '');
        $methodName = $prefix . $camelizedType;
        return $this->$methodName($argument);
    }

    /**
     * Evaluates value by type.
     * (non-PHPdoc)
     * @see PHPUnit_Framework_Constraint::evaluate()
     */
    public function evaluate($other)
    {
        return $this->callProtectedByType('evaluate', $other);
    }

    /**
     * Generates a failure exception based on exception type
     *
     * (non-PHPdoc)
     * @see PHPUnit_Framework_Constraint::fail()
     */
    public function fail($other, $description, $not = FALSE)
    {
        $failureDescription = $this->failureDescription($other, $description, $not);

        if (in_array($this->_type, $this->_typesWithDiff)) {
            throw new EcomDev_PHPUnit_Constraint_Exception(
                $failureDescription,
                PHPUnit_Util_Diff::diff($this->getExpectedValue(), $this->getActualValue($other)),
                $description
            );
        } else {
            throw new EcomDev_PHPUnit_Constraint_Exception(
                $failureDescription, $this->getActualValue($other), $description
            );
        }
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
        if ($this->_useActualValue) {
            return $this->_actualValue;
        }

        return $other;
    }

    /**
     * Returns a scalar representation of expected value
     *
     * @return string
     */
    protected function getExpectedValue()
    {
        return $this->_expectedValue;
    }

    /**
     * Text reperesentation of constraint
     * (non-PHPdoc)
     * @see PHPUnit_Framework_SelfDescribing::toString()
     */
    public function toString()
    {
        return $this->callProtectedByType('text');
    }
}