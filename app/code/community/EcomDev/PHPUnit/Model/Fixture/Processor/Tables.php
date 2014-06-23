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


class EcomDev_PHPUnit_Model_Fixture_Processor_Tables
    extends Mage_Core_Model_Abstract
    implements EcomDev_PHPUnit_Model_Fixture_ProcessorInterface
{

    const STORAGE_KEY = 'tables';

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
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Tables
     */
    public function initialize(EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        return $this;
    }

    /**
     * Apply tables records from fixture file
     *
     * @param array                                   $data
     * @param string                                  $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Tables
     */
    public function apply(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {

        $ignoreCleanUp = array();

        // Ignore cleaning of tables if shared fixture loaded something
        if ($fixture->isScopeLocal()
            && $fixture->getStorageData(self::STORAGE_KEY,
                                        EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED)) {
            $ignoreCleanUp = array_keys($fixture->getStorageData(self::STORAGE_KEY,
                                        EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED));
        }

        $this->getResource()->beginTransaction();
        foreach (array_reverse(array_keys($data)) as $tableEntity) {
            if (!in_array($tableEntity, $ignoreCleanUp)) {
                $this->getResource()->cleanTable($tableEntity);
            }
        }
        foreach ($data as $tableEntity => $tableData) {
            if (!empty($tableData)) {
                $this->getResource()->loadTableData($tableEntity, $tableData);
            }
        }
        $this->getResource()->commit();
        $fixture->setStorageData(self::STORAGE_KEY, $data);
        return $this;
    }

    /**
     * Discard applied table records
     *
     * @param array[] $data
     * @param string $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Tables
     */
    public function discard(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        $restoreTableData = array();

        // Data for tables used in shared fixture
        if ($fixture->isScopeLocal()
            && $fixture->getStorageData(self::STORAGE_KEY,
                                        EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED)) {
            $restoreTableData = $fixture->getStorageData(self::STORAGE_KEY,
                                                         EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED);
        }
        $this->getResource()->beginTransaction();

        foreach (array_reverse(array_keys($data)) as $tableEntity) {
            $this->getResource()->cleanTable($tableEntity);
        }
        foreach (array_keys($data) as $tableEntity) {
            if (isset($restoreTableData[$tableEntity])) {
                $this->getResource()->loadTableData($tableEntity, $restoreTableData[$tableEntity]);
            }
        }

        $this->getResource()->commit();
        $fixture->setStorageData(self::STORAGE_KEY, null);
        return $this;
    }
}
