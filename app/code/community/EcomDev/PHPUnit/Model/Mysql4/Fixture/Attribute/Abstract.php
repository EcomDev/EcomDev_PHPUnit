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
 * @copyright  Copyright (c) 2012 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 * @author     Steve Rice <srice@endertech.com>
 */

abstract class EcomDev_PHPUnit_Model_Mysql4_Fixture_Attribute_Abstract
	extends EcomDev_PHPUnit_Model_Mysql4_Fixture_Complex_Abstract
{
	protected $_setupModel = 'Mage_Eav_Model_Entity_Setup';

	/**
	 * @param string $entityType
	 * @return array
	 */
	public function loadDefaultAttributes($entityType)
	{
		/** @var $eavConfig Mage_Eav_Model_Config */
		$eavConfig = Mage::getSingleton('eav/config');

		$attributeCodes = $eavConfig->getEntityAttributeCodes($entityType);

		return $attributeCodes;
	}

	/**
	 * Loads EAV attribute into DB tables
	 *
	 * @throws UnexpectedValueException
	 * @throws InvalidArgumentException
	 * @param string $entityType
	 * @param array $values
	 */
	public function loadAttribute($entityType, $values)
	{
		/** @var $eavConfig Mage_Eav_Model_Config */
		$eavConfig = Mage::getSingleton('eav/config');

		/** @var $entityTypeModel Mage_Eav_Model_Entity_Type */
		$entityTypeModel = $eavConfig->getEntityType($entityType);

		$entityModel = $entityTypeModel->getEntity();

		//use entity model to figure out setup class
		$entityReflection = EcomDev_Utils_Reflection::getRelflection($entityModel);
		$classArray = explode('_', $entityReflection->getName());
		$moduleName = $classArray[0] . '_' . $classArray[1];

		$eavSetupModel = $this->_getSetupModelForModule($moduleName);

		foreach ($values as $value) {
			if (!isset($value['attribute_code'])) {
				throw new InvalidArgumentException('Attribute definition must contain attribute_code');
			}

			/** @var $eavSetupModel Mage_Eav_Model_Entity_Setup */
			$eavSetupModel->addAttribute($entityTypeModel->getEntityTypeCode(), $value['attribute_code'], $value);
		}
	}

	/**
	 * Remove fixture-generated attributes from database
	 *
	 * @param string $entityType
	 * @param array $attributeCodes
	 * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_Attribute_Abstract
	 * @throws EcomDev_PHPUnit_Model_Mysql4_Fixture_Exception
	 */
	public function cleanAttributes($entityType, array $attributeCodes)
	{
		$eavSetup = new Mage_Eav_Model_Entity_Setup('core_setup');

		try {
			//delete entry from eav/attribute and allow FK cascade to delete all related values
			$this->_getWriteAdapter()
				->delete(
					$this->getTable('eav/attribute'),
					array(
						'entity_type_id = ?'    => $eavSetup->getEntityTypeId($entityType),
						'attribute_code IN (?)' => $attributeCodes,
					)
				);
			$this->_getWriteAdapter()->commit();
		} catch (Exception $e) {
			throw new EcomDev_PHPUnit_Model_Mysql4_Fixture_Exception(
				sprintf('Unable to clear records for a table "%s"', 'eav/attribute'),
				$e
			);
		}

		return $this;
	}

	/**
	 * Reset autoincrement value of all EAV attribute tables or those associated with an entity type
	 *
	 * @throws EcomDev_PHPUnit_Model_Mysql4_Fixture_Exception
	 * @param string $entityType
	 * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_Attribute_Abstract
	 */
	public function resetAttributesAutoIncrement($entityType = null)
	{
		//@TODO track which tables are altered

		if ($entityType !== null) {
			/** @var $eavConfig Mage_Eav_Model_Config */
			$eavConfig = Mage::getSingleton('eav/config');
			/** @var $entityTypeModel Mage_Eav_Model_Entity_Type */
			$entityTypeModel = $eavConfig->getEntityType($entityType);
			$this->resetTableAutoIncrement($entityTypeModel->getAdditionalAttributeTable());
		} else {
			//@TODO don't hardcode these
			$this->resetTableAutoIncrement('eav/attribute');
			$this->resetTableAutoIncrement('eav/attribute_set');
			$this->resetTableAutoIncrement('eav/attribute_group');
			$this->resetTableAutoIncrement('eav/attribute_label');
			$this->resetTableAutoIncrement('eav/attribute_option');
			$this->resetTableAutoIncrement('eav/attribute_option_value');
		}

		return $this;
	}

	/**
	 * Reset autoincrement value of a table
	 *
	 * @param string $table
	 * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_Attribute_Abstract
	 * @throws EcomDev_PHPUnit_Model_Mysql4_Fixture_Exception
	 */
	public function resetTableAutoIncrement($table)
	{
		try {
			//reset table auto_increment to maximum value in table
			$this->_getWriteAdapter()->query("ALTER TABLE `{$this->getTable($table)}` AUTO_INCREMENT = 1");
		} catch (Exception $e) {
			throw new EcomDev_PHPUnit_Model_Mysql4_Fixture_Exception(
				sprintf('Unable to reset autoincrement for table "%s"', $table),
				$e
			);
		}
		return $this;
	}

	/**
	 * Get the setup model used by a Magento module
	 *
	 * @param $moduleName
	 * @return mixed
	 * @throws UnexpectedValueException
	 */
	protected function _getSetupModelForModule($moduleName)
	{
		$resources = Mage::getConfig()->getNode('global/resources')->children();
		$resourceName = 'eav_setup';
		$className = 'Mage_Eav_Model_Entity_Setup';

		foreach ($resources as $resName => $resource) {
			if (!$resource->setup) {
				continue;
			}
			if (isset($resource->setup->module) && $resource->setup->module == $moduleName
				&& isset($resource->setup->class)) {
				$className = $resource->setup->getClassName();
				$resourceName = $resName;
				break;
			}
		}

		$setupModel = new $className($resourceName);

		$setupReflection = EcomDev_Utils_Reflection::getRelflection($setupModel);

		if (!$setupReflection->hasMethod('addAttribute')) {
			throw new UnexpectedValueException('Problem loading EAV setup model');
		}

		return $setupModel;
	}
}
