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


class EcomDev_PHPUnit_Model_Fixture_Processor_Vfs implements EcomDev_PHPUnit_Model_Fixture_ProcessorInterface
{
    const STORAGE_KEY = 'vfs';

    /**
     * Does nothing
     *
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Cache
     */
    public function initialize(EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        return $this;
    }

    /**
     * Apply virtual file system structure
     *
     * @param array                                   $data
     * @param string                                  $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Cache
     */
    public function apply(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        if ($fixture->isScopeLocal()
            && ($parentData = $fixture->getStorageData(self::STORAGE_KEY,
                                                       EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED))) {
            $data = array_merge_recursive($parentData, $data);
        }

        $fixture->getVfs()->apply($data);
        $fixture->setStorageData(self::STORAGE_KEY, $data);

        return $this;
    }

    /**
     * Discard applied virtual file system structure
     *
     * @param array[] $data
     * @param string $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Cache
     */
    public function discard(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        $fixture->getVfs()->discard();
        $fixture->setStorageData(self::STORAGE_KEY, null);
        return $this;
    }
}