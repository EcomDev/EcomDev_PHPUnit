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
 * Registry fixture, works great for resetting singletons and other single instances in Magento
 *
 * Can be used by specifying annotations:
 * @singleton catalog/product_type
 * @resource catalog/product
 * @helper catalog
 * @registry key
 *
 * or by specifying it in Yaml file:
 *
 * registry:
 *  singleton:
 *     - catalog/product_type
 *  resource:
 *     - catalog/product
 *  helper
 *     - catalog
 *     - core/url
 *
 */
class EcomDev_PHPUnit_Model_Fixture_Processor_Registry 
    implements EcomDev_PHPUnit_Model_Fixture_ProcessorInterface
{
    const STORAGE_KEY = 'registry';

    /**
     * Clears singletons before tests
     *
     * @param array[]                                 $data
     * @param string                                  $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Registry
     * @throws RuntimeException
     */
    public function apply(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        $typeToKey = array(
            'singleton' => '_singleton/',
            'resource'  => '_resource_singleton/',
            'helper'    => '_helper/',
            'registry'  => ''
        );

        if ($fixture->getStorageData(self::STORAGE_KEY) !== null) {
            throw new RuntimeException('Registry data was not cleared after previous test');
        }

        $oldRegistry = array();

        foreach ($data as $type => $keys) {
            if (!isset($typeToKey[$type])) {
                continue;
            }

            foreach ($keys as $key) {
                // Preserve old registry value
                $oldRegistry[$typeToKey[$type] . $key] = Mage::registry($typeToKey[$type] . $key);
                // Set new value to registry
                EcomDev_PHPUnit_Test_Case_Util::app()->replaceRegistry($typeToKey[$type] . $key, null);
            }
        }

        $fixture->setStorageData(self::STORAGE_KEY, $oldRegistry);
        return $this;
    }

    /**
     * Sets back old singletons after tests
     *
     * @param array[]                                 $data
     * @param string                                  $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_ProcessorInterface
     */
    public function discard(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        if ($fixture->getStorageData(self::STORAGE_KEY) === null) {
            return $this;
        }

        $oldRegistry = $fixture->getStorageData(self::STORAGE_KEY);
        foreach ($oldRegistry as $key => $value) {
            // Set old value to registry
            EcomDev_PHPUnit_Test_Case_Util::app()->replaceRegistry($key, $value);
        }

        $fixture->setStorageData(self::STORAGE_KEY, null);
        return $this;
    }

    /**
     * Initializes fixture processor before applying data
     *
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Registry
     */
    public function initialize(EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        $options = $fixture->getOptions();
        $registry = array();
        foreach (array('singleton', 'resource', 'helper', 'registry') as $type) {
            if (!isset($options[$type])) {
                continue;
            }

            foreach ($options[$type] as $name) {
                $registry[$type][] = $name;
            }
        }

        if ($registry) {
            $fixture->setFixtureValue('registry', $registry);
        }

        return $this;
    }
}