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
 * Layout model that adds additional functionality
 * for testing the layout itself
 *
 */
class EcomDev_PHPUnit_Model_Layout
    extends Mage_Core_Model_Layout
    implements EcomDev_PHPUnit_Constraint_Layout_LoggerInterface,
               EcomDev_PHPUnit_IsolationInterface
{
    /**
     * List of replaced blocks creation
     *
     * @return array
     */
    protected $_replaceBlockCreation = array();

    /**
     * Records for gathering information about all,
     * the actions that was performed
     *
     *
     * @var array
     */
    protected $_records = array();

    /**
     * List of collected args for action call
     *
     * @var array
     */
    protected $_collectedArgs = null;

    /**
     * Collected block during block creation
     *
     * @var Mage_Core_Block_Abstract
     */
    protected $_collectedBlock = null;


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
     * Overridden for possibility of replacing a block by mock object
     * 
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
        $this->_records = array();

        foreach ($this->_blocks as $block) {
            /* @var $block Mage_Core_Block_Abstract */
            // Remove references between blocks
            EcomDev_Utils_Reflection::setRestrictedPropertyValue($block, '_parentBlock', null);
            $block->unsMessageBlock();
            $block->unsetChildren();
        }

        $this->_blocks = array();
        return $this;
    }


    /**
     * Returns all the recorded actions
     *
     * @return array
     */
    public function getRecords()
    {
        return $this->_records;
    }

     /**
     * Returns all actions performed on the target
     * or if target is null returns actions for all targets
     *
     * @param string $action
     * @param string|null $target
     * @return array
     */
    public function findAll($action, $target = null)
    {
        if ($target !== null && isset($this->_records[$action][$target])) {
            return $this->_records[$action][$target];
        } elseif ($target !== null) {
            return array();
        } elseif (!isset($this->_records[$action])) {
            return array();
        }

        $result = array();
        foreach ($this->_records[$action] as $target => $records) {
            $record['target'] = $target;
            $result = array_merge($result, $records);
        }

        return $result;
    }

    /**
     * Returns all actions targets
     *
     * @param string $action
     * @return array
     */
    public function findAllTargets($action)
    {
        if (isset($this->_records[$action])) {
            return array_keys($this->_records[$action]);
        }

        return array();
    }

    /**
     * Returns a single target action record by specified parameters
     *
     * @param string $action
     * @param string $target
     * @param array $parameters
     * @param string $searchType
     * @return boolean
     */
    public function findByParameters($action, $target, array $parameters, $searchType = self::SEARCH_TYPE_AND)
    {
        if (!isset($this->_records[$action][$target])) {
            return array();
        }

        $records = array();
        $arrayValues = false;

        // If it is a numeric array, then actual parameters should transformed as well
        if (count(array_filter(array_keys($parameters), 'is_int')) === count($parameters)) {
            $arrayValues = true;
        }


        foreach ($this->_records[$action][$target] as $actualParameters) {
            if ($arrayValues) {
                $actualParameters = array_values($actualParameters);
            }

            $intersection = array_intersect_assoc($actualParameters, $parameters);
            switch ($searchType) {
                case self::SEARCH_TYPE_OR:
                    $match = !empty($intersection);
                    break;
                case self::SEARCH_TYPE_EXACT:
                    $match = count($intersection) === count($actualParameters);
                    break;
                case self::SEARCH_TYPE_AND:
                default:
                    $match = count($intersection) === count($parameters);
                    break;
            }

            if ($match) {
                $records[] = $actualParameters;
            }
        }

        return $records;
    }

    /**
     * Returns first action that was recorded for target
     *
     * @param string $action
     * @param string $target
     * @return array
     */
    public function findFirst($action, $target)
    {
        if (!isset($this->_records[$action][$target])) {
            return false;
        }

        reset($this->_records[$action][$target]);

        return current($this->_records[$action][$target]);
    }

    /**
     * Records a particular target action
     *
     * @param string $action
     * @param string|null $target
     * @param array $parameters
     * @return EcomDev_PHPUnit_Model_Layout
     */
    public function record($action, $target = null, array $parameters = array())
    {
        $this->_records[$action][$target][] = $parameters;
        return $this;
    }

    /**
     * Observes a system event that is triggered on block render process start
     *
     * @param Varien_Event_Observer $observer
     * @return EcomDev_PHPUnit_Model_Layout
     */
    public function recordBlockRender(Varien_Event_Observer $observer)
    {
        /* @var $block Mage_Core_Block_Abstract */
        $block = $observer->getEvent()->getBlock();
        $transport = $observer->getEvent()->getTransport();

        $this->record(
            self::ACTION_BLOCK_RENDERED,
            $block->getNameInLayout(),
            array('content' => $transport->getHtml())
        );
    }

    /**
     * Records action call
     * 
     * @see Mage_Core_Model_Layout::_generateAction()
     */
    protected function _generateAction($node, $parent)
    {
        $this->_collectedArgs = $this->_collectActionArguments($node);
        parent::_generateAction($node, $parent);
        if ($this->_collectedArgs !== null) {
            $this->_translateLayoutNode($node, $this->_collectedArgs);
            $method = (string)$node['method'];
            if (!empty($node['block'])) {
                $parentName = (string)$node['block'];
            } else {
                $parentName = $parent->getBlockName();
            }

            $target = $parentName . '::' . $method;
            $this->record(self::ACTION_BLOCK_ACTION, $target, $this->_collectedArgs);
        }
        return $this;
    }



    /**
     * Collects action arguments
     *
     * @param Varien_SimpleXml_Element $node
     * @return array|null
     */
    protected function _collectActionArguments($node)
    {
        if (isset($node['ifconfig']) && !Mage::getStoreConfigFlag((string)$node['ifconfig'])) {
            return null;
        }

        $args = (array)$node->children();
        unset($args['@attributes']);

        foreach ($args as $key => $arg) {
            if (($arg instanceof Mage_Core_Model_Layout_Element)) {
                if (isset($arg['helper'])) {
                    $helperName = explode('/', (string)$arg['helper']);
                    $helperMethod = array_pop($helperName);
                    $helperName = implode('/', $helperName);
                    $arg = $arg->asArray();
                    unset($arg['@']);
                    $args[$key] = call_user_func_array(array(Mage::helper($helperName), $helperMethod), $arg);
                } else {
                    /**
                     * if there is no helper we hope that this is assoc array
                     */
                    $arr = array();
                    foreach($arg as $subkey => $value) {
                        $arr[(string)$subkey] = $value->asArray();
                    }
                    if (!empty($arr)) {
                        $args[$key] = $arr;
                    }
                }
            }
        }

        if (isset($node['json'])) {
            $json = explode(' ', (string)$node['json']);
            foreach ($json as $arg) {
                $args[$arg] = Mage::helper('core')->jsonDecode($args[$arg]);
            }
        }

        return $args;
    }

    /**
     * Records information about new block creation
     * 
     * @see Mage_Core_Model_Layout::_generateBlock()
     */
    protected function _generateBlock($node, $parent)
    {
        $this->_collectedBlock = null;
        parent::_generateBlock($node, $parent);
        if ($this->_collectedBlock) {
            $target = $this->_collectedBlock->getNameInLayout();
            $params = array();
            if (isset($node['as'])) {
                $params['alias'] = (string)$node['as'];
            } else {
                $params['alias'] = $target;
            }

            if (isset($node['class'])) {
                $params['type'] = (string)$node['class'];
            } elseif (isset($node['type'])) {
                $params['type'] = (string)$node['type'];
            }

            $params['class'] = get_class($this->_collectedBlock);

            $params['is_root'] = isset($node['output']);
            $this->record(self::ACTION_BLOCK_CREATED, $target, $params);

            if (isset($node['template'])) {
                $this->record(self::ACTION_BLOCK_ACTION, $target . '::setTemplate',
                              array('template' => (string)$node['template']));
            }
        }
        return $this;
    }

    /**
     * Collects block creation
     * 
     * @see Mage_Core_Model_Layout::addBlock()
     */
    public function addBlock($block, $blockName)
    {
        $block = parent::addBlock($block, $blockName);

        if ($this->_collectedBlock === null) {
            $this->_collectedBlock = $block;
        }

        return $block;
    }


    /**
     * Records information about blocks removal and loaded layout handles
     * (non-PHPdoc)
     * @see Mage_Core_Model_Layout::generateXml()
     */
    public function generateXml()
    {
        $loadedHandles = $this->getUpdate()->getHandles();
        foreach ($loadedHandles as $key => $handle) {
            $params = array();
            if ($key > 0) {
                $params['after'] = array_slice($loadedHandles, 0, $key);
            } else {
                $params['after'] = array();
            }

            if ($key < count($loadedHandles)) {
                $params['before'] = array_slice($loadedHandles, $key + 1);
            } else {
                $params['before'] = array();
            }

            $this->record(self::ACTION_HANDLE_LOADED, $handle, $params);
        }

        parent::generateXml();

        $removedBlocks = $this->_xml->xpath('//block[@ignore]');

        if (is_array($removedBlocks)) {
            foreach ($removedBlocks as $block) {
                $this->record(self::ACTION_BLOCK_REMOVED, $block->getBlockName());
            }
        }

        return $this;
    }


    /**
     * Returns block position information in the parent subling.
     * Returned array contains two keys "before" and "after"
     * which are list of block names in this positions
     *
     * @param string $block
     * @return array
     */
    public function getBlockPosition($block)
    {
        $result = array(
            'before' => array(),
            'after' => array()
        );

        $block = $this->getBlock($block);
        if (!$block || !$block->getParentBlock()) {
            return $result;
        }

        $sortedBlockNames = $block->getParentBlock()->getSortedChildren();
        $key = 'before';
        foreach ($sortedBlockNames as $blockName) {
            if ($blockName == $block->getNameInLayout()) {
                $key = 'after';
                continue;
            }
            $result[$key][] = $blockName;
        }

        return $result;
    }

	/**
     * Returns block parent
     *
     * @param string $block
     * @return srting|boolean
     */
    public function getBlockParent($block)
    {
        $block = $this->getBlock($block);
        if (!$block || !$block->getParentBlock()) {
            return false;
        }

        return $block->getParentBlock()->getNameInLayout();
    }

    /**
     * Returns block property by getter
     *
     * @param string $block
     * @param $property
     * @throws RuntimeException
     * @return mixed
     */
    public function getBlockProperty($block, $property)
    {
        $block = $this->getBlock($block);

        if (!$block) {
            throw new RuntimeException('Received a call to block, that does not exist');
        }

        return $block->getDataUsingMethod($property);
    }

    /**
     * Retuns a boolean flag for layout load status
     *
     * @return boolean
     */
    public function isLoaded()
    {
        return $this->_xml->hasChildren();
    }

    /**
     * Records that layout was rendered
     * (non-PHPdoc)
     * @see Mage_Core_Model_Layout::getOutput()
     */
    public function getOutput()
    {
        $this->record(self::ACTION_RENDER, 'layout');

        // parent::getOutput() with Inchoo_PHP7 fix:
        $out = '';
        if (!empty($this->_output)) {
            foreach ($this->_output as $callback) {
                $out .= $this->getBlock($callback[0])->{$callback[1]}();
            }
        }

        return $out;
    }
}
