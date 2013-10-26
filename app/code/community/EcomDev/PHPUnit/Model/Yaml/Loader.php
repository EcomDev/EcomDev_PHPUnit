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

// Loading Spyc yaml parser,
// because Symfony component is not working properly with nested structures
require_once 'Spyc/spyc.php';

class EcomDev_PHPUnit_Model_Yaml_Loader
{
    const XML_PATH_YAML_FILE_LOADERS = 'phpunit/suite/yaml/loaders';

    const TYPE_FIXTURE = 'fixtures';
    const TYPE_PROVIDER = 'providers';
    const TYPE_EXPECTATION = 'expectations';

    /**
     * YAML file loaders
     *
     * @var EcomDev_PHPUnit_Model_Yaml_LoaderInterface[]
     */
    protected $_loaders = array();

    /**
     * Returns arrays of loaders
     *
     * @return EcomDev_PHPUnit_Model_Yaml_LoaderInterface[]
     */
    public function getLoaders()
    {
        if (empty($this->_loaders)) {
            $this->_initLoaders();
        }

        return $this->_loaders;
    }

    /**
     * Adds a loader to list of loaders
     *
     * @param EcomDev_PHPUnit_Model_Yaml_LoaderInterface $loader
     * @return EcomDev_PHPUnit_Model_Yaml_Loader
     */
    public function addLoader(EcomDev_PHPUnit_Model_Yaml_LoaderInterface $loader)
    {
        $this->_loaders[] = $loader;
        return $this;
    }

    /**
     * Initializes current loaders from configuration
     *
     * @return EcomDev_PHPUnit_Model_Yaml_Loader
     */
    protected function _initLoaders()
    {
        if ($loadersNode = Mage::getConfig()->getNode(self::XML_PATH_YAML_FILE_LOADERS)) {
            foreach ($loadersNode->children() as $loader) {
                $loaderInstance = Mage::getSingleton((string)$loader);
                $this->addLoader($loaderInstance);
            }
        }

        return $this;
    }

    /**
     * Finds YAML file or returns false
     *
     * It invokes all added loaders for loading a yaml file
     *
     * @param string $className
     * @param string $type
     * @param string $name
     * @return string|bool
     */
    public function resolveFilePath($className, $type, $name)
    {
        $filePath = false;
        foreach ($this->getLoaders() as $loader) {
            // Break load file resolve path on first found file
            if ($filePath = $loader->resolveFilePath($name, $className, $type)) {
                break;
            }
        }

        return $filePath;
    }

    /**
     * Loads YAML file content
     *
     * @param string $filePath
     * @return array
     */
    public function load($filePath)
    {
        return Spyc::YAMLLoad($filePath);
    }
}
