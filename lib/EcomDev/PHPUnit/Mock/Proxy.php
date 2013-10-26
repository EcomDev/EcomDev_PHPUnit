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
 * PHPUnit Mock Object Proxy
 *
 * Used to support mock builder auto-apply as soon as expects method is called.
 */
class EcomDev_PHPUnit_Mock_Proxy
    extends PHPUnit_Framework_MockObject_MockBuilder
    implements PHPUnit_Framework_MockObject_MockObject
{
    protected $mockInstance;

    /**
     * Adds method name to a mock builder
     *
     * @param string $methodName
     * @return $this
     */
    public function addMethod($methodName)
    {
        $this->methods[] = $methodName;
        return $this;
    }


    /**
     * Removes method name from a mock builder
     *
     * @param string $methodName
     * @return $this
     */
    public function removeMethod($methodName)
    {
        $methodIndex = array_search($methodName, $this->methods);
        if ($methodIndex !== false) {
            array_splice($this->methods, $methodIndex, 1);
        }
        return $this;
    }

    /**
     * Preserves methods from override in mocked object
     *
     * @return $this
     */
    public function preserveMethods()
    {
        $this->setMethods(null);
        return $this;
    }

    /**
     * Proxied mock instance retrieval
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockInstance()
    {
        if ($this->mockInstance === null) {
            $reflection = EcomDev_Utils_Reflection::getReflection($this->className);
            $this->mockInstance = ($reflection->isAbstract() || $reflection->isInterface())
                                    ? $this->getMockForAbstractClass() : $this->getMock();
        }

        return $this->mockInstance;
    }

    /**
     * Returns mock class name for generated mock instance
     *
     * @return string
     */
    public function getMockClass()
    {
        return get_class($this->getMockInstance());
    }


    /**
     * Registers a new expectation in the mock object and returns the match
     * object which can be infused with further details.
     *
     * @param  PHPUnit_Framework_MockObject_Matcher_Invocation $matcher
     * @return PHPUnit_Framework_MockObject_Builder_InvocationMocker
     */
    public function expects(PHPUnit_Framework_MockObject_Matcher_Invocation $matcher)
    {
        return $this->getMockInstance()->expects($matcher);
    }

    /**
     * Registers a new static expectation in the mock object and returns the
     * match object which can be infused with further details.
     *
     * @param  PHPUnit_Framework_MockObject_Matcher_Invocation $matcher
     * @return PHPUnit_Framework_MockObject_Builder_InvocationMocker
     * @throws RuntimeException in case if you call it
     */
    public static function staticExpects(PHPUnit_Framework_MockObject_Matcher_Invocation $matcher)
    {
        throw new RuntimeException(
            'This method cannot be called on mock proxy, use staticExpectsProxy instead'
        );
    }

    /**
     * Registers a new static expectation in the mock object and returns the
     * match object which can be infused with further details.
     *
     * @param PHPUnit_Framework_MockObject_Matcher_Invocation $matcher
     * @return PHPUnit_Framework_MockObject_Builder_InvocationMocker
     */
    public function staticExpectsProxy(PHPUnit_Framework_MockObject_Matcher_Invocation $matcher)
    {
        return $this->getMockInstance()->staticExpects($matcher);
    }

    /**
     * Returns invocation mocker for
     *
     * @throws RuntimeException
     * @return PHPUnit_Framework_MockObject_InvocationMocker
     */
    public function __phpunit_getInvocationMocker()
    {
        throw new RuntimeException(
            'Mock object proxy cannot be used for retrieving invocation mockers, '
                . 'use getMockInstance method for real mock object'
        );
    }

    /**
     * Returns static invocation mocker
     * 
     * @throws RuntimeException
     * @return PHPUnit_Framework_MockObject_InvocationMocker
     */
    public static function __phpunit_getStaticInvocationMocker()
    {
        throw new RuntimeException(
            'Mock object proxy cannot be used for retrieving invocation mockers, '
                . 'use getMockInstance method for real mock object'
        );
    }

    /**
     * Verifies that the current expectation is valid. If everything is OK the
     * code should just return, if not it must throw an exception.
     *
     * @throws PHPUnit_Framework_ExpectationFailedException
     */
    public function __phpunit_verify()
    {
        throw new RuntimeException(
            'Mock object proxy cannot be used for verifying mock'
                . 'use getMockInstance method for real mock object'
        );
    }

    /**
     * Forwards all method calls to mock instance
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array(
            array($this->getMockInstance(), $name),
            $arguments
        );
    }

}