<?php

/**
 * Abstract class for constraints based on configuration
 *
 */
abstract class EcomDev_PHPUnit_Constraint_Config_Abstract
    extends PHPUnit_Framework_Constraint
    implements EcomDev_PHPUnit_Constraint_Config_Interface
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
     * Config node path defined in the constructor
     *
     * @var string
     */
    protected $_nodePath = null;

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
     * Flag for using of actual valu in failure description
     *
     * @var boolean
     */
    protected $_useActualValue = false;

    /**
     * Constraint constructor
     *
     * @param string $nodePath
     * @param string $type
     * @param mixed $expectedValue
     */
    public function __construct($nodePath, $type, $expectedValue = null)
    {
        if (empty($nodePath) || !is_string($nodePath)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'string', $type);
        }

        $reflection = EcomDev_Utils_Reflection::getRelflection(get_class($this));
        $types = array();
        foreach ($reflection->getConstants() as $name => $constant) {
            if (strpos($name, 'TYPE_') === 0) {
                $types[] = $constant;
            }
        }

        if (empty($type) || !is_string($type) || !in_array($type, $types)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(2, 'string', $type);
        }


        if (isset($this->_expectedValueValidation[$type])) {
            $expectedValueType = (isset($this->_expectedValueValidation[$type][2]) ?
                                  isset($this->_expectedValueValidation[$type][2]) :
                                  '');

            // Mandatory check
            if (isset($this->_expectedValueValidation[$type][0])
                && $this->_expectedValueValidation[$type][0]
                && $expectedValue === null) {
                throw PHPUnit_Util_InvalidArgumentHelper::factory(3, $expectedValueType, $expectedValue);
            }

            // Type check
            if (isset($this->_expectedValueValidation[$type][1])
                && !$this->_expectedValueValidation[$type][1]($expectedValue)) {
                throw PHPUnit_Util_InvalidArgumentHelper::factory(3, $expectedValueType, $expectedValue);
            }

        }


        $this->_nodePath = $nodePath;
        $this->_type = $type;
        $this->_expectedValue = $expectedValue;
    }

    /**
     * Set actual value that will be used in the fail message
     *
     * @param mixed $actual
     * @return EcomDev_PHPUnit_Constraint_Config_Abstract
     */
    protected function setActualValue($actual)
    {
        $this->_useActualValue = true;
        $this->_actualValue = $actual;
        return $this;
    }

    /**
     * Returns node path for checking
     *
     * (non-PHPdoc)
     * @see EcomDev_PHPUnit_Constraint_Config_Interface::getNodePath()
     */
    public function getNodePath()
    {
        return $this->_nodePath;
    }

    /**
     * Calls internal protected method by defined constraint type
     * Also can be passed a single argument
     *
     * @param string $prefix
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
        if ($other === false) {
            // If node was not found, than evaluation fails
            return false;
        }

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
            if ($this->_useActualValue) {
                $other = $this->_actualValue;
            } elseif ($other->hasChildren()) {
                $other = $other->asNiceXml();
            } else {
                $other = (string) $other;
            }

            if ($this->_expectedValue instanceof Varien_Simplexml_Element) {
                $expected = $this->_expectedValue->asNiceXml();
            } else {
                $expected = $this->_expectedValue;
            }

            throw new EcomDev_PHPUnit_Constraint_Exception(
                $failureDescription,
                PHPUnit_Util_Diff::diff($expected, $other),
                $description
            );
        } else {
            throw new EcomDev_PHPUnit_Constraint_Exception(
                $failureDescription, $other->asNiceXml(), $description
            );
        }
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