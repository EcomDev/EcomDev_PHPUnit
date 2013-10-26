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
 */

abstract class EcomDev_PHPUnit_Model_Mysql4_Fixture_AbstractComplex 
    extends EcomDev_PHPUnit_Model_Mysql4_Fixture
{
	/**
	 * Fixture options
	 *
	 * @var array
	 */
	protected $_options = array();

	/**
	 * Fixture model
	 *
	 * @var EcomDev_PHPUnit_Model_FixtureInterface
	 */
	protected $_fixture = null;

	/**
	 * Inject fixture model into complex loader
	 *
	 * @param EcomDev_PHPUnit_Model_FixtureInterface $fixture
	 * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_AbstractComplex
	 */
	public function setFixture($fixture)
	{
		$this->_fixture = $fixture;
		return $this;
	}

	/**
	 * Set fixture options
	 *
	 * @param array $options
	 * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_AbstractComplex
	 */
	public function setOptions(array $options)
	{
		$this->_options = $options;
		return $this;
	}
}
