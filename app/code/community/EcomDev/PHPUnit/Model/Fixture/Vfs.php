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

use org\bovigo\vfs\vfsStream as Stream;
use org\bovigo\vfs\vfsStreamDirectory as StreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper as StreamWrapper;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor as StreamVisitor;

/**
 * VFS library wrapper, to have simple api for manipulation with virtual fs
 *
 *
 */
class EcomDev_PHPUnit_Model_Fixture_Vfs
{
    /**
     * Current root directory stack of the VFS before apply process were started
     *
     * @var StreamDirectory[]
     */
    protected $_currentRoot = array();

    /**
     * Applies VFS directory structure
     *
     * @param array $data
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Vfs
     */
    public function apply($data)
    {
        if (StreamWrapper::getRoot()) {
            $this->_currentRoot[] = StreamWrapper::getRoot();
        }
        Stream::setup('root', null, $data);
        return $this;
    }

    /**
     * Discards VFS file system changes
     *
     * @return EcomDev_PHPUnit_Model_Fixture_Vfs
     */
    public function discard()
    {
        if ($this->_currentRoot) {
            StreamWrapper::setRoot(array_pop($this->_currentRoot));
        }
        return $this;
    }

    /**
     * Dump current files structure
     *
     * @return array
     */
    public function dump()
    {
        $currentRoot = StreamWrapper::getRoot();
        if (!$currentRoot) {
            return array();
        }

        $visitor = new StreamVisitor();
        $visitor->visit($currentRoot);
        return $visitor->getStructure();
    }

    /**
     * Returns stream wrapper url for operation
     * via built-in fs functions
     *
     * @param string $path
     * @return string
     */
    public function url($path)
    {
        if (strpos($path, StreamWrapper::getRoot()->getName()) === false) {
            $path = StreamWrapper::getRoot()->getName() . '/' . $path;
        }

        return Stream::url($path);
    }
}
