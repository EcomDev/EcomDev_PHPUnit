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

class EcomDev_PHPUnit_Model_Yaml_Loader_Global extends EcomDev_PHPUnit_Model_Yaml_AbstractLoader
{
    /**
     * Returns processed file path
     *
     * @param string $fileName
     * @param string $relatedClassName
     * @param string $type
     * @return string|bool
     */
    protected function _getFilePath($fileName, $relatedClassName, $type)
    {
        $reflection = EcomDev_Utils_Reflection::getReflection($relatedClassName);
        $fileObject = new SplFileInfo($reflection->getFileName());

        $basePath = $fileObject->getPath();

        // While base path is within a base directory of Magento installment
        while (strpos($basePath, Mage::getBaseDir()) && !is_dir($basePath . DS . $type)) {
            $basePath = dirname($basePath);
        }

        if (basename($basePath)) {
            return $basePath . DS . $type . DS .$fileName;
        }

        return false;
    }
}