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

class EcomDev_PHPUnit_Model_Fixture_Processor_Cache implements EcomDev_PHPUnit_Model_Fixture_ProcessorInterface
{
    const STORAGE_KEY = 'cache_options';

    /**
     * Initializes cache options
     *
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Cache
     */
    public function initialize(EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        $options = $fixture->getOptions();
        if (isset($options['cache'])) {
            $cacheOptions = array();
            foreach ($options['cache'] as $annotation) {
                list($action, $cacheType) = preg_split('/\s+/', trim($annotation));
                $flag = ($action === 'off' ? 0 : 1);
                if ($cacheType === 'all') {
                    foreach (Mage::app()->getCacheInstance()->getTypes() as $type) {
                        $cacheOptions[$type->getId()] = $flag;
                    }
                } else {
                    $cacheOptions[$cacheType] = $flag;
                }
            }

            $fixture->setFixtureValue('cache_options', $cacheOptions);
        }

        return $this;
    }

    /**
     * Apply cache options from the fixture data
     *
     * @param array                                   $data
     * @param string                                  $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Cache
     */
    public function apply(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        $originalOptions = EcomDev_PHPUnit_Test_Case_Util::app()->getCacheOptions();
        $fixture->setStorageData(self::STORAGE_KEY, $originalOptions);

        $data += $originalOptions;
        EcomDev_PHPUnit_Test_Case_Util::app()->setCacheOptions($data);
        return $this;
    }

    /**
     * Discard applied cache options
     *
     * @param array[] $data
     * @param string $key
     * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Processor_Cache
     */
    public function discard(array $data, $key, EcomDev_PHPUnit_Model_FixtureInterface $fixture)
    {
        EcomDev_PHPUnit_Test_Case_Util::app()->setCacheOptions(
            $fixture->getStorageData(self::STORAGE_KEY)
        );
        return $this;
    }
}