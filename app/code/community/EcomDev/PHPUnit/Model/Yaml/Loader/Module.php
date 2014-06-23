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
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @author     Colin Mollenhour <http://colin.mollenhour.com>
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */
class EcomDev_PHPUnit_Model_Yaml_Loader_Module extends EcomDev_PHPUnit_Model_Yaml_AbstractLoader
{
    /**
     * Returns processed file path based on module test directory
     *
     * @param string $fileName
     * @param string $relatedClassName
     * @param string $type
     * @return string|bool
     */
    protected function _getFilePath($fileName, $relatedClassName, $type)
    {
        $moduleName = false;

        if (preg_match('#^~(?<module>[^/]*)/(?<fileName>.*)$#', $fileName, $matches)) {
            $fileName = $matches['fileName'];
            if(!empty($matches['module'])) {
                $moduleName = $matches['module'];
            }
        }

        if (!$moduleName) {
            try {
                $moduleName = EcomDev_PHPUnit_Test_Case_Util::getModuleNameByClassName($relatedClassName);
            } catch (RuntimeException $e) {
                return false;
            }
        }


        $basePath = array();
        if ($prefixPosition = strpos($relatedClassName, $moduleName . '_Test_')) {
            $testType = substr($relatedClassName, $prefixPosition, strpos($relatedClassName, '_', $prefixPosition));
            $basePath[] = Mage::getModuleDir('', $moduleName) . DS . 'Test' . DS . $testType;
        }

        $basePath[] = Mage::getModuleDir('', $moduleName) . DS . 'Test';

        return $this->_checkFilePath($basePath, $fileName, $type);
    }
}
