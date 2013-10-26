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
 * @author     Steve Rice <srice@endertech.com>
 * @author     Jonathan Day <jonathan@aligent.com.au>
 */


class EcomDev_PHPUnit_Model_Fixture_Processor_Attributes
    extends Mage_Core_Model_Abstract
    implements EcomDev_PHPUnit_Model_Fixture_ProcessorInterface
{

    const STORAGE_KEY = 'entities';

    // Configuration path for attribute loaders
    const XML_PATH_FIXTURE_ATTRIBUTE_LOADERS = 'phpunit/suite/fixture/attribute';
    // Default attribute loader class node in loaders configuration
    const DEFAULT_ATTRIBUTE_LOADER_NODE = 'default';
    // Default attribute loader class alias
    const DEFAULT_ATTRIBUTE_LOADER_CLASS = 'ecomdev_phpunit/fixture_attribute_default';


    /**
     * Initialize fixture resource model
     */
    protected function _construct()
    {
        $this->_init('ecomdev_phpunit/fixture');
    }

    /**
     * Does nothing
     *
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Attributes
     */
    public function initialize(EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        return $this;
    }

    /**
     * Retrieves attribute loader for a particular attribute type
     *
     * @param string $entityType
     * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_Attribute_Abstract
     */
    protected function _getAttributeLoader($entityType)
    {
        $loaders = Mage::getConfig()->getNode(self::XML_PATH_FIXTURE_ATTRIBUTE_LOADERS);

        if (isset($loaders->$entityType)) {
            $classAlias = (string)$loaders->$entityType;
        } elseif (isset($loaders->{self::DEFAULT_ATTRIBUTE_LOADER_NODE})) {
            $classAlias = (string)$loaders->{self::DEFAULT_ATTRIBUTE_LOADER_NODE};
        } else {
            $classAlias = self::DEFAULT_ATTRIBUTE_LOADER_CLASS;
        }

        return Mage::getResourceSingleton($classAlias);
    }


    /**
     * Apply attribute records from fixture file
     *
     * @param array                                   $data
     * @param string                                  $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Attributes
     */
    public function apply(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        $attributeLoaders = array();

        $this->getResource()->beginTransaction();

        foreach ($data as $entityType => $values) {
            $attributeLoaders[] = $this->_getAttributeLoader($entityType)
                ->setFixture($fixture)
                ->setOptions($fixture->getOptions())
                ->loadAttribute($entityType, $values);
        }

        $this->getResource()->commit();

        foreach ($attributeLoaders as $attributeLoader){
            $attributeLoader->runRequiredIndexers();
        }

        $fixture->setStorageData(self::STORAGE_KEY, array_keys($data));
        return $this;
    }

    /**
     * Discard applied attribute records
     *
     * @param array[] $data
     * @param string $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Attributes
     */
    public function discard(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        $ignoreCleanUp = array();

        // Ignore cleaning of entities if shared fixture loaded something for them
        if ($fixture->isScopeLocal()
            && $fixture->getStorageData(self::STORAGE_KEY,
                                        EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED)) {
            $ignoreCleanUp = $fixture->getStorageData(self::STORAGE_KEY,
                                                      EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED);
        }

        $this->getResource()->beginTransaction();
        foreach (array_keys($data) as $entityType) {
            if (in_array($entityType, $ignoreCleanUp)) {
                continue;
            }
            $this->_getAttributeLoader($entityType)
                ->cleanAttributes($entityType,$data);
        }

        $this->getResource()->commit();
        EcomDev_PHPUnit_Test_Case_Util::replaceRegistry('_singleton/eav/config', null);  //clean out the EAV cache
        return $this;
    }
}