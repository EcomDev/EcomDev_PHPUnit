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

abstract class EcomDev_PHPUnit_Model_Yaml_AbstractLoader
    implements EcomDev_PHPUnit_Model_Yaml_LoaderInterface
{
    const DATA_DIR = '_data';

    protected $_typeMap = array(
        'fixtures' => 'fx',
        'providers' => 'dp',
        'expectations' => 'ex'
    );

    /**
     * Resolves YAML file path based on its filename,
     * if file is not found, it should return false
     *
     * @param string $fileName name of the file
     * @param string $relatedClassName class name from which load of yaml file is invoked
     * @param string $type type of Yaml file (provider, fixture, expectation)
     * @return string|bool
     */
    public function resolveFilePath($fileName, $relatedClassName, $type)
    {
        if (strrpos($fileName, '.yaml') !== strlen($fileName) - 5) {
            $fileName .= '.yaml';
        }

        $filePath = $this->_getFilePath($fileName, $relatedClassName, $type);

        if ($filePath && file_exists($filePath)) {
            return $filePath;
        }

        return false;
    }

    /**
     * Returns processed file path
     *
     * @param string $fileName
     * @param string $relatedClassName
     * @param string $type
     * @return string
     */
    abstract protected function _getFilePath($fileName, $relatedClassName, $type);

    /**
     * Looks in path for possible existent fixture
     *
     * @param string|array $path
     * @param string $fileName
     * @param string $type
     * @return bool|string
     */
    protected function _checkFilePath($path, $fileName, $type)
    {
        if (is_array($path)) {
            foreach ($path as $value) {
                if ($filePath = $this->_checkFilePath($value, $fileName, $type)) {
                    return $filePath;
                }
            }

            return false;
        }

        if (isset($this->_typeMap[$type])
            && file_exists($filePath = $path . DS . self::DATA_DIR . DS . $this->_typeMap[$type] . '-' . $fileName)) {
            return $filePath;
        }

       if (file_exists($filePath = $path . DS . $type . DS . $fileName)) {
            return $filePath;
        }

        return false;
    }
}
