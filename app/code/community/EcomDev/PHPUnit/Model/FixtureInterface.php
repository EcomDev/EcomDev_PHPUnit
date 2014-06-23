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
 * Interface for fixture model
 * Can be used for creation of
 * absolutely different implementation of fixture,
 * then current one.
 *
 */
interface EcomDev_PHPUnit_Model_FixtureInterface 
    extends EcomDev_PHPUnit_Model_LoadableInterface
{
    /** Local scope - used for fixtures that apply only to the current test */
    const SCOPE_LOCAL = 'local';
    /** Shared scope - used for fixtures that apply to the current test class */
    const SCOPE_SHARED = 'shared';
    /** Default scope - used for storing data that exists in database before tests are run */
    const SCOPE_DEFAULT = 'default';

    /**
     * Sets fixture options
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options);

    /**
     * Sets fixture options
     *
     * @return array
     */
    public function getOptions();

    /**
     * Sets storage for fixtures
     *
     * @param Varien_Object $storage
     * @return $this
     */
    public function setStorage(Varien_Object $storage);

    /**
     * Retrieve fixture storage
     *
     * @return Varien_Object
     */
    public function getStorage();

    /**
     * Retrieves storage data for a particular fixture scope
     *
     * @param string $key
     * @param string|null $scope
     */
    public function getStorageData($key, $scope = null);


    /**
     * Sets storage data for a particular fixture scope
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $scope
     */
    public function setStorageData($key, $value, $scope = null);

    /**
     * Returns current fixture scope
     *
     * @return string
     */
    public function getScope();

    /**
     * Sets current fixture scope
     *
     *
     * @param string $scope EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_LOCAL|EcomDev_PHPUnit_Model_FixtureInterface::SCOPE_SHARED
     */
    public function setScope($scope);

    /**
     * Check that current fixture scope is equal to SCOPE_DEFAULT
     *
     * @abstract
     * @return boolean
     */
    public function isScopeDefault();

    /**
     * Check that current fixture scope is equal to SCOPE_SHARED
     *
     * @return boolean
     */
    public function isScopeShared();

    /**
     * Check that current fixture scope is equal to SCOPE_LOCAL
     *
     * @return boolean
     */
    public function isScopeLocal();

    /**
     * Loads fixture files from test class annotations
     *
     * @param string $className
     * @return $this
     */
    public function loadForClass($className);

    /**
     * Sets fixture value
     *
     * @param string $key
     * @param array[] $value
     *
     * @return $this
     */
    public function setFixtureValue($key, $value);

    /**
     * Retrieves fixture value
     *
     * @param string $key
     *
     * @return array[]
     */
    public function getFixtureValue($key);

    /**
     * Returns VFS wrapper instance
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Vfs
     */
    public function getVfs();
}
