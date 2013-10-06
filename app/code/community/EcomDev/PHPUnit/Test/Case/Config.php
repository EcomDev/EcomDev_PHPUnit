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
 * Base test case for testing module configurations
 *
 */
abstract class EcomDev_PHPUnit_Test_Case_Config extends EcomDev_PHPUnit_Test_Case
{
    /**
     * Get the adminhtml menu config nodes.
     *
     * @return Varien_Simplexml_Element
     */
    protected static function _getAdminhtmlMenu()
    {
        /** @var Varien_Simplexml_Element $menuNode */
        $menuNode = Mage::getSingleton('admin/config')->getAdminhtmlConfig()->getNode('menu');

        if (!($menuNode instanceof Varien_Simplexml_Element))
        {
            self::fail('Could not load adminhtml menu');
        }

        return $menuNode;
    }


    /**
     * Receive the Class::Action to a route.
     *
     * @param $module
     * @param $controller
     * @param $action
     *
     * @return void
     * @throws Mage_Core_Exception
     */
    protected static function _routeToMethod($module, $controller = null, $action = null)
    {
        /** @var Mage_Core_Controller_Varien_Router_Abstract $router */
        $router = self::app()->getFrontController()->getRouterByRoute($module);

        $front = $router->getFront();

        if ($module == 'adminhtml')
        { // correct adminhtml to admin, because the router only knows that
            $module = 'admin';
        }

        /**
         * Searching router args by module name from route using it as key
         */
        $modules = $router->getModuleByFrontName($module);

        if (!is_array($modules))
        { // no modules? meh...
            return null;
        }

        // get controller name
        if (!$controller)
        {
            $controller = $front->getDefault('controller');
        }

        // get action name
        if (!$action)
        {
            $action = $front->getDefault('action');
        }

        /**
         * Going through modules to find appropriate controller
         */
        foreach ($modules as $realModule)
        {
            $controllerFileName = $router->getControllerFileName($realModule, $controller);
            if (!$router->validateControllerFileName($controllerFileName))
            { // no filename for this controller found: go on searching
                continue;
            }

            $controllerClassName = $router->getControllerClassName($realModule, $controller);
            if (!$controllerClassName)
            { // no controller found for this module: go on searching
                continue;
            }

            // include controller file if needed
            if (!class_exists($controllerClassName, false))
            { // not yet loaded: try to
                if (!file_exists($controllerFileName))
                { // configured but no file given: ignore bullshit and continue
                    continue;
                }

                require_once $controllerFileName;

                if (!class_exists($controllerClassName, false))
                {
                    throw Mage::exception(
                              'Mage_Core',
                              Mage::helper('core')->__(
                                  'Controller file was loaded but class does not exist'
                              )
                    );
                }
            }

            if (in_array($action . 'Action', get_class_methods($controllerClassName)))
            { // yeha found: return like "Class::methodAction"
                return $controllerClassName . '::' . $action . 'Action';
            }
        }

        return null;
    }


    /**
     * Validate if a route has an controller and action.
     *
     * @param $module
     * @param $controller
     * @param $action
     *
     * @return bool
     * @throws Mage_Core_Exception
     */
    protected static function _validateRoute($module, $controller = null, $action = null)
    {
        return (bool) self::_routeToMethod($module, $controller, $action);
    }

    /**
     * Returns a new instance of EcomDev_PHPUnit_Constraint_Config
     *
     * @param EcomDev_PHPUnit_Constraint_Config_Interface $configContstraint
     * @return EcomDev_PHPUnit_Constraint_Config
     */
    public static function config($configContstraint)
    {
        return new EcomDev_PHPUnit_Constraint_Config($configContstraint);
    }

    /**
     * A new constraint for checking node value
     *
     * @param string $nodePath
     * @param string $type
     * @param mixed $expectedValue
     * @return EcomDev_PHPUnit_Constraint_Config
     */
    public static function configNode($nodePath, $type, $expectedValue = null)
    {
        return self::config(
            new EcomDev_PHPUnit_Constraint_Config_Node($nodePath, $type, $expectedValue)
        );
    }

    /**
     * A new constraint for checking module node
     *
     * @param string $moduleName
     * @param string $type
     * @param string|null $expectedValue
     * @return EcomDev_PHPUnit_Constraint_Config
     */
    public static function configModule($moduleName, $type, $expectedValue = null)
    {
        return self::config(
            new EcomDev_PHPUnit_Constraint_Config_Module($moduleName, $type, $expectedValue)
        );
    }

    /**
     * A new constraint for checking resources node
     *
     * @param string $moduleName
     * @param string $type
     * @param string|null $expectedValue
     * @return EcomDev_PHPUnit_Constraint_Config
     */
    public static function configResource($moduleName,
                                          $type = EcomDev_PHPUnit_Constraint_Config_Resource::TYPE_SETUP_DEFINED,
                                          $expectedValue = null)
    {

        return self::config(
            new EcomDev_PHPUnit_Constraint_Config_Resource($moduleName, $type,
                                                           self::app()->getConfig()->getModuleDir('', $moduleName),
                                                           $expectedValue)
        );
    }

    /**
     * A new constraint for checking resource setup scripts consistency
     *
     * @param string $moduleName
     * @param string $type
     * @param array|null $expectedValue
     * @param string $resourceName
     * @return EcomDev_PHPUnit_Constraint_Config
     */
    public static function configResourceScript($moduleName,
                                                $type = EcomDev_PHPUnit_Constraint_Config_Resource_Script::TYPE_SCRIPT_SCHEME,
                                                array $expectedValue = null, $resourceName = null)
    {
        return self::config(
            new EcomDev_PHPUnit_Constraint_Config_Resource_Script($moduleName, $type,
                                                                  self::app()->getConfig()->getModuleDir('', $moduleName),
                                                                  $resourceName, $expectedValue)
        );
    }
    

    /**
     * A new constraint for checking class alias nodes
     *
     * @param string $group
     * @param string $classAlias
     * @param string $expectedClassName
     * @param string $type
     * @return EcomDev_PHPUnit_Constraint_Config
     */
    public static function configClassAlias($group, $classAlias, $expectedClassName,
        $type = EcomDev_PHPUnit_Constraint_Config_ClassAlias::TYPE_CLASS_ALIAS)
    {
        return self::config(
            new EcomDev_PHPUnit_Constraint_Config_ClassAlias($group, $classAlias, $expectedClassName, $type)
        );
    }

    /**
     * A new constraint for checking table alias nodes
     *
     * @param string $tableAlias
     * @param string $expectedTableName
     * @param string $type
     * @return EcomDev_PHPUnit_Constraint_Config
     */
    public static function configTableAlias($tableAlias, $expectedTableName,
        $type = EcomDev_PHPUnit_Constraint_Config_TableAlias::TYPE_TABLE_ALIAS)
    {
        return self::config(
            new EcomDev_PHPUnit_Constraint_Config_TableAlias($tableAlias, $expectedTableName, $type)
        );
    }

    /**
     * Creates layout constraint
     *
     * @param string $area
     * @param string $expectedFile
     * @param string $type
     * @param string|null $layoutUpdate
     * @param string|null $theme
     * @param string|null $designPackage
     * @return EcomDev_PHPUnit_Constraint_Config
     */
    public static function configLayout($area, $expectedFile, $type, $layoutUpdate = null, $theme = null, $designPackage = null)
    {
        if (!EcomDev_PHPUnit_Constraint_Config_Layout::getDesignPackageModel()) {
            EcomDev_PHPUnit_Constraint_Config_Layout::setDesignPackageModel(Mage::getModel('ecomdev_phpunit/design_package'));
        }

        return self::config(
            new EcomDev_PHPUnit_Constraint_Config_Layout($area, $expectedFile, $type, $layoutUpdate, $theme, $designPackage)
        );
    }

    /**
     * Constraint for testing observer
     * event definitions in configuration
     *
     * @param string $area
     * @param string $eventName
     * @param string $observerClassAlias
     * @param string $observerMethod
     * @param string $type
     * @param string|null $observerName
     * @return EcomDev_PHPUnit_Constraint_Config
     */
    public static function configEventObserver($area, $eventName, $observerClassAlias, $observerMethod,
        $type = EcomDev_PHPUnit_Constraint_Config_EventObserver::TYPE_DEFINDED, $observerName = null)
    {
        return self::config(
            new EcomDev_PHPUnit_Constraint_Config_EventObserver($area, $eventName, $observerClassAlias, $observerMethod, $type, $observerName)
        );
    }

    /**
     * Constraint for testing observer
     * event definitions in configuration
     *
     * @param string $area
     * @param string $routeName
     * @param string $expectedValue
     * @param string $type
     * @return EcomDev_PHPUnit_Constraint_Config
     */
    public static function configRouter($area, $routeName, $expectedValue,
                                               $type = EcomDev_PHPUnit_Constraint_Config_Route::TYPE_MODULE)
    {
        return self::config(
            new EcomDev_PHPUnit_Constraint_Config_Route($area, $routeName, $type, $expectedValue)
        );
    }

    /**
     * Executes configuration constraint
     *
     * @param PHPUnit_Framework_Constraint $constraint
     * @param string  $message
     * @return void
     */
    public static function assertThatConfig(PHPUnit_Framework_Constraint $constraint, $message)
    {
        self::assertThat(Mage::getConfig(), $constraint, $message);
    }

    /**
     * Asserts that config resource for module is defined
     *
     *
     * @param string $moduleName
     * @param mixed $expectedResourceName
     * @param string $message
     */
    public static function assertSetupResourceDefined($moduleName = null, $expectedResourceName = null, $message = '')
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }
        self::assertThatConfig(
            self::configResource($moduleName,
                                 EcomDev_PHPUnit_Constraint_Config_Resource::TYPE_SETUP_DEFINED,
                                 $expectedResourceName),
            $message
        );
    }

    /**
     * Asserts that config resource for module is NOT defined
     *
     *
     * @param string $moduleName
     * @param mixed $expectedResourceName
     * @param string $message
     */
    public static function assertSetupResourceNotDefined($moduleName = null, $expectedResourceName = null, $message = '')
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }
        self::assertThatConfig(
            self::logicalNot(
                self::configResource($moduleName,
                                     EcomDev_PHPUnit_Constraint_Config_Resource::TYPE_SETUP_DEFINED,
                                     $expectedResourceName)
            ),
            $message
        );
    }

    /**
     * Asserts that config resource for module is defined and directory with the same name exists in module
     *
     *
     * @param string $moduleName
     * @param mixed $expectedResourceName
     * @param string $message
     */
    public static function assertSchemeSetupExists($moduleName = null, $expectedResourceName = null, $message = '')
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }
        self::assertThatConfig(
            self::configResource($moduleName,
                EcomDev_PHPUnit_Constraint_Config_Resource::TYPE_SETUP_SCHEME_EXISTS,
                $expectedResourceName),
            $message
        );
    }

    /**
     * Asserts that config resource for module is defined and directory with the same name exists in module
     *
     *
     * @param string $moduleName
     * @param mixed $expectedResourceName
     * @param string $message
     */
    public static function assertSchemeSetupNotExists($moduleName = null, $expectedResourceName = null, $message = '')
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }
        self::assertThatConfig(
            self::logicalNot(
                self::configResource($moduleName,
                    EcomDev_PHPUnit_Constraint_Config_Resource::TYPE_SETUP_SCHEME_EXISTS,
                    $expectedResourceName)
            ),
            $message
        );
    }

    /**
     * Asserts that config resource for module is defined and directory with the same name exists in module directory
     * for data setup scripts
     *
     *
     * @param string $moduleName
     * @param mixed $expectedResourceName
     * @param string $message
     */
    public static function assertDataSetupExists($moduleName = null, $expectedResourceName = null, $message = '')
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }
        self::assertThatConfig(
            self::configResource($moduleName,
                EcomDev_PHPUnit_Constraint_Config_Resource::TYPE_SETUP_DATA_EXISTS,
                $expectedResourceName),
            $message
        );
    }

    /**
     * Asserts that config resource for module is defined and directory with the same name exists in module
     * directory for data setup scripts
     *
     * @param string $moduleName
     * @param mixed $expectedResourceName
     * @param string $message
     */
    public static function assertDataSetupNotExists($moduleName = null, $expectedResourceName = null, $message = '')
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }
        self::assertThatConfig(
            self::logicalNot(
                self::configResource($moduleName,
                    EcomDev_PHPUnit_Constraint_Config_Resource::TYPE_SETUP_DATA_EXISTS,
                    $expectedResourceName)
            ),
            $message
        );
    }

    /**
     * Asserts that there is defined properly list of data/scheme upgrade scripts
     *
     * @param string $type
     * @param string|null $from
     * @param string|null $to
     * @param string|null $moduleName
     * @param string|null $moduleName
     * @param mixed $expectedResourceName
     * @param string $message
     */
    public static function assertSetupScriptVersions(
        $type = EcomDev_PHPUnit_Constraint_Config_Resource_Script::TYPE_SCRIPT_SCHEME, $from = null, $to = null,
        $moduleName = null, $resourceName = null,$message = '')
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        if ($to === null) {
            $moduleConfig = self::app()->getConfig()->getModuleConfig($moduleName);
            if (isset($moduleConfig->version)) {
                $to = (string)$moduleConfig->version;
            }
        }

        self::assertThatConfig(
            self::configResourceScript($moduleName,
                $type,
                array($from, $to),
                $resourceName
            ),
            $message
        );
    }

    /**
     * Asserts that there is defined properly list of scheme upgrade scripts
     *
     * @param string|null $from
     * @param string|null $to
     * @param string|null $moduleName
     * @param string|null $moduleName
     * @param mixed $expectedResourceName
     * @param string $message
     */
    public static function assertSchemeSetupScriptVersions($from = null, $to = null,
        $moduleName = null, $resourceName = null,$message = '')
    {
        self::assertSetupScriptVersions(EcomDev_PHPUnit_Constraint_Config_Resource_Script::TYPE_SCRIPT_SCHEME, $from,
                                        $to, $moduleName, $resourceName, $message);
    }

    /**
     * Asserts that there is defined properly list of data upgrade scripts
     *
     * @param string|null $from
     * @param string|null $to
     * @param string|null $moduleName
     * @param string|null $moduleName
     * @param mixed $expectedResourceName
     * @param string $message
     */
    public static function assertDataSetupScriptVersions($from = null, $to = null,
                                                           $moduleName = null, $resourceName = null,$message = '')
    {
        self::assertSetupScriptVersions(EcomDev_PHPUnit_Constraint_Config_Resource_Script::TYPE_SCRIPT_DATA, $from,
            $to, $moduleName, $resourceName, $message);
    }

    /**
     * Alias of assertSchemeSetupExists() model
     *
     *
     * @param string $moduleName
     * @param mixed $expectedResourceName
     * @param string $message
     */
    public static function assertSetupResourceExists($moduleName = null, $expectedResourceName = null, $message = '')
    {
        self::assertSchemeSetupExists($moduleName, $expectedResourceName, $message);
    }

    /**
     * Alias of assertSchemeSetupNotExists() model
     *
     *
     * @param string $moduleName
     * @param mixed $expectedResourceName
     * @param string $message
     */
    public static function assertSetupResourceNotExists($moduleName = null, $expectedResourceName = null, $message = '')
    {
        self::assertSchemeSetupNotExists($moduleName, $expectedResourceName, $message);
    }

    /**
     * Asserts that config node value is equal to the expected value.
     *
     *
     * @param string $nodePath
     * @param mixed $expectedValue
     * @param string $message
     * @param string $type type of assertion (string, xml, child, etc)
     */
    public static function assertConfigNodeValue($nodePath, $expectedValue, $message = '',
        $type = EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_STRING)
    {
        self::assertThatConfig(
            self::configNode($nodePath, $type, $expectedValue),
            $message
        );
    }

    /**
     * Asserts that config node value is not equal to the expected value.
     *
     *
     * @param string $nodePath
     * @param mixed $expectedValue
     * @param string $message
     * @param string $type type of assertion (EcomDev_PHPUnit_Constraint_Config_Node::TYPE_*)
     */
    public static function assertConfigNodeNotValue($nodePath, $expectedValue, $message = '',
        $type = EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_STRING)
    {
        self::assertThatConfig(
            self::logicalNot(
                self::configNode($nodePath, $type, $expectedValue)
            ),
            $message
        );
    }


    /**
     * Assert that configuration node $nodePath has child with tag name $childName
     *
     * @param string $nodePath
     * @param string $childName
     * @param string $message
     */
    public static function assertConfigNodeHasChild($nodePath, $childName, $message = '')
    {
        self::assertConfigNodeValue(
            $nodePath, $childName, $message,
            EcomDev_PHPUnit_Constraint_Config_Node::TYPE_HAS_CHILD
        );
    }

    /**
     * Assert that configuration node $nodePath doesn't have child with tag name $childName
     *
     * @param string $nodePath
     * @param string $childName
     * @param string $message
     */
    public static function assertConfigNodeNotHasChild($nodePath, $childName, $message = '')
    {
        self::assertConfigNodeNotValue(
            $nodePath, $childName, $message,
            EcomDev_PHPUnit_Constraint_Config_Node::TYPE_HAS_CHILD
        );
    }

    /**
     * Assert that configuration node $nodePath has children
     *
     * @param string $nodePath
     * @param string $message
     */
    public static function assertConfigNodeHasChildren($nodePath, $message = '')
    {
        self::assertConfigNodeValue(
            $nodePath, null, $message,
            EcomDev_PHPUnit_Constraint_Config_Node::TYPE_HAS_CHILDREN
        );
    }

    /**
     * Assert config node $nodePath doesn't have children
     *
     * @param string $nodePath
     * @param string $message
     */
    public static function assertConfigNodeNotHasChildren($nodePath, $message = '')
    {
        self::assertConfigNodeNotValue(
            $nodePath, null, $message,
            EcomDev_PHPUnit_Constraint_Config_Node::TYPE_HAS_CHILDREN
        );
    }

    /**
     * Assert that configuration node $nodePath contains $expectedValue in comma separated value list
     *
     * @param string $nodePath
     * @param scalar $expectedValue
     * @param string $message
     */
    public static function assertConfigNodeContainsValue($nodePath, $expectedValue, $message = '')
    {
        self::assertConfigNodeValue(
            $nodePath, $expectedValue, $message,
            EcomDev_PHPUnit_Constraint_Config_Node::TYPE_CONTAIN_VALUE
        );
    }

    /**
     * Assert that configuration node $nodePath doesn't contain $expectedValue in comma separated value list
     *
     * @param string $nodePath
     * @param scalar $expectedValue
     * @param string $message
     */
    public static function assertConfigNodeNotContainsValue($nodePath, $expectedValue, $message = '')
    {
        self::assertConfigNodeValue(
            $nodePath, $expectedValue, $message,
            EcomDev_PHPUnit_Constraint_Config_Node::TYPE_CONTAIN_VALUE
        );
    }

    /**
     * Assert config node is equal to content of simple xml element
     *
     * @param string $nodePath
     * @param SimpleXmlElement $simpleXml
     * @param string $message
     */
    public static function assertConfigNodeSimpleXml($nodePath, SimpleXmlElement $simpleXml, $message = '')
    {
         self::assertConfigNodeValue(
            $nodePath, $simpleXml, $message,
            EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_XML
        );
    }

    /**
     * Assert config node is not equal to content of simple xml element
     *
     * @param string $nodePath
     * @param SimpleXmlElement $simpleXml
     * @param string $message
     */
    public static function assertConfigNodeNotSimpleXml($nodePath, SimpleXmlElement $simpleXml, $message = '')
    {
         self::assertConfigNodeNotValue(
            $nodePath, $simpleXml, $message,
            EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_XML
        );
    }

    /**
     * Assert config node is less than expected decimal value
     *
     * @param string $nodePath
     * @param decimal $expectedValue
     * @param string $message
     */
    public static function assertConfigNodeLessThan($nodePath, $expectedValue, $message = '')
    {
         self::assertConfigNodeValue(
            $nodePath, $expectedValue, $message,
            EcomDev_PHPUnit_Constraint_Config_Node::TYPE_LESS_THAN
        );
    }

    /**
     * Assert config node is less or equals than expected decimal value
     *
     * @param string $nodePath
     * @param decimal $expectedValue
     * @param string $message
     */
    public static function assertConfigNodeLessThanOrEquals($nodePath, $expectedValue, $message = '')
    {
        self::assertThatConfig(
            self::logicalOr(
                self::configNode(
                    $nodePath,
                    EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_NUMBER,
                    $expectedValue
                ),
                self::configNode(
                    $nodePath,
                    EcomDev_PHPUnit_Constraint_Config_Node::TYPE_LESS_THAN,
                    $expectedValue
                )
           ),
           $message
       );
    }

    /**
     * Assert config node is greater than expected decimal value
     *
     * @param string $nodePath
     * @param decimal $expectedValue
     * @param string $message
     */
    public static function assertConfigNodeGreaterThan($nodePath, $expectedValue, $message = '')
    {
         self::assertConfigNodeValue(
            $nodePath, $expectedValue, $message,
            EcomDev_PHPUnit_Constraint_Config_Node::TYPE_GREATER_THAN
        );
    }

    /**
     * Assert config node is less or equalsthan expected decimal value
     *
     * @param string $nodePath
     * @param decimal $expectedValue
     * @param string $message
     */
    public static function assertConfigNodeGreaterThanOrEquals($nodePath, $expectedValue, $message = '')
    {
        self::assertThatConfig(
            self::logicalOr(
                self::configNode(
                    $nodePath,
                    EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_NUMBER,
                    $expectedValue
                ),
                self::configNode(
                    $nodePath,
                    EcomDev_PHPUnit_Constraint_Config_Node::TYPE_GREATER_THAN,
                    $expectedValue
                )
           ),
           $message
       );
    }

    /**
     * Assert that the module is active
     *
     * @param string $message
     * @param string $moduleName
     */
    public static function assertModuleIsActive($message = '', $moduleName = null)
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        self::assertThatConfig(
            self::configModule(
                $moduleName,
                EcomDev_PHPUnit_Constraint_Config_Module::TYPE_IS_ACTIVE
            ),
            $message
        );
    }

    /**
     * Assert that the module is not active
     *
     * @param string $message
     * @param string $moduleName
     */
    public static function assertModuleIsNotActive($message = '', $moduleName = null)
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        self::assertThatConfig(
            self::logicalNot(
                self::configModule(
                    $moduleName,
                    EcomDev_PHPUnit_Constraint_Config_Module::TYPE_IS_ACTIVE
                )
            ),
            $message
        );
    }

    /**
     * Assert that the module is in a particular code pool
     *
     * @param string $expected
     * @param string $message
     * @param string $moduleName
     */
    public static function assertModuleCodePool($expected, $message = '', $moduleName = null)
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        self::assertThatConfig(
            self::configModule(
                $moduleName,
                EcomDev_PHPUnit_Constraint_Config_Module::TYPE_CODE_POOL,
                $expected
            ),
            $message
        );
    }

    /**
     * Assert that the module depends on another module
     *
     * @param string $requiredModuleName
     * @param string $message
     * @param string $moduleName
     */
    public static function assertModuleDepends($requiredModuleName, $message = '', $moduleName = null)
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        self::assertThatConfig(
            self::configModule(
                $moduleName,
                EcomDev_PHPUnit_Constraint_Config_Module::TYPE_DEPENDS,
                $requiredModuleName
            ),
            $message
        );
    }

    /**
     * Assert that the module doesn't depend on another module
     *
     * @param string $requiredModuleName
     * @param string $message
     * @param string $moduleName
     */
    public static function assertModuleNotDepends($requiredModuleName, $message = '', $moduleName = null)
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        self::assertThatConfig(
            self::logicalNot(
                self::configModule(
                    $moduleName,
                    EcomDev_PHPUnit_Constraint_Config_Module::TYPE_DEPENDS,
                    $requiredModuleName
                )
            ),
            $message
        );
    }

    /**
     * Assert that the module version is equal to expected one
     *
     * @param string $expectedVersion
     * @param string $message
     * @param string $moduleName
     */
    public static function assertModuleVersion($expectedVersion, $message = '', $moduleName = null)
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        self::assertThatConfig(
            self::configModule(
                $moduleName,
                EcomDev_PHPUnit_Constraint_Config_Module::TYPE_EQUALS_VERSION,
                $expectedVersion
            ),
            $message
        );
    }

    /**
     * Assert that the module version is not equal to expected one
     *
     * @param string $expectedVersion
     * @param string $message
     * @param string $moduleName
     */
    public static function assertModuleVersionNot($expectedVersion, $message = '', $moduleName = null)
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        self::assertThatConfig(
            self::logicalNot(
                self::configModule(
                    $moduleName,
                    EcomDev_PHPUnit_Constraint_Config_Module::TYPE_EQUALS_VERSION,
                    $expectedVersion
                )
            ),
            $message
        );
    }

    /**
     * Assert that the module version is less than expected one
     *
     * @param string $expectedVersion
     * @param string $message
     * @param string $moduleName
     */
    public static function assertModuleVersionLessThan($expectedVersion, $message = '', $moduleName = null)
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        self::assertThatConfig(
            self::configModule(
                $moduleName,
                EcomDev_PHPUnit_Constraint_Config_Module::TYPE_LESS_THAN_VERSION,
                $expectedVersion
            ),
            $message
        );
    }

    /**
     * Assert that the module version is less than or equal to expected one
     *
     * @param string $expectedVersion
     * @param string $message
     * @param string $moduleName
     */
    public static function assertModuleVersionLessThanOrEquals($expectedVersion, $message = '', $moduleName = null)
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        self::assertThatConfig(
            self::logicalOr(
                self::configModule(
                    $moduleName,
                    EcomDev_PHPUnit_Constraint_Config_Module::TYPE_EQUALS_VERSION,
                    $expectedVersion
                ),
                self::configModule(
                    $moduleName,
                    EcomDev_PHPUnit_Constraint_Config_Module::TYPE_LESS_THAN_VERSION,
                    $expectedVersion
                )
            ),
            $message
        );
    }

    /**
     * Assert that the module version is greater than expected one
     *
     * @param string $expectedVersion
     * @param string $message
     * @param string $moduleName
     */
    public static function assertModuleVersionGreaterThan($expectedVersion, $message = '', $moduleName = null)
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        self::assertThatConfig(
            self::configModule(
                $moduleName,
                EcomDev_PHPUnit_Constraint_Config_Module::TYPE_GREATER_THAN_VERSION,
                $expectedVersion
            ),
            $message
        );
    }

    /**
     * Assert that the module version is greater than or equal to expected one
     *
     * @param string $expectedVersion
     * @param string $message
     * @param string $moduleName
     */
    public static function assertModuleVersionGreaterThanOrEquals($expectedVersion, $message = '', $moduleName = null)
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        self::assertThatConfig(
            self::logicalOr(
                self::configModule(
                    $moduleName,
                    EcomDev_PHPUnit_Constraint_Config_Module::TYPE_EQUALS_VERSION,
                    $expectedVersion
                ),
                self::configModule(
                    $moduleName,
                    EcomDev_PHPUnit_Constraint_Config_Module::TYPE_GREATER_THAN_VERSION,
                    $expectedVersion
                )
            ),
            $message
        );
    }


    /**
     * Assert that grouped class alias is mapped to expected class name
     *
     * @param string $group
     * @param string $classAlias
     * @param string $expectedClassName
     * @param string $message
     */
    public static function assertGroupedClassAlias($group, $classAlias, $expectedClassName, $message = '')
    {
        self::assertThatConfig(
            self::configClassAlias($group, $classAlias, $expectedClassName),
            $message
        );
    }

    /**
     * Assert that grouped class alias is not mapped to expected class name
     *
     * @param string $group
     * @param string $classAlias
     * @param string $expectedClassName
     * @param string $message
     */
    public static function assertGroupedClassAliasNot($group, $classAlias, $expectedClassName, $message = '')
    {
        self::assertThatConfig(
            self::logicalNot(
                self::configClassAlias($group, $classAlias, $expectedClassName)
            ),
            $message
        );
    }

    /**
     * Assert that block alias is mapped to expected class name
     *
     * @param string $classAlias
     * @param string $expectedClassName
     * @param string $message
     */
    public static function assertBlockAlias($classAlias, $expectedClassName, $message = '')
    {
        self::assertGroupedClassAlias(
            EcomDev_PHPUnit_Constraint_Config_ClassAlias::GROUP_BLOCK,
            $classAlias,
            $expectedClassName,
            $message
        );
    }

    /**
     * Assert that block alias is not mapped to expected class name
     *
     * @param string $classAlias
     * @param string $expectedClassName
     * @param string $message
     */
    public static function assertBlockAliasNot($classAlias, $expectedClassName, $message = '')
    {
        self::assertGroupedClassAliasNot(
            EcomDev_PHPUnit_Constraint_Config_ClassAlias::GROUP_BLOCK,
            $classAlias,
            $expectedClassName,
            $message
        );
    }

    /**
     * Assert that model alias is mapped to expected class name
     *
     * @param string $classAlias
     * @param string $expectedClassName
     * @param string $message
     */
    public static function assertModelAlias($classAlias, $expectedClassName, $message = '')
    {
        self::assertGroupedClassAlias(
            EcomDev_PHPUnit_Constraint_Config_ClassAlias::GROUP_MODEL,
            $classAlias,
            $expectedClassName,
            $message
        );
    }

    /**
     * Assert that model alias is not mapped to expected class name
     *
     * @param string $classAlias
     * @param string $expectedClassName
     * @param string $message
     */
    public static function assertModelAliasNot($classAlias, $expectedClassName, $message = '')
    {
        self::assertGroupedClassAliasNot(
            EcomDev_PHPUnit_Constraint_Config_ClassAlias::GROUP_MODEL,
            $classAlias,
            $expectedClassName,
            $message
        );
    }

    /**
     * Assert that resource model alias is mapped to expected class name
     *
     * @param string $classAlias
     * @param string $expectedClassName
     * @param string $message
     */
    public static function assertResourceModelAlias($classAlias, $expectedClassName, $message = '')
    {
        $classAlias = Mage::getConfig()->getRealResourceModelClassAlias($classAlias);

        self::assertGroupedClassAlias(
            EcomDev_PHPUnit_Constraint_Config_ClassAlias::GROUP_MODEL,
            $classAlias,
            $expectedClassName,
            $message
        );
    }

    /**
     * Assert that resource model alias is not mapped to expected class name
     *
     * @param string $classAlias
     * @param string $expectedClassName
     * @param string $message
     */
    public static function assertResourceModelAliasNot($classAlias, $expectedClassName, $message = '')
    {
        $classAlias = Mage::getConfig()->getRealResourceModelClassAlias($classAlias);

        self::assertGroupedClassAliasNot(
            EcomDev_PHPUnit_Constraint_Config_ClassAlias::GROUP_MODEL,
            $classAlias,
            $expectedClassName,
            $message
        );
    }

    /**
     * Assert that table alias is mapped to expected table name
     *
     * @param string $tableAlias
     * @param string $expectedTableName
     * @param string $message
     */
    public static function assertTableAlias($tableAlias, $expectedTableName, $message = '')
    {
        self::assertThatConfig(
            self::configTableAlias($tableAlias, $expectedTableName),
            $message
        );
    }

    /**
     * Assert that table alias is NOT mapped to expected table name
     *
     * @param string $tableAlias
     * @param string $expectedTableName
     * @param string $message
     */
    public static function assertTableAliasNot($tableAlias, $expectedTableName, $message = '')
    {
        self::assertThatConfig(
            self::logicalNot(
                self::configTableAlias($tableAlias, $expectedTableName)
            ),
            $message
        );
    }

    /**
     * Assert that helper alias is mapped to expected class name
     *
     * @param string $classAlias
     * @param string $expectedClassName
     * @param string $message
     */
    public static function assertHelperAlias($classAlias, $expectedClassName, $message = '')
    {
        self::assertGroupedClassAlias(
            EcomDev_PHPUnit_Constraint_Config_ClassAlias::GROUP_HELPER,
            $classAlias,
            $expectedClassName,
            $message
        );
    }

    /**
     * Assert that helper alias is not mapped to expected class name
     *
     * @param string $classAlias
     * @param string $expectedClassName
     * @param string $message
     */
    public static function assertHelperAliasNot($classAlias, $expectedClassName, $message = '')
    {
        self::assertGroupedClassAliasNot(
            EcomDev_PHPUnit_Constraint_Config_ClassAlias::GROUP_HELPER,
            $classAlias,
            $expectedClassName,
            $message
        );
    }

    /**
     * Assert that configuration has definition of the layout file.
     * If layout update name is specified, then it will restrict assertion by it.
     *
     * @param string $area (frontend|adminhtml)
     * @param string $expectedFileName
     * @param string $layoutUpdate
     * @param string $message
     */
    public static function assertLayoutFileDefined($area, $expectedFileName, $layoutUpdate = null, $message = '')
    {
        self::assertThatConfig(
            self::configLayout(
                $area, $expectedFileName,
                EcomDev_PHPUnit_Constraint_Config_Layout::TYPE_LAYOUT_DEFINITION,
                $layoutUpdate
            ),
            $message
        );
    }

    /**
     * Asserts that layout file exists in current design package
     *
     * @param string $area (frontend|adminhtml)
     * @param string $expectedFileName
     * @param string $message
     */
    public static function assertLayoutFileExists($area, $expectedFileName, $message = '')
    {
        self::assertLayoutFileDefined($area, $expectedFileName, null, $message);

        self::assertThatConfig(
            self::configLayout(
                $area, $expectedFileName,
                EcomDev_PHPUnit_Constraint_Config_Layout::TYPE_LAYOUT_FILE
            ),
            $message
        );
    }

    /**
     * Asserts that layout file exists in a particular theme and design package
     *
     * @param string $area (frontend|adminhtml)
     * @param string $expectedFileName
     * @param string $message
     */
    public static function assertLayoutFileExistsInTheme($area, $expectedFileName, $theme,
        $designPackage = null, $message = '')
    {
        self::assertLayoutFileDefined($area, $expectedFileName, null, $message);

        self::assertThatConfig(
            self::configLayout(
                $area, $expectedFileName,
                EcomDev_PHPUnit_Constraint_Config_Layout::TYPE_LAYOUT_FILE,
                null,
                $theme,
                $designPackage
            ),
            $message
        );
    }

    /**
     * Asserts that event observer is defined for an event
     * and not disabled. If observer name is defined, it additionaly checks it.
     *
     * @param string $area
     * @param string $eventName
     * @param string $observerClassAlias
     * @param string $observerMethod
     * @param string|null $observerName
     * @param string $message
     */
    public static function assertEventObserverDefined($area, $eventName, $observerClassAlias,
        $observerMethod, $observerName = null, $message = '')
    {
        self::assertThatConfig(
            self::configEventObserver(
                $area, $eventName, $observerClassAlias, $observerMethod,
                EcomDev_PHPUnit_Constraint_Config_EventObserver::TYPE_DEFINDED,
                $observerName
            ),
            $message
        );
    }

    /**
     * Asserts that event observer is not defined for an event
     * or disabled.
     * If observer name is defined, it additionaly checks it.
     *
     * @param string $area
     * @param string $eventName
     * @param string $observerClassAlias
     * @param string $observerMethod
     * @param string|null $observerName
     * @param string $message
     */
    public static function assertEventObserverNotDefined($area, $eventName, $observerClassAlias,
        $observerMethod, $observerName = null, $message = '')
    {
        self::assertThatConfig(
            self::logicalNot(
                self::configEventObserver(
                    $area, $eventName, $observerClassAlias, $observerMethod,
                    EcomDev_PHPUnit_Constraint_Config_EventObserver::TYPE_DEFINDED,
                    $observerName
                )
            ),
            $message
        );
    }

    /**
     * Asserts that route frontName is the same as expected one
     *
     * If $frontName is empty, it uses $routeName as its expected value
     *
     * @param string $routeName
     * @param string|null $frontName
     * @param string $area
     * @param string $message
     */
    public static function assertRouteFrontName($routeName, $frontName = null,
                                                $area = EcomDev_PHPUnit_Model_App::AREA_FRONTEND, $message = '')
    {
        if ($frontName === null) {
            $frontName = $routeName;
        }
        self::assertThatConfig(
            self::configRouter(
                $area, $routeName, $frontName, EcomDev_PHPUnit_Constraint_Config_Route::TYPE_FRONT_NAME
            ),
            $message
        );
    }

    /**
     * Asserts that route frontName is NOT the same as expected one
     *
     * If $frontName is empty, it uses $routeName as its expected value
     *
     * @param string $routeName
     * @param string|null $frontName
     * @param string $area
     * @param string $message
     */
    public static function assertRouteFrontNameNot($routeName, $frontName = null,
                                                   $area = EcomDev_PHPUnit_Model_App::AREA_FRONTEND, $message = '')
    {
        if ($frontName === null) {
            $frontName = $routeName;
        }
        self::assertThatConfig(
            self::logicalNot(
                self::configRouter(
                    $area, $routeName, $frontName, EcomDev_PHPUnit_Constraint_Config_Route::TYPE_FRONT_NAME
                )
            ),
            $message
        );
    }

    /**
     * Asserts that route belongs to expected router
     *
     * @param string $routeName
     * @param string $routerName
     * @param string $area
     * @param string $message
     */
    public static function assertRouteIn($routeName, $routerName = 'standard',
                                         $area = EcomDev_PHPUnit_Model_App::AREA_FRONTEND, $message = '')
    {
        self::assertThatConfig(
            self::configRouter(
                $area, $routeName, $routerName, EcomDev_PHPUnit_Constraint_Config_Route::TYPE_ROUTER
            ),
            $message
        );
    }

    /**
     * Asserts that route DOES NOT belong to expected router
     *
     * @param string $routeName
     * @param string $routerName
     * @param string $area
     * @param string $message
     */
    public static function assertRouteNotIn($routeName, $routerName = 'standard',
                                            $area = EcomDev_PHPUnit_Model_App::AREA_FRONTEND, $message = '')
    {
        self::assertThatConfig(
            self::logicalNot(
                self::configRouter(
                    $area, $routeName, $routerName, EcomDev_PHPUnit_Constraint_Config_Route::TYPE_ROUTER
                )
            ),
            $message
        );
    }

    /**
     * Asserts that route contains module as controller resolve path
     *
     * @param string $routeName
     * @param string|null   $moduleName
     * @param string $area
     * @param string $message
     */
    public static function assertRouteModule($routeName, $moduleName = null,
                                             $area = EcomDev_PHPUnit_Model_App::AREA_FRONTEND, $message = '')
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }
        self::assertThatConfig(
            self::configRouter(
                $area, $routeName, $moduleName
            ),
            $message
        );
    }

    /**
     * Asserts that route DOES NOT contain module as controller resolve path
     *
     * @param string $routeName
     * @param string|null   $moduleName
     * @param string $area
     * @param string $message
     */
    public static function assertRouteModuleNot($routeName, $moduleName = null,
                                                $area = EcomDev_PHPUnit_Model_App::AREA_FRONTEND, $message = '')
    {
        if ($moduleName === null) {
            $moduleName = self::getModuleNameFromCallStack();
        }

        self::assertThatConfig(
            self::logicalNot(
                self::configRouter(
                    $area, $routeName, $moduleName
                )
            ),
            $message
        );
    }

    /**
     * Asserts that route contains all expected modules in the same order in its controller resolve path
     *
     * @param string $routeName
     * @param array  $expectedModuleNames
     * @param string $area
     * @param string $message
     */
    public static function assertRouteModuleOrder($routeName, array $expectedModuleNames,
                                                  $area = EcomDev_PHPUnit_Model_App::AREA_FRONTEND, $message = '')
    {
        self::assertThatConfig(
            self::configRouter(
                $area, $routeName, $expectedModuleNames, EcomDev_PHPUnit_Constraint_Config_Route::TYPE_MODULE_ORDER
            ),
            $message
        );
    }

    /**
     * Asserts that route DOES NOT contain all expected modules in the same order in its controller resolve path
     *
     * @param string $routeName
     * @param array  $expectedModuleNames
     * @param string $area
     * @param string $message
     */
    public static function assertRouteModuleOrderNot($routeName, array $expectedModuleNames,
                                                        $area = EcomDev_PHPUnit_Model_App::AREA_FRONTEND, $message = '')
    {
        self::assertThatConfig(
            self::logicalNot(
                self::configRouter(
                    $area, $routeName, $expectedModuleNames, EcomDev_PHPUnit_Constraint_Config_Route::TYPE_MODULE_ORDER
                )
            ),
            $message
        );
    }

    /**
     * Asserts that default config value matches expected type of constraint with expected value
     *
     * @param string $nodePath
     * @param mixed  $expectedValue
     * @param string $message
     * @param string $type
     */
    public static function assertDefaultConfigValue($nodePath, $expectedValue, $message = '',
                                                    $type = EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_STRING)
    {
        $nodePath = 'default/' . $nodePath;
        self::assertConfigNodeValue($nodePath, $expectedValue, $message, $type);
    }

    /**
     * Asserts that default config value DOES NOT match expected type of constraint with expected value
     *
     * @param string $nodePath
     * @param mixed  $expectedValue
     * @param string $message
     * @param string $type
     */
    public static function assertDefaultConfigValueNot($nodePath, $expectedValue, $message = '',
                                                    $type = EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_STRING)
    {
        $nodePath = 'default/' . $nodePath;
        self::assertConfigNodeNotValue($nodePath, $expectedValue, $message, $type);
    }

    /**
     * Asserts that store config value matches expected type of constraint with expected value
     *
     * @param string|int $store
     * @param string $nodePath
     * @param mixed  $expectedValue
     * @param string $message
     * @param string $type
     */
    public static function assertStoreConfigValue($store, $nodePath, $expectedValue, $message = '',
                                                  $type = EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_STRING)
    {
        $nodePath = 'stores/' . self::app()->getStore($store)->getCode() . '/' . $nodePath;
        self::assertConfigNodeValue($nodePath, $expectedValue, $message, $type);
    }

    /**
     * Asserts that store config value DOES NOT match expected type of constraint with expected value
     *
     * @param string|int $store
     * @param string $nodePath
     * @param mixed  $expectedValue
     * @param string $message
     * @param string $type
     */
    public static function assertStoreConfigValueNot($store, $nodePath, $expectedValue, $message = '',
                                                     $type = EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_STRING)
    {
        $nodePath = 'stores/' . self::app()->getStore($store)->getCode() . '/' . $nodePath;
        self::assertConfigNodeNotValue($nodePath, $expectedValue, $message, $type);
    }

    /**
     * Asserts that website config value matches expected type of constraint with expected value
     *
     * @param string|int $website
     * @param string $nodePath
     * @param mixed  $expectedValue
     * @param string $message
     * @param string $type
     */
    public static function assertWebsiteConfigValue($website, $nodePath, $expectedValue, $message = '',
                                                    $type = EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_STRING)
    {
        $nodePath = 'websites/' . self::app()->getWebsite($website)->getCode() . '/' . $nodePath;
        self::assertConfigNodeValue($nodePath, $expectedValue, $message, $type);
    }

    /**
     * Asserts that website config value DOES NOT match expected type of constraint with expected value
     *
     * @param string|int $website
     * @param string $nodePath
     * @param mixed  $expectedValue
     * @param string $message
     * @param string $type
     */
    public static function assertWebsiteConfigValueNot($website, $nodePath, $expectedValue, $message = '',
                                                       $type = EcomDev_PHPUnit_Constraint_Config_Node::TYPE_EQUALS_STRING)
    {
        $nodePath = 'websites/' . self::app()->getWebsite($website)->getCode() . '/' . $nodePath;
        self::assertConfigNodeNotValue($nodePath, $expectedValue, $message, $type);
    }


    /**
     * Assert that a menu in the backend exists.
     *
     * @param        $node
     * @param string $message
     *
     * @return void
     */
    public static function assertAdminhtmlMenu($node, $message = '')
    {
        $menuNode = self::_getAdminhtmlMenu();

        if (!$message)
        {
            $message = sprintf('Failed asserting that menu %s is set.', $node);
        }

        self::assertThat(
            $menuNode->xpath($node),
            self::logicalNot(self::isEmpty()),
            $message
        );
    }


    /**
     * Assert that a menu in the backend will run a specific action.
     *
     * @param        $node
     * @param        $expectedAction
     * @param string $message
     *
     * @return void
     */
    public static function assertAdminhtmlMenuAction($node, $expectedAction, $message = '')
    {
        $menuNode = self::_getAdminhtmlMenu();

        if (!$message)
        { // no message: generate default
            $message = sprintf('Failed asserting that menu %s has a proper action.', $node);
        }

        if (array() == ($nodeValue = $menuNode->xpath($node . '/action')))
        { // empty: not found
            self::fail(sprintf('Adminhtml menu %s could not be found.', $node));
        }

        self::assertThat(
            $expectedAction,
            self::equalTo((string) $nodeValue[0]),
            $message
        );
    }


    /**
     * Assert that a menu in the backend will be fetched by a router.
     *
     * @param        $node
     * @param string $message
     *
     * @return void
     */
    public static function assertAdminhtmlMenuHasRouter($node, $message = '')
    {
        $menuNode = self::_getAdminhtmlMenu();

        if (!$message)
        { // no message: generate default
            $message = sprintf(
                'Failed asserting that menu %s action will be fetched by a router.',
                $node
            );
        }

        if (array() == ($nodeValue = $menuNode->xpath($node . '/action')))
        { // empty: menu not found
            self::fail(sprintf('Adminhtml menu %s could not be found.', $node));
        }

        $route = (string) $nodeValue[0];

        // fetch module, controller and action if given
        $routeSet   = explode('/', $route);
        $module     = array_shift($routeSet);
        $controller = array_shift($routeSet);
        $action     = array_shift($routeSet);
        $found      = self::_validateRoute($module, $controller, $action);

        self::assertThat(
            $found,
            self::isTrue(),
            $message
        );
    }
}
