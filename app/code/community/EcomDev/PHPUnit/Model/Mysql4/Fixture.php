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
 * @copyright  Copyright (c) 2011 Ecommerce Developers (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Fixture resource model.
 *
 * Created for direct operations with DB.
 *
 */
class EcomDev_PHPUnit_Model_Mysql4_Fixture extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_setResource('ecomdev_phpunit');
    }

    /**
     * Cleans table in test database
     *
     * @param string $tableEntity
     * @return EcomDev_PHPUnit_Model_Mysql4_Fixture
     */
    public function cleanTable($tableEntity)
    {
        $this->_getWriteAdapter()
            ->truncate($this->getTable($tableEntity));
        return $this;
    }

    /**
     * Loads multiple data rows into table
     *
     * @param string $tableEntity
     * @param array $tableData
     */
    public function loadTableData($tableEntity, $tableData)
    {
        $tableColumns = $this->_getWriteAdapter()
            ->describeTable($this->getTable($tableEntity));

        $records = array();
        foreach ($tableData as $row) {
            $records[] = $this->_getTableRecord($row, $tableColumns);
        }

        $this->_getWriteAdapter()->insertMultiple(
            $this->getTable($tableEntity),
            $records
        );

        return $this;
    }

	/**
     * Prepares entity table record from array
     *
     * @param array $row
     * @param array $tableColumns list of entity_table columns
     * @return array
     */
    protected function _getTableRecord($row, $tableColumns)
    {
        $record = array();

        // Fullfil table records with data
        foreach ($tableColumns as $columnName => $definition) {
            if (isset($row[$columnName])) {
                $record[$columnName] = $this->_getTableRecordValue($row[$columnName]);
            } elseif ($definition['DEFAULT'] !== null) {
                $record[$columnName] = $definition['DEFAULT'];
            } else {
                $record[$columnName] = (($definition['NULLABLE']) ? null : '');
            }
        }

        return $record;
    }

    /**
     * Processes table record values,
     * used for transforming custom values like serialized
     * or JSON data
     *
     *
     * @param mixed $value
     * @return string
     */
    protected function _getTableRecordValue($value)
    {
        // If it is scalar php type, then just return itself
        if (!is_array($value)) {
            return $value;
        }

        if (isset($value['json'])) {
            return Mage::helper('core')->jsonEncode($value['json']);
        }

        if (isset($value['serialized'])) {
            return serialize($value['serialized']);
        }

        throw new InvalidArgumentException('Unrecognized type for DB column');
    }
}