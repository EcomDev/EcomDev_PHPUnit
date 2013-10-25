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
 * Category EAV fixture loader
 *
 *
 */
class EcomDev_PHPUnit_Model_Mysql4_Fixture_Eav_Catalog_Category extends EcomDev_PHPUnit_Model_Mysql4_Fixture_Eav_Catalog_Abstract
{
    protected $_requiredIndexers = array(
        'catalog_category_flat'
    );

    protected function _getBackendTables()
    {
        return array(
            'varchar',
            'int',
            'decimal',
            'text',
            'datetime'
        );
    }

    /**
     * Overriden to add easy fixture loading for product associations
     * (non-PHPdoc)
     * @see EcomDev_PHPUnit_Model_Mysql4_Fixture_Eav_Abstract::_getCustomTableRecords()
     */
    protected function _getCustomTableRecords($row, $entityTypeModel)
    {
        return $this->_getProductAssociationRecords($row, $entityTypeModel);
    }


    /**
     * Generates records for catalog_category_product table
     *
     * @param array $row
     * @param Mage_Eav_Model_Entity_Type $entityTypeModel
     * @return array
     */
    protected function _getProductAssociationRecords($row, $entityTypeModel)
    {
        if (isset($row['products']) && is_array($row['products'])) {
            $records = array();
            foreach ($row['products'] as $productId => $position) {
                $records[] = array(
                    'category_id' => $row[$this->_getEntityIdField($entityTypeModel)],
                    'product_id'  => $productId,
                    'position' => $position
                );
            }

            if ($records) {
                $this->addRequiredIndexer('catalog_category_product');
                return array('catalog/category_product' => $records);
            }
        }
        return array();
    }
}