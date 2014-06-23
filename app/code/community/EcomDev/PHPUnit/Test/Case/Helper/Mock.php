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

use EcomDev_PHPUnit_Test_Case_Util as TestUtil;

/**
 * Mock helper for Test Case
 *
 */
class EcomDev_PHPUnit_Test_Case_Helper_Mock extends EcomDev_PHPUnit_AbstractHelper
{
    /**
     * Creates a mockery for a class alias of particular type
     *
     * @param string $type
     * @param string $classAlias
     * @param array $methods
     * @param array $constructorArgs
     *
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function helperMockClassAlias($type, $classAlias, array $methods = array(), array $constructorArgs = array())
    {
        return TestUtil::getGroupedClassMockBuilder($this->testCase, $type, $classAlias)
            ->setConstructorArgs($constructorArgs)
            ->setMethods($methods);
    }

    /**
     * Creates a mock for a model by its class alias
     *
     * @param string $classAlias
     * @param array $methods
     * @param array $constructorArgs
     *
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function helperMockModel($classAlias, array $methods = array(), array $constructorArgs = array())
    {
        return $this->helperMockClassAlias('model', $classAlias, $methods, $constructorArgs);
    }

    /**
     * Creates a mock for a block by its class alias
     *
     * @param string $classAlias
     * @param array $methods
     * @param array $constructorArgs
     *
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function helperMockBlock($classAlias, array $methods = array(), array $constructorArgs = array())
    {
        return $this->helperMockClassAlias('block', $classAlias, $methods, $constructorArgs);
    }

    /**
     * Creates a mock for a block by its class alias
     *
     * @param string $classAlias
     * @param array $methods
     * @param array $constructorArgs
     *
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function helperMockHelper($classAlias, array $methods = array(), array $constructorArgs = array())
    {
        return $this->helperMockClassAlias('helper', $classAlias, $methods, $constructorArgs);
    }
}
