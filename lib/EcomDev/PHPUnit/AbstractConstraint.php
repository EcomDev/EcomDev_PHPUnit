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
 * Abstract constraint for EcomDev_PHPUnit constraints
 * Contains flexible constraint types implementation
 *
 */
abstract class EcomDev_PHPUnit_AbstractConstraint
    extends PHPUnit_Framework_Constraint
{
    /**
     * List of validation rules for expected value
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
     * Comparison type defined in the constructor
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
     * Comparison failure for nice failure messages
     *
     * @var PHPUnit_Framework_ComparisonFailure
     */
    protected $_comparisonFailure = null;

    /**
     * Abstract cnstraint constructor,
     * provides unified interface for working with multiple types of evalation
     *
     * @param string $type
     * @param mixed  $expectedValue
     *
     * @throws PHPUnit_Framework_Exception
     */
    public function __construct($type, $expectedValue = null)
    {
        $reflection = EcomDev_Utils_Reflection::getReflection(get_class($this));
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
                                  $this->_expectedValueValidation[$type][2] :
                                  '');

            // Mandatory check
            if (isset($this->_expectedValueValidation[$type][0])
                && $this->_expectedValueValidation[$type][0]
                && $expectedValue === null) {
                throw PHPUnit_Util_InvalidArgumentHelper::factory(2, $expectedValueType, $expectedValue);
            }

            // Type check
            if (isset($this->_expectedValueValidation[$type][1])
                && $expectedValue !== null
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
     * @return EcomDev_PHPUnit_AbstractConstraint
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
     * @param mixed|null $argument
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
     *
     * @see PHPUnit_Framework_Constraint::evaluate()
     *
     * @param  mixed $other Value or object to evaluate.
     * @param  string $description Additional information about the test
     * @param  bool $returnResult Whether to return a result or throw an exception
     * @return mixed
     */
    public function evaluate($other, $description = '', $returnResult = false)
    {
        $success = false;

        if ($this->callProtectedByType('evaluate', $other)) {
            $success = true;
        }

        if ($returnResult) {
            return $success;
        }

        if (!$success) {
            $this->fail($other, $description);
        }
    }

    /**
     * Generates a failure exception based on exception type
     *
     * (non-PHPdoc)
     * @see PHPUnit_Framework_Constraint::fail()
     */
    public function fail($other, $description, PHPUnit_Framework_ComparisonFailure $comparisonFailure = NULL)
    {
        $failureDescription = sprintf('Failed asserting that %s', $this->failureDescription($other));

        if (in_array($this->_type, $this->_typesWithDiff)) {
            throw new EcomDev_PHPUnit_Constraint_Exception(
                $failureDescription,
                $this->getComparisonFailure($this->getExpectedValue(), $this->getActualValue($other)),
                $description
            );
        } else {
            throw new EcomDev_PHPUnit_Constraint_Exception(
                $failureDescription, $this->getActualValue($other), $description
            );
        }
    }

    /**
     * Adds compatibility to PHPUnit 3.6
     *
     * @param mixed $other
     * @return string
     */
    protected function failureDescription($other)
    {
        if (method_exists($this, 'customFailureDescription')) {
            return $this->customFailureDescription($other);
        }

        return parent::failureDescription($other);
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
     * @return scalar
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

    /**
     * Exports value as string
     *
     * @param mixed $value
     * @return string
     */
    public function exportAsString($value)
    {
        if (is_array($value) && preg_match('/^\d+$/', implode('', array_keys($value)))) {
            $stringValue = '';
            foreach ($value as $val) {
                $stringValue .= (is_string($val) ? $val : PHPUnit_Util_Type::export($val)) . "\n";
            }

            return $stringValue;
        } else {
            return PHPUnit_Util_Type::export($value);
        }
    }

    /**
     * Compares two values by using correct comparator for two types
     *
     * @param mixed $expectedValue
     * @param mixed $actualValue
     * @return bool
     */
    public function compareValues($expectedValue, $actualValue)
    {
        $comparatorFactory = PHPUnit_Framework_ComparatorFactory::getDefaultInstance();

        try {
            $comparator = $comparatorFactory->getComparatorFor(
                $expectedValue, $actualValue
            );

            $comparator->assertEquals(
                $expectedValue,
                $actualValue
            );
        }

        catch (PHPUnit_Framework_ComparisonFailure $f) {
            $this->_comparisonFailure = $f;
            return false;
        }

        return true;
    }

    /**
     * Retrieve comparison failure exception.
     *
     * Is used for generation of the failure messages
     *
     * @param mixed $expectedValue
     * @param mixed $actualValue
     *
     * @return PHPUnit_Framework_ComparisonFailure
     */
    public function getComparisonFailure($expectedValue, $actualValue)
    {
        if ($this->_comparisonFailure !== null) {
            $failure = $this->_comparisonFailure;
            $this->_comparisonFailure = null;
            return $failure;
        }

        return new PHPUnit_Framework_ComparisonFailure(
            $expectedValue,
            $actualValue,
            $this->exportAsString($expectedValue),
            $this->exportAsString($actualValue)
        );
    }
}
