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
 * Constraint for testing the configuration values
 *
 *
 */
class EcomDev_PHPUnit_Constraint_Config extends PHPUnit_Framework_Constraint
{
    /**
     * Configuration instance
     *
     * @var Varien_Simplexml_Config
     */
    protected $config = null;

    /**
     * Configuration constraint
     *
     * @var PHPUnit_Framework_Constraint
     */
    protected $constraint = null;

    /**
     * Creates configuration constraint for config object
     *
     * @param $constraint
     * @throws PHPUnit_Framework_Exception
     * @internal param \Varien_Simplexml_Config $config
     */
    public function __construct($constraint)
    {
        if (!$constraint instanceof EcomDev_PHPUnit_Constraint_ConfigInterface) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                1, 'EcomDev_PHPUnit_Constraint_ConfigInterface'
            );
        }
        $this->constraint = $constraint;
    }

    /**
     * Failure generator
     *
     * @param mixed $other
     * @param string $description
     * @param PHPUnit_Framework_ComparisonFailure $comparisonFailure
     */
    public function fail($other, $description, PHPUnit_Framework_ComparisonFailure $comparisonFailure = NULL)
    {
        $nodeValue = $this->getNodeValue($other);

        return $this->constraint->fail($nodeValue, $description, $comparisonFailure);
    }

    /**
     * Retrives a node value from configuration by child constraint path
     *
     *
     * @param $config
     * @throws EcomDev_PHPUnit_Constraint_Exception
     * @return
     * @internal param \Varien_Simplexml_Config $other
     */
    protected function getNodeValue($config)
    {
        $nodeValue = $config->getNode(
            $this->constraint->getNodePath()
        );

        if ($nodeValue === false) {
            throw new EcomDev_PHPUnit_Constraint_Exception(
                sprintf('Cannot find any node in specified path: %s', $this->constraint->getNodePath())
            );
        }

        return $nodeValue;
    }

    /**
     * Evalutes constraint that is passed in the parameter
     *
     * @param Varien_Simplexml_Config $config
     * @param string $description
     * @param bool $returnResult
     * @return bool
     * @see PHPUnit_Framework_Constraint::evaluate()
     */
    public function evaluate($config, $description = '', $returnResult = false)
    {
        $nodeValue = $this->getNodeValue($config);

        return $this->constraint->evaluate($nodeValue, $description, $returnResult);
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString()
    {
        return $this->constraint->toString();
    }

    /**
     * Returns the description of the failure
     *
     * The beginning of failure messages is "Failed asserting that" in most
     * cases. This method should return the second part of that sentence.
     *
     * To provide additional failure information additionalFailureDescription
     * can be used.
     *
     * @param  mixed $other Evaluated value or object.
     * @return string
     */
    protected function failureDescription($other)
    {
        $nodeValue = $this->getNodeValue($other);
        return $this->constraint->failureDescription($nodeValue);
    }
}
