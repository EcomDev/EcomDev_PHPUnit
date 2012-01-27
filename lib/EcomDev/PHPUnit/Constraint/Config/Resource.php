<?php
/**
 * Setup resources configuration constraint
 *
 */
class EcomDev_PHPUnit_Constraint_Config_Resource
    extends EcomDev_PHPUnit_Constraint_Config_Abstract
{
    const XML_PATH_RESOURCES_NODE = 'global/resources';

    const TYPE_SETUP_DEFINED = 'setup_defined';
    const TYPE_SETUP_EXISTS = 'setup_exists';

    /**
     * Name of the module for constraint
     *
     * @var string
     */
    protected $_moduleName = null;
    
    /**
     * The module directory for constraint
     *
     * @var string
     */
    protected $_moduleDirectory = null;

    /**
     * Contraint for evaluation of module config node
     *
     * @param string $nodePath
     * @param string $type
     * @param string $moduleDirectory
     * @param mixed $expectedValue
     */
    public function __construct($moduleName, $type, $moduleDirectory = null, $expectedValue = null)
    {
        $this->_expectedValueValidation += array(
            self::TYPE_SETUP_DEFINED => array(false, 'is_string', 'string'),
            self::TYPE_SETUP_EXISTS => array(false, 'is_string', 'string'),
        );

        $this->_typesWithDiff[] = self::TYPE_SETUP_DEFINED;
        $this->_typesWithDiff[] = self::TYPE_SETUP_EXISTS;

        parent::__construct(
            self::XML_PATH_RESOURCES_NODE,
            $type,
            $expectedValue
        );

        $this->_moduleName = $moduleName;
        $this->_moduleDirectory = $moduleDirectory;
        
        if ($this->_type === self::TYPE_SETUP_EXISTS 
            && !is_dir($moduleDirectory)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(3, 'real directory', $moduleDirectory);
        }
    }
    
    /**
     * Returns list of module setup resources
     * 
     * @param Varien_Simplexml_Element $xml
     * @return array
     */
    protected function getModuleSetupResources(Varien_Simplexml_Element $xml)
    {
        $resourcesForModule = array(); 
        foreach ($xml->children() as $resourceNode) {
            if (isset($resourceNode->setup->module) 
                && (string)$resourceNode->setup->module === $this->_moduleName) {
                $resourcesForModule[] = $resourceNode->getName();
            }
        }
        
        return $resourcesForModule;
    }
    
    /**
     * Checks definition of expected resource name
     *
     * @param Varien_Simplexml_Element $other
     */
    protected function evaluateSetupDefined($other)
    {
        $moduleResources = $this->getModuleSetupResources($other);
        
        if ($this->_expectedValue === null) {
            $this->_expectedValue = empty($moduleResources) ? 
                                    strtolower($this->_moduleName) . '_setup' : 
                                    current($moduleResources);
        }
        
        $this->setActualValue($moduleResources);
        
        return in_array($this->_expectedValue, $this->_actualValue);
    }
    
    /**
     * Represents contraint for definition of setup resources
     * 
     * @return string
     */
    public function textSetupDefined()
    {
        return sprintf('contains resource definition for %s module with %s name', $this->_moduleName, $this->_expectedValue);
    }
    
    /**
     * Checks existanse and definition of expected resource name
     *
     * @param Varien_Simplexml_Element $other
     */
    protected function evaluateSetupExists($other)
    {
        $moduleResources = $this->getModuleSetupResources($other);
        
        if ($this->_expectedValue === null) {
            $this->_expectedValue = empty($moduleResources) ? 
                                    strtolower($this->_moduleName) . '_setup' : 
                                    current($moduleResources);
        }
        
        if (!is_dir($this->_moduleDirectory . DIRECTORY_SEPARATOR . 'sql')) {
            $this->setActualValue(array());
            return false;
        }
        
        $dirIterator = new DirectoryIterator($this->_moduleDirectory . DIRECTORY_SEPARATOR . 'sql');
        
        $resourceDirectories = array();
        
        foreach ($dirIterator as $entry) {
            if ($entry->isDir() && !$entry->isDot()) {
                $resourceDirectories[] = $entry->getBasename();
            }
        }
        
        $this->setActualValue($resourceDirectories);
        
        return in_array($this->_expectedValue, $moduleResources) 
               && in_array($this->_expectedValue, $this->_actualValue);
    }
    
    /**
     * Represents contraint for definition of setup resources
     * 
     * @return string
     */
    public function textSetupExists()
    {
        return sprintf('are defined or created directory for it in sql one of %s module with %s name', $this->_moduleName, $this->_expectedValue);
    }
    
    /**
     * Custom failure description for showing config related errors
     * (non-PHPdoc)
     * @see PHPUnit_Framework_Constraint::customFailureDescription()
     */
    protected function customFailureDescription($other, $description, $not)
    {
        return sprintf(
            'Failed asserting that setup resources %s.',
            $this->toString()
        );
    }
}
