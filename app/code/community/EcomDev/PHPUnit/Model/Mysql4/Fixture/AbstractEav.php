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
 * Base implementation of EAV fixtures loader
 *
 */
abstract class EcomDev_PHPUnit_Model_Mysql4_Fixture_AbstractEav
	extends EcomDev_PHPUnit_Model_Mysql4_Fixture_AbstractComplex
    implements EcomDev_PHPUnit_Model_Mysql4_Fixture_RestoreAwareInterface
{
    const RESTORE_KEY = 'restore_%s_data';
    
    /**
     * List of indexers required to build
     *
     * @var array
     */
    protected $_requiredIndexers = array();

    /**
     * Original list of indexers required to build
     *
     * @var array
     */
    protected $_originalIndexers = array();

    /**
     * List of tables that should be restored after run
     * 
     * @var string[]
     */
    protected $_restoreTables = array();

    /**
     * Default data for eav entity
     * 
     * @var array
     */
    protected $_defaultData = array();

    /**
     * Retrieve required indexers for re-building
     *
     * @return array
     */
    public function getRequiredIndexers()
    {
        return $this->_requiredIndexers;
    }

    /**
     * Run required indexers and reset to original required indexers
     *
     * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_AbstractEav
     */
    public function runRequiredIndexers()
    {
        if (empty($this->_options['doNotIndexAll'])) {
            $indexer = Mage::getSingleton('index/indexer');
            foreach ($this->getRequiredIndexers() as $indexerCode) {
                if (empty($this->_options['doNotIndex']) || !in_array($indexerCode, $this->_options['doNotIndex'])) {
                    $process = $indexer->getProcessByCode($indexerCode);
                    if ($process) {
                        $process->reindexAll();
                    }
                }
            }
        }

        // Restoring original required indexers for making tests isolated
        $this->_requiredIndexers = $this->_originalIndexers;
        return $this;
    }

    /**
     * Add indexer by specific code to required indexers list
     *
     * @param string $code
     * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_AbstractEav
     */
    public function addRequiredIndexer($code)
    {
        if (!in_array($code, $this->_requiredIndexers)) {
            $this->_requiredIndexers[] = $code;
        }
        return $this;
    }

    /**
     * Clean entity data table
     *
     * @param string $entityType
     * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_AbstractEav
     */
    public function cleanEntity($entityType)
    {
	    /** @var $entityTypeModel Mage_Eav_Model_Entity_Type */
        $entityTypeModel = Mage::getSingleton('eav/config')->getEntityType($entityType);
        $this->cleanTable($entityTypeModel->getEntityTable());
        return $this;
    }


    /**
     * Saves data for restoring it after fixture has been cleaned up
     *
     * @param string $code storage code
     * @return $this
     */
    public function saveData($code)
    {
        if ($this->_restoreTables) {
            $storageKey = sprintf(self::RESTORE_KEY, $code);
            $data = array();
            foreach ($this->_restoreTables as $table) {
                $select = $this->_getReadAdapter()->select();
                $select->from($table);
                $data[$table] = $this->_getReadAdapter()->fetchAll($select);
            }
            $this->_fixture->setStorageData($storageKey, $data);
        }
        
        return $this;
    }
    
    /**
     * Restored saved data
     *
     * @param string $code storage code
     * @return $this
     */
    public function restoreData($code)
    {
        if ($this->_restoreTables) {
            $storageKey = sprintf(self::RESTORE_KEY, $code);
            $data = $this->_fixture->getStorageData($storageKey);
            foreach ($this->_restoreTables as $table) {
                if (!empty($data[$table])) {
                    $this->_getWriteAdapter()->insertOnDuplicate(
                        $table,
                        $data[$table]
                    );
                }
            }
        }
        
        return $this;
    }

    /**
     * Clears storage from stored backup data
     *
     * @param $code
     * @return $this
     */
    public function clearData($code)
    {
        if ($this->_restoreTables) {
            $storageKey = sprintf(self::RESTORE_KEY, $code);
            $this->_fixture->setStorageData($storageKey, array());
        }
        
        return $this;
    }

    /**
     * Loads EAV data into DB tables
     *
     * @param string $entityType
     * @param array $values
     * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_AbstractEav
     * @throws RuntimeException
     */
    public function loadEntity($entityType, $values)
    {
        $this->_originalIndexers = $this->_requiredIndexers;
        if (!empty($this->_options['addRequiredIndex'])) {
            foreach ($this->_options['addRequiredIndex'] as $data) {
                if (preg_match('/^([a-z0-9_\\-]+)\\s+([a-z0-9_\\-]+)\s*$/i', $data, $match)
                    && $match[1] == $entityType) {
                    $this->_requiredIndexers[] = $match[2];
                }
            }
        }

	    /** @var $entityTypeModel Mage_Eav_Model_Entity_Type */
        $entityTypeModel = Mage::getSingleton('eav/config')->getEntityType($entityType);


        $entityTableColumns = $this->_getWriteAdapter()->describeTable(
            $this->getTable($entityTypeModel->getEntityTable())
        );

        $attributeTableColumns = $this->_getAttributeTablesColumnList($entityTypeModel);


        $entities = array();
        $entityValues = array();

        // Custom values array is used for
        // inserting custom entity data in custom tables.
        // It is an associative array with table name as key,
        // and rows list as value
        // See getCustomTableRecords
        $customValues = array();
        
        if ($this->_defaultData) {
            $dataToInsert = $this->_defaultData;
            // Prevent insertion of default data, 
            // if there is already data available
            foreach ($values as $index => $row) {
                if (isset($row[$this->_getEntityIdField($entityTypeModel)]) 
                    && isset($dataToInsert[$this->_getEntityIdField($entityTypeModel)])) {
                    $dataToInsert = array();
                    break;
                }
            }
            
            foreach ($dataToInsert as $row) {
                array_unshift($values, $row);
            }
        }
        

        foreach ($values as $index => $row) {
            if (!isset($row[$this->_getEntityIdField($entityTypeModel)])) {
                throw new RuntimeException('Entity Id should be specified in EAV fixture');
            }

            // Fulfill necessary information
            $values[$index]['entity_type_id'] = $entityTypeModel->getEntityTypeId();
            $row = $values[$index]; 
            
            if (!isset($row['attribute_set_id'])) {
                $defaultAttributeSet = $entityTypeModel->getDefaultAttributeSetId();
                
                // Fix Magento core issue with attribute set information for customer and its address
                if (in_array($entityType, array('customer', 'customer_address'))) {
                    $defaultAttributeSet = 0;
                }
                
                $values[$index]['attribute_set_id'] = $defaultAttributeSet;
            }

            // Preparing entity table record
            $entity = $this->_getTableRecord($row, $entityTableColumns);
            $entities[] = $entity;

            // Preparing simple attributes records
            foreach ($entityTypeModel->getAttributeCollection() as $attribute) {
	            /** @var $attribute Mage_Eav_Model_Entity_Attribute */
                $attributeBackendTable = $attribute->getBackendTable();
                if (!$attribute->isStatic()
                    && $attributeBackendTable
                    && isset($attributeTableColumns[$attributeBackendTable])) {

                    // Preparing data for insert per attribute table
                    $attributeRecords = $this->_getAttributeRecords(
                        $row,
                        $attribute,
                        $attributeTableColumns[$attributeBackendTable]
                    );

                    if ($attributeRecords) {
                        if (!isset($entityValues[$attributeBackendTable])) {
                            $entityValues[$attributeBackendTable] = array();
                        }

                        $entityValues[$attributeBackendTable] = array_merge(
                            $entityValues[$attributeBackendTable],
                            $attributeRecords
                        );
                    }
                }
            }

            // Processing custom entity values
            $customValues = array_merge_recursive(
                $customValues,
                $this->_getCustomTableRecords($row, $entityTypeModel)
            );
        }

        $this->_getWriteAdapter()->insertOnDuplicate(
            $this->getTable($entityTypeModel->getEntityTable()),
            $entities
        );

        foreach ($entityValues as $tableName => $records) {
            $this->_getWriteAdapter()->insertOnDuplicate(
                $tableName,
                $records
            );
        }

        foreach ($customValues as $tableName => $records) {
            $this->_getWriteAdapter()->insertOnDuplicate(
                (strpos($tableName, '/') !== false ? $this->getTable($tableName) : $tableName),
                $records
            );
        }

        foreach ($entities as $entity) {
            $this->_customEntityAction($entity, $entityTypeModel);
        }

        return $this;
    }


    /**
     * Performs custom action on entity
     *
     * @param array $entity
     * @param Mage_Eav_Model_Entity_Type $entityTypeModel
     * @return EcomDev_PHPUnit_Model_Mysql4_Fixture_AbstractEav
     */
    protected function _customEntityAction($entity, $entityTypeModel)
    {
        return $this;
    }

    /**
     * If you have some custom EAV tables,
     * this method will help you to insert
     * them on fixture processing step
     * It should return an associative array, where an entry key
     * is the table name and its value is a list of table rows
     *
     * @example
     * return array(
     *    'some/table' => array(
     *        array(
     *            'field' => 'value'
     *        )
     *    )
     * )
     *
     * @param array $row
     * @param Mage_Eav_Model_Entity_Type $entityTypeModel
     * @return array
     */
    protected function _getCustomTableRecords($row, $entityTypeModel)
    {
        return array();
    }

    /**
     * Retrieves associative list of attribute tables and their columns
     *
     * @param Mage_Eav_Model_Entity_Type $entityTypeModel
     * @return array
     */
    protected function _getAttributeTablesColumnList($entityTypeModel)
    {
        $tableNames = array_unique(
            $entityTypeModel->getAttributeCollection()
                ->walk('getBackendTable')
        );

        $columnsByTable = array();

        foreach ($tableNames as $table) {
            if ($table) {
                $columnsByTable[$table] = $this->_getWriteAdapter()
                    ->describeTable(
                        $table
                    );
            }
        }

        return $columnsByTable;
    }


    /**
     * Retrieves attribute records for single entity
     *
     * @param array $row
     * @param array $attribute
     * @param $tableColumns
     * @return array
     * @internal param \Mage_Eav_Model_Entity_Type $entityTypeModel
     */
    protected function _getAttributeRecords($row, $attribute, $tableColumns)
    {
        $records = array();

        $value = $this->_getAttributeValue($row, $attribute);

        if ($value !== null) {
            $valueInfo = $this->_getAttributeValueInfo($row, $attribute);
            $valueInfo['value'] = $value;
            $records[] = $this->_getTableRecord($valueInfo, $tableColumns);
        }

        return $records;
    }

    /**
     * Returns attribute meta info for record,
     * e.g. entity_type_id, attribute_id, etc
     *
     * @param array $row
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @return array
     */
    protected function _getAttributeValueInfo($row, $attribute)
    {
        return array(
            'attribute_id' => $attribute->getId(),
            'entity_type_id' => $attribute->getEntityTypeId(),
            $this->_getEntityIdField($attribute) => $row[$this->_getEntityIdField($attribute)]
        );
    }


    /**
     * Retrieves attribute value
     *
     * @param array $row
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @return mixed|null
     */
    protected function _getAttributeValue($row, $attribute)
    {
        if (isset($row[$attribute->getAttributeCode()]) && !is_array($row[$attribute->getAttributeCode()])) {
            $value = $row[$attribute->getAttributeCode()];
        } elseif ($attribute->getIsRequired()
                  && $attribute->getDefaultValue() !== null
                  && $attribute->getDefaultValue() !== ''
                  && !is_array($attribute->getDefaultValue())) {
            $value = $attribute->getDefaultValue();
        } else {
            $value = null;
        }

        if ($attribute->usesSource() && $value !== null) {
            if ($attribute->getSource() instanceof Mage_Eav_Model_Entity_Attribute_Source_Abstract) {
                $value = $attribute->getSource()->getOptionId($value);
            } else {
                $value = $this->_getOptionIdNonAttributeSource($attribute->getSource()->getAllOptions(), $value);
            }
        }

        return $value;
    }

    /**
     * Get option id if attribute source model does not support eav attribute interface
     *
     *
     * @param array $options
     * @param mixed $value
     * @return null|string
     */
    protected function _getOptionIdNonAttributeSource($options, $value)
    {
        foreach ($options as $option) {
            if (strcasecmp($option['label'], $value)==0 || $option['value'] == $value) {
                return $option['value'];
            }
        }

        return null;
    }

    /**
     * Retrieves entity id field, based on entity configuration
     *
     * @param Mage_Eav_Model_Entity_Type|Mage_Eav_Model_Entity_Attribute $entityTypeModel
     * @return string
     */
    protected function _getEntityIdField($entityTypeModel)
    {
        if ($entityTypeModel->getEntityIdField()) {
            return $entityTypeModel->getEntityIdField();
        }
        return Mage_Eav_Model_Entity::DEFAULT_ENTITY_ID_FIELD;
    }
}
