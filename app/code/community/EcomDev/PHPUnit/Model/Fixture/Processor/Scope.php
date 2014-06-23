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

class EcomDev_PHPUnit_Model_Fixture_Processor_Scope
    implements EcomDev_PHPUnit_Model_Fixture_ProcessorInterface
{
    const STORAGE_KEY = 'scope';

    /**
     * Model aliases by type of scope
     *
     * @var array
     */
    protected $modelByType = array(
        'store' => 'core/store',
        'group' => 'core/store_group',
        'website' => 'core/website'
    );

    /**
     * Does nothing
     *
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Scope
     */
    public function initialize(EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        return $this;
    }

    /**
     * Handle scope row data
     *
     * @param string $type
     * @param array $row
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     * @return boolean|Mage_Core_Model_Abstract
     */
    protected function _handleScopeRow($type, $row, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        $previousScope = array();

        if ($fixture->isScopeLocal()
            && $fixture->getStorageData(self::STORAGE_KEY,
                                        EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED) !== null) {
            $previousScope = $fixture->getStorageData(self::STORAGE_KEY,
                                                      EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED);
        }

        if (isset($previousScope[$type][$row[$type . '_id']])) {
            return false;
        }

        $scopeModel = Mage::getModel($this->modelByType[$type]);
        $scopeModel->setData($row);

        // Change property for saving new objects with specified ids
        EcomDev_Utils_Reflection::setRestrictedPropertyValues(
            $scopeModel->getResource(),
            array(
                '_isPkAutoIncrement' => false
            )
        );

        try {
            $scopeModel->isObjectNew(true);
            $scopeModel->save();
        } catch (Exception $e) {
            Mage::logException($e);
            // Skip duplicated key violation, since it might be a problem
            // of previous run with fatal error
            // Revert changed property
            EcomDev_Utils_Reflection::setRestrictedPropertyValues(
                $scopeModel->getResource(),
                array(
                    '_isPkAutoIncrement' => true
                )
            );
            // Load to make possible deletion
            $scopeModel->load($row[$type . '_id']);
        }

        // Revert changed property
        EcomDev_Utils_Reflection::setRestrictedPropertyValues(
            $scopeModel->getResource(),
            array(
                '_isPkAutoIncrement' => true
            )
        );

        return $scopeModel;
    }

    /**
     * Validate scope data
     *
     * @param array $types
     * @throws RuntimeException
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Scope
     */
    protected function _validateScope($types)
    {
        foreach ($types as $type => $rows) {
            if (!isset($this->modelByType[$type])) {
                throw new RuntimeException(sprintf('Unknown "%s" scope type specified', $type));
            }

            foreach ($rows as $rowNumber => $row) {
                if (!isset($row[$type . '_id'])) {
                    throw new RuntimeException(sprintf('Missing primary key for "%s" scope entity at #%d row', $type, $rowNumber + 1));
                }
            }
        }

        return $this;
    }

    /**
     * Apply scoped data (store, website, group)
     *
     * @param array                                   $data
     * @param string                                  $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @throws RuntimeException
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Scope
     */
    public function apply(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        EcomDev_PHPUnit_Test_Case_Util::app()->disableEvents();
        // Validate received fixture data
        $this->_validateScope($data);

        if ($fixture->getStorageData(self::STORAGE_KEY) !== null) {
            throw new RuntimeException('Scope data was not cleared after previous test');
        }

        $scopeModels = array();

        foreach ($data as $type => $rows) {
            foreach ($rows as $row) {
                $model = $this->_handleScopeRow($type, $row, $fixture);
                if ($model) {
                    $scopeModels[$type][$model->getId()] = $model;
                }
            }
        }

        $fixture->setStorageData(self::STORAGE_KEY, $scopeModels);

        EcomDev_PHPUnit_Test_Case_Util::app()->enableEvents();
        EcomDev_PHPUnit_Test_Case_Util::app()->reinitStores();
        return $this;
    }

    /**
     * Discard applied scope models
     *
     * @param array[] $data
     * @param string $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Scope
     */
    public function discard(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        if ($fixture->getStorageData(self::STORAGE_KEY) === null) {
            return $this;
        }

        EcomDev_PHPUnit_Test_Case_Util::app()->disableEvents();
        $scope = array_reverse($fixture->getStorageData(self::STORAGE_KEY));
        foreach ($scope as $models) {
            foreach ($models as $model) {
                $model->delete();
            }
        }

        $fixture->setStorageData(self::STORAGE_KEY, null);

        EcomDev_PHPUnit_Test_Case_Util::app()->getCache()->clean(
            Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG,
            array(
                Mage_Core_Model_Store::CACHE_TAG,
                Mage_Core_Model_Store_Group::CACHE_TAG,
                Mage_Core_Model_Website::CACHE_TAG
            )
        );

        EcomDev_PHPUnit_Test_Case_Util::app()->enableEvents();
        EcomDev_PHPUnit_Test_Case_Util::app()->reinitStores();
        return $this;
    }
}