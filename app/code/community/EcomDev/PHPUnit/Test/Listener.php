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

class EcomDev_PHPUnit_Test_Listener implements PHPUnit_Framework_TestListener
{
    const XML_PATH_UNIT_TEST_APP = 'phpunit/suite/app/class';

    /**
     * First level test suite that is used
     * for running all the tests
     *
     * @var PHPUnit_Framework_TestSuite
     */
    protected $firstLevelTestSuite = null;

    /**
     * Returns app reflection instance
     *
     * @return ReflectionClass|ReflectionObject
     */
    protected function getAppReflection()
    {
        $appClass = (string) Mage::getConfig()->getNode(self::XML_PATH_UNIT_TEST_APP);
        $reflectionClass = EcomDev_Utils_Reflection::getReflection($appClass);

        return $reflectionClass;
    }

}