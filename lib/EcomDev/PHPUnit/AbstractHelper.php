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
 * Base helper implementation
 */
abstract class EcomDev_PHPUnit_AbstractHelper 
    implements EcomDev_PHPUnit_HelperInterface
{
    /**
     * @var PHPUnit_Framework_TestCase
     */
    protected $testCase;

    /**
     * Checks existence of helper action
     *
     * @param string $action
     * @return bool
     */
    public function has($action)
    {
        return $this->hasMethod('helper' . ucfirst($action));
    }

    /**
     * Invokes defined helper action
     *
     * @param string $action
     * @param array  $args
     *
     * @throws RuntimeException
     * @return mixed
     */
    public function invoke($action, array $args)
    {
        if (!$this->has($action)) {
            throw new RuntimeException(sprintf('Helper "%s" is not invokable.', $action));
        }

        $methodName = 'helper' . ucfirst($action);
        return $this->callMethod($methodName, $args);
    }

    /**
     * Call method to make testable of the invoke method
     *
     * @param $method
     * @param $args
     * @return mixed
     */
    protected function callMethod($method, $args)
    {
        return call_user_func_array(array($this, $method), $args);
    }

    /**
     * Has method for making abstract testable
     *
     * @param array $method
     * @return bool
     */
    protected function hasMethod($method)
    {
        $reflection = EcomDev_Utils_Reflection::getReflection($this);
        return $reflection->hasMethod($method);
    }

    /**
     * Sets test case property for helper
     *
     * @param PHPUnit_Framework_TestCase $testCase
     *
     * @return $this
     */
    public function setTestCase(PHPUnit_Framework_TestCase $testCase)
    {
        $this->testCase = $testCase;
        return $this;
    }

}