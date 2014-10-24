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

class EcomDev_PHPUnit_Model_Fixture_Processor_Config 
    implements EcomDev_PHPUnit_Model_Fixture_ProcessorInterface
{
    /**
     * Does nothing
     *
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     * @return $this
     */
    public function initialize(EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        return $this;
    }

    /**
     * Apply cache options from the fixture data
     *
     * @param array                                   $data
     * @param string                                  $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return $this
     */
    public function apply(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        $key === 'config_xml' ? $this->_applyConfigXml($data) : $this->_applyConfig($data);
        return $this;
    }

    /**
     * Applies fixture configuration values into Mage_Core_Model_Config
     *
     * @param array $configuration
     * @return $this
     * @throws InvalidArgumentException
     */
    protected function _applyConfig($configuration)
    {
        if (!is_array($configuration)) {
            throw new InvalidArgumentException('Configuration part should be an associative list');
        }

        Mage::getConfig()->loadScopeSnapshot();

        foreach ($configuration as $path => $value) {
            $this->_setConfigNodeValue($path, $value);
        }

        Mage::getConfig()->loadDb();

        // Flush website and store configuration caches
        foreach (Mage::app()->getWebsites(true) as $website) {
            EcomDev_Utils_Reflection::setRestrictedPropertyValue(
                $website, '_configCache', array()
            );
        }
        foreach (Mage::app()->getStores(true) as $store) {
            EcomDev_Utils_Reflection::setRestrictedPropertyValue(
                $store, '_configCache', array()
            );
        }
        return $this;
    }

    /**
     * Applies raw xml data to config node
     *
     * @param array $configuration
     * @return $this
     * @throws InvalidArgumentException
     */
    protected function _applyConfigXml($configuration)
    {
        if (!is_array($configuration)) {
            throw new InvalidArgumentException('Configuration part should be an associative list');
        }

        foreach ($configuration as $path => $value) {
            if (!is_string($value)) {
                throw new InvalidArgumentException('Configuration value should be a valid xml string');
            }
            try {
                $xmlElement = new Varien_Simplexml_Element($value);
            } catch (Exception $e) {
                throw new InvalidArgumentException('Configuration value should be a valid xml string', 0, $e);
            }

            $node = Mage::getConfig()->getNode($path);

            if (!$node) {
                throw new InvalidArgumentException('Configuration value should be a valid xml string');
            }

            $node->extend($xmlElement, true);
        }

        return $this;
    }

    /**
     * Discard applied cache options
     *
     * @param array[] $data
     * @param string $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return $this
     */
    public function discard(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        $this->_restoreConfig();
        return $this;
    }

    /**
     * Restores config to a previous configuration scope
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Config
     */
    protected function _restoreConfig()
    {
        Mage::getConfig()->loadScopeSnapshot();
        Mage::getConfig()->loadDb();
        
        // Flush website and store configuration caches
        foreach (Mage::app()->getWebsites(true) as $website) {
            EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            $website, '_configCache', array()
            );
        }
        foreach (Mage::app()->getStores(true) as $store) {
            EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            $store, '_configCache', array()
            );
        }
        
        return $this;
    }

    /**
     * Sets configuration value
     *
     * @param string $path
     * @param string $value
     *
     * @return $this
     */
    protected function _setConfigNodeValue($path, $value)
    {
        if (($originalNode = Mage::getConfig()->getNode($path)) && $originalNode->getAttribute('backend_model')) {
            $backendModel = $originalNode->getAttribute('backend_model');
            $backend = Mage::getModel((string) $backendModel);
            $backend->setPath($path)->setValue($value);
            EcomDev_Utils_Reflection::invokeRestrictedMethod($backend, '_beforeSave');
            $value = $backend->getValue();
        }

        if (is_array($value)) {
            Mage::throwException(
                sprintf(
                    'There is a collision in configuration value %s. Got: %s',
                    $path,
                    print_r($value, true)
                )
            );
        }
        
        Mage::getConfig()->setNode($path, $value);
        return $this;
    }


}
