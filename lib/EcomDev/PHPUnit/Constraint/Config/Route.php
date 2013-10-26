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
 * Controller router configuration constraint
 *
 */
class EcomDev_PHPUnit_Constraint_Config_Route
    extends EcomDev_PHPUnit_Constraint_AbstractConfig
{
    const XML_PATH_ROUTE_NODE = '%s/routers/%s';

    const TYPE_MODULE = 'module';
    const TYPE_MODULE_ORDER = 'module_order';
    const TYPE_ROUTER = 'router';
    const TYPE_FRONT_NAME = 'front_name';


    /**
     * Name of the area for constraint
     *
     * @var string
     */
    protected $_area = null;

    /**
     * Name of the route for constraint
     *
     * @var string
     */
    protected $_routeName = null;

    /**
     * Constraint for evaluation of module config node
     *
     * @param string $area
     * @param string $route
     * @param string $type
     * @param mixed $expectedValue
     */
    public function __construct($area, $route, $type, $expectedValue)
    {
        $this->_area = $area;
        $this->_routeName = $route;

        $this->_expectedValueValidation += array(
            self::TYPE_MODULE => array(true, 'is_string', 'string'),
            self::TYPE_MODULE_ORDER => array(true, 'is_array', 'array'),
            self::TYPE_ROUTER => array(true, 'is_string', 'string'),
            self::TYPE_FRONT_NAME => array(true, 'is_string', 'string')
        );

        $this->_typesWithDiff[] = self::TYPE_MODULE;
        $this->_typesWithDiff[] = self::TYPE_MODULE_ORDER;
        $this->_typesWithDiff[] = self::TYPE_FRONT_NAME;
        $this->_typesWithDiff[] = self::TYPE_ROUTER;

        parent::__construct(
            sprintf(self::XML_PATH_ROUTE_NODE, $this->_area, $this->_routeName),
            $type,
            $expectedValue
        );


    }

    /**
     * Evaluates that module is added to route for controllers processing
     *
     * @param Varien_Simplexml_Element $other
     * @return boolean
     */
    protected function evaluateModule($other)
    {
        if (!isset($other->args)
            || (!isset($other->args->module) && !isset($other->args->modules))) {
            $this->_expectedValue = (array) $this->_expectedValue;
            $this->setActualValue(array());
            return false;
        }

        $currentModules = $this->getModules($other->args);

        // Save actual value
        $this->setActualValue($currentModules);

        // Will add diff to module structure
        if (!in_array($this->_expectedValue, $currentModules)) {
            $currentModules[] = $this->_expectedValue;
        }

        $this->_expectedValue = $currentModules;
        return $this->_actualValue === $this->_expectedValue;
    }

    /**
     * Text representation of class alias constraint
     *
     * @return string
     */
    protected function textModule()
    {
        return 'contains expected module';
    }

    /**
     * Evaluates that modules are added to route for controllers processing
     * in particular order
     *
     * @param Varien_Simplexml_Element $other
     * @return boolean
     */
    protected function evaluateModuleOrder($other)
    {
        if ((!isset($other->args->module) && !isset($other->args->modules))) {
            $this->setActualValue(array());
            return false;
        }

        $currentModules = $this->getModules($other->args);

        // Save actual value
        $this->setActualValue($currentModules);

        $previousIndex = false;
        foreach ($this->_expectedValue as $index => $expectedValue) {
            if ($previousIndex === false) {
                if (!in_array($expectedValue, $currentModules)
                    && (!isset($this->_expectedValue[$index+1])
                        || !in_array($this->_expectedValue[$index+1], $currentModules))) {
                    $currentModules[] = $expectedValue;
                } elseif (!in_array($expectedValue, $currentModules)) {
                    // Add new item before next one
                    array_splice($currentModules,
                                 array_search($this->_expectedValue[$index+1], $currentModules),
                                 0, array($expectedValue));
                }
            } else {
                $previousValue = $this->_expectedValue[$previousIndex];
                $isInArray = in_array($expectedValue, $currentModules);
                $isAfter = $isInArray && array_search($previousValue, $currentModules) >
                                         array_search($expectedValue, $currentModules);

                if ($isInArray && $isAfter) {
                    continue;
                } elseif (!$isAfter) {
                    // Remove current item from modules
                    array_splice($currentModules, array_search($expectedValue, $currentModules), 1);
                }

                // Add new item after previous one
                array_splice($currentModules,
                             array_search($previousValue, $currentModules)+1,
                             0, array($expectedValue));
            }

            $previousIndex = $index;
        }

        $this->_expectedValue = $currentModules;

        return $this->_actualValue === $this->_expectedValue;
    }

    /**
     * Text representation of class alias constraint
     *
     * @return string
     */
    protected function textModuleOrder()
    {
        return 'contains modules in expected order';
    }

    /**
     * Evaluates that route is added to expected router
     *
     * @param Varien_Simplexml_Element $other
     * @return boolean
     */
    protected function evaluateRouter($other)
    {
        $this->setActualValue((string)$other->use);
        return $this->_actualValue === $this->_expectedValue;
    }

    /**
     * Text representation of class alias constraint
     *
     * @return string
     */
    protected function textRouter()
    {
        return 'is specified for expected router';
    }

    /**
     * Evaluates that route is added to expected router
     *
     * @param Varien_Simplexml_Element $other
     * @return boolean
     */
    protected function evaluateFrontName($other)
    {
        $frontName = '';
        if (isset($other->args->frontName)) {
            $frontName = (string)$other->args->frontName;
        }
        $this->setActualValue($frontName);

        return $this->_actualValue === $this->_expectedValue;
    }

    /**
     * Text representation of class alias constraint
     *
     * @return string
     */
    protected function textFrontName()
    {
        return 'is specified for the same front name as expected';
    }

    /**
     * Returns sorted list of modules from args node
     *
     * @param Varien_Simplexml_Element $argsNode
     * @return array
     */
    protected function getModules(Varien_Simplexml_Element $argsNode)
    {
        $modules = array();

        if (isset($argsNode->module)) {
            $modules[] = (string)$argsNode->module;
        }
        if (isset($argsNode->modules)) {
            // Repeats logic in core router
            foreach ($argsNode->modules->children() as $module) {
                if ((string)$module) {
                    if ($before = $module->getAttribute('before')) {
                        $position = array_search($before, $modules);
                        if ($position === false) {
                            $position = 0;
                        }
                        array_splice($modules, $position, 0, (string)$module);
                    } elseif ($after = $module->getAttribute('after')) {
                        $position = array_search($after, $modules);
                        if ($position === false) {
                            $position = count($modules);
                        }
                        array_splice($modules, $position+1, 0, (string)$module);
                    } else {
                        $modules[] = (string)$module;
                    }
                }

            }
        }

        return $modules;
    }

    /**
     * Custom failure description for showing config related errors
     * (non-PHPdoc)
     * @see PHPUnit_Framework_Constraint::customFailureDescription()
     */
    protected function customFailureDescription($other)
    {
        return sprintf(
            'controller route %s for %s area %s.',
            $this->_routeName,
            $this->_area,
            $this->toString()
        );
    }
}
