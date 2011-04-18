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
 * Layout model that adds additional functionality
 * for testing the layout itself
 *
 */
class EcomDev_PHPUnit_Model_Layout extends Mage_Core_Model_Layout
{
    /**
     * List of replaced blocks creation
     *
     * @return array
     */
    protected $_replaceBlockCreation = array();

    /**
     * Replaces creation of some block by mock object
     *
     * @param string $classAlias
     * @param PHPUnit_Framework_MockObject_MockObject|PHPUnit_Framework_MockObject_MockBuilder $mock
     * @return EcomDev_PHPUnit_Model_Layout
     */
    public function replaceBlockCreation($classAlias, $mock)
    {
        $this->_replaceBlockCreation[$classAlias] = $mock;
        return $this;
    }

    /**
     * Flushes instance creation instruction list
     *
     * @return EcomDev_PHPUnit_Model_Layout
     */
    public function flushReplaceBlockCreation()
    {
        $this->_replaceBlockCreation = array();
        return $this;
    }

    /**
     * Overriden for possibility of replacing a block by mock object
     * (non-PHPdoc)
     * @see Mage_Core_Model_Layout::_getBlockInstance()
     */
    protected function _getBlockInstance($block, array $attributes=array())
    {
        if (!isset($this->_replaceBlockCreation[$block])) {
            return parent::_getBlockInstance($block, $attributes);
        }

        return $this->_replaceBlockCreation[$block];
    }

    /**
     * Resets layout instance properties
     *
     * @return EcomDev_PHPUnit_Model_Layout
     */
    public function reset()
    {
        $this->setXml(simplexml_load_string('<layout/>', $this->_elementClass));
        $this->_update = Mage::getModel('core/layout_update');
        $this->_area = null;
        $this->_helpers = array();
        $this->_directOutput = false;
        $this->_output = array();

        foreach ($this->_blocks as $block) {
            // Remove references between blocks
            $block->setParentBlock(null);
            $block->setMessageBlock(null);
            $block->unsetChildren();
        }

        $this->_blocks = array();

        $this->flushReplaceBlockCreation();
        return $this;
    }
}
