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


class EcomDev_PHPUnit_Model_Fixture_Processor_Eav
    extends Mage_Core_Model_Abstract
    implements EcomDev_PHPUnit_Model_Fixture_ProcessorInterface
{

    const STORAGE_KEY = 'entities';

    // Configuration path for eav loaders
    const XML_PATH_FIXTURE_EAV_LOADERS = 'phpunit/suite/fixture/eav';
    // Default eav loader class node in loaders configuration
    const DEFAULT_EAV_LOADER_NODE = 'default';
    // Default eav loader class alias
    const DEFAULT_EAV_LOADER_CLASS = 'ecomdev_phpunit/fixture_eav_default';


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
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Eav
     */
    public function initialize(EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        return $this;
    }

    /**
     * Retrieves eav loader for a particular entity type
     *
     * @param string $entityType
     * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_AbstractEav
     */
    protected function _getEavLoader($entityType)
    {
        $loaders = Mage::getConfig()->getNode(self::XML_PATH_FIXTURE_EAV_LOADERS);

        if (isset($loaders->$entityType)) {
            $classAlias = (string)$loaders->$entityType;
        } elseif (isset($loaders->{self::DEFAULT_EAV_LOADER_NODE})) {
            $classAlias = (string)$loaders->{self::DEFAULT_EAV_LOADER_NODE};
        } else {
            $classAlias = self::DEFAULT_EAV_LOADER_CLASS;
        }

        return Mage::getResourceSingleton($classAlias);
    }


    /**
     * Apply eav records from fixture file
     *
     * @param array                                   $data
     * @param string                                  $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Eav
     */
    public function apply(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        $eavLoaders = array();

        $this->getResource()->beginTransaction();

        foreach ($data as $entityType => $values) {
            $eavLoaders[] = $this->_getEavLoader($entityType)
                ->setFixture($fixture)
                ->setOptions($fixture->getOptions())
                ->loadEntity($entityType, $values);
        }

        $this->getResource()->commit();

        foreach ($eavLoaders as $eavLoader){
            $eavLoader->runRequiredIndexers();
        }

        $fixture->setStorageData(self::STORAGE_KEY, array_keys($data));
        return $this;
    }

    /**
     * Discard applied eav records
     *
     * @param array[] $data
     * @param string $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Eav
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
            $this->_getEavLoader($entityType)
                ->cleanEntity($entityType);
        }

        $this->getResource()->commit();
        return $this;
    }
}