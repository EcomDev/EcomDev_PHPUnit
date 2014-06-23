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
 * Product EAV fixture loader
 *
 *
 */
class EcomDev_PHPUnit_Model_Mysql4_Fixture_Eav_Catalog_Product extends EcomDev_PHPUnit_Model_Mysql4_Fixture_Eav_Catalog_Abstract
{
    protected $_requiredIndexers = array(
        'cataloginventory_stock',
        'catalog_product_attribute',
        'catalog_product_price'
    );


    /**
     * Overridden to fix issue with flat tables existence mark
     * 
     * @see EcomDev_PHPUnit_Model_Mysql4_Fixture_Eav_Abstract::loadEntity()
     */
    public function loadEntity($entityType, $values)
    {
        // Fix of Product Flat Indexer
        EcomDev_Utils_Reflection::setRestrictedPropertyValue(
            Mage::getResourceSingleton('catalog/product_flat_indexer'),
            '_existsFlatTables',
            array()
        );

        return parent::loadEntity($entityType, $values);
    }

    /**
     * Overridden to add easy fixture loading for websites and categories associations
     * 
     * @see EcomDev_PHPUnit_Model_Mysql4_Fixture_Eav_Abstract::_getCustomTableRecords()
     */
    protected function _getCustomTableRecords($row, $entityTypeModel)
    {
        $records = array();
        $records += $this->_getWebsiteVisibilityRecords($row, $entityTypeModel);
        $records += $this->_getTierPriceRecords($row, $entityTypeModel);
        $records += $this->_getCategoryAssociationRecords($row, $entityTypeModel);
        $records += $this->_getProductStockRecords($row, $entityTypeModel);
        $records += $this->_getProductSuperRelations($row, $entityTypeModel);
        $records += $this->_getProductBundleRelations($row, $entityTypeModel);
        return $records;
    }

    /**
     * Changed to support price attribute type multi-scope
     *
     * @param array $row
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @param array $tableColumns
     * @return array
     * @see EcomDev_PHPUnit_Model_Mysql4_Fixture_Eav_Catalog_Abstract::_getAttributeRecords()
     */
    protected function _getAttributeRecords($row, $attribute, $tableColumns)
    {
        if ($attribute->getFrontendInput() == 'price') {
            $attribute->setIsGlobal(
                Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE
            );
        }

        return parent::_getAttributeRecords($row, $attribute, $tableColumns);
    }

    /**
     * Generates records for catalog_product_bundle_option and catalog_product_bundle_selection tables
     *
     * @param array $row
     * @param Mage_Eav_Model_Entity_Type $entityTypeModel
     * @return array
     * @throws RuntimeException
     */
    protected function _getProductBundleRelations($row, $entityTypeModel){
        $result = array();
        if (isset($row['bundle_options']) && is_array($row['bundle_options'])) {
            $aOptions = array();
            $aSelections = array();
            foreach($row['bundle_options'] as $iOptionId => $aOption){
                $aOptions[] = array(
                    'option_id' => $iOptionId,
                    'parent_id' => $row[$this->_getEntityIdField($entityTypeModel)],
                    'type' => 'radio'  //TODO: allow specification of different types
                );
                foreach($aOption as $iSelectionProductId){
                    $aSelections[] = array(
                        'option_id' => $iOptionId,
                        'parent_product_id' => $row[$this->_getEntityIdField($entityTypeModel)],
                        'product_id' => $iSelectionProductId,
                    );
                }
            }
            if(!empty($aOptions)){
                $result += array('bundle/option' => $aOptions);
            }
            if(!empty($aSelections)){
                $result += array('bundle/selection' => $aSelections);
            }
        }
        return $result;
    }

    /**
     * Generates records for catalog_product_super_attribute and catalog_product_super_link tables
     *
     * @param array $row
     * @param Mage_Eav_Model_Entity_Type $entityTypeModel
     * @throws Exception
     * @return array
     */
    protected function _getProductSuperRelations($row, $entityTypeModel)
    {
        $result = array();
        if (isset($row['super_attributes']) && is_array($row['super_attributes'])) {
            $records = array();
            $attributeCodes = $entityTypeModel->getAttributeCollection();
            foreach ($row['super_attributes'] as $attributeCode) {
                $attribute = $attributeCodes->getItemByColumnValue('attribute_code', $attributeCode);
                if(!$attribute){
                    throw new Exception('Super attribute not found with code: ' . $attributeCode);
                }
                $attributeId = $attribute->getId();
                $records[] = array(
                    'product_id' => $row[$this->_getEntityIdField($entityTypeModel)],
                    'attribute_id' => $attributeId
                );
            }

            if ($records) {
                $result += array('catalog/product_super_attribute' => $records);
            }
        }
        if (isset($row['configurable_children']) && is_array($row['configurable_children'])) {
            $records = array();
            foreach ($row['configurable_children'] as $childId) {
                $records[] = array(
                    'parent_id' => $row[$this->_getEntityIdField($entityTypeModel)],
                    'product_id' => $childId
                );
            }

            if ($records) {
                $result += array('catalog/product_super_link' => $records);
            }
        }

        return $result;
    }


    /**
     * Generates records for catalog_product_website table
     *
     * @param array $row
     * @param Mage_Eav_Model_Entity_Type $entityTypeModel
     * @return array
     * @throws RuntimeException
     */
    protected function _getWebsiteVisibilityRecords($row, $entityTypeModel)
    {
        if (isset($row['website_ids']) && is_array($row['website_ids'])) {
            $records = array();
            foreach ($row['website_ids'] as $websiteId) {
                $website = Mage::app()->getWebsite($websiteId);
                $records[] = array(
                    'product_id' => $row[$this->_getEntityIdField($entityTypeModel)],
                    'website_id' => $website->getId()
                );
            }

            // We shouldn't return empty table data
            if ($records) {
                return array('catalog/product_website' => $records);
            }
        }

        return array();
    }

	/**
     * Generates records for catalog_product_entity_tier_price table
     *
     * @param array $row
     * @param Mage_Eav_Model_Entity_Type $entityTypeModel
     * @return array
     */
    protected function _getTierPriceRecords($row, $entityTypeModel)
    {
        if (isset($row['tier_price']) && is_array($row['tier_price'])) {
            $tableName = $entityTypeModel->getValueTablePrefix() . '_tier_price';
            $columns = $this->_getWriteAdapter()->describeTable(
                $tableName
            );

            $records = array();
            foreach ($row['tier_price'] as $tierPrice) {
                $tierPrice[$this->_getEntityIdField($entityTypeModel)] = $row[$this->_getEntityIdField($entityTypeModel)];
                $records[] = $this->_getTableRecord($tierPrice, $columns);
            }

            if ($records) {
                return array($tableName => $records);
            }
        }
        return array();
    }

    /**
     * Generates records for catalog_category_product table
     *
     * @param array $row
     * @param Mage_Eav_Model_Entity_Type $entityTypeModel
     * @return array
     */
    protected function _getCategoryAssociationRecords($row, $entityTypeModel)
    {
        if (isset($row['category_ids']) && is_array($row['category_ids'])) {
            $records = array();
            foreach ($row['category_ids'] as $categoryId) {
                $records[] = array(
                    'category_id' => $categoryId,
                    'product_id'  => $row[$this->_getEntityIdField($entityTypeModel)]
                );
            }

            if ($records) {
                $this->addRequiredIndexer('catalog_category_product');
                return array('catalog/category_product' => $records);
            }
        }
        return array();
    }

    /**
     * Generates records for cataloginventory_stock_item table
     *
     * @param array $row
     * @param Mage_Eav_Model_Entity_Type $entityTypeModel
     * @return array
     */
    protected function _getProductStockRecords($row, $entityTypeModel)
    {
        if (isset($row['stock']) && is_array($row['stock'])) {
            $columns = $this->_getWriteAdapter()->describeTable(
                $this->getTable('cataloginventory/stock_item')
            );

            $row['stock']['product_id'] = $row[$this->_getEntityIdField($entityTypeModel)];

            if (!isset($row['stock']['stock_id'])) {
                $row['stock']['stock_id'] = 1;
            }

            return array(
                'cataloginventory/stock_item' => array(
                    $this->_getTableRecord($row['stock'], $columns)
                )
            );
        }
        return array();
    }


    /**
     * Adding enabled and visibility indexes
     *
     * 
     * @see EcomDev_PHPUnit_Model_Mysql4_Fixture_Eav_Abstract::_customEntityAction()
     */
    protected function _customEntityAction($entity, $entityTypeModel)
    {
        Mage::getResourceSingleton('catalog/product_status')
            ->refreshEnabledIndex($entity[$this->_getEntityIdField($entityTypeModel)], 0);

        parent::_customEntityAction($entity, $entityTypeModel);
        return $this;
    }
}
