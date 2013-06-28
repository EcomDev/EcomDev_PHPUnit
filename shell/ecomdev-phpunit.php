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


require_once 'abstract.php';

// Only this workaround fixes Magento core issue in 1.8 :(
$abstractShell = new ReflectionClass('Mage_Shell_Abstract');

define('PHPUNIT_MAGE_PATH', dirname(dirname($abstractShell->getFileName())));

$appFile = PHPUNIT_MAGE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';

require_once $appFile;

/**
 * Shell script for autoinstalling of required files for phpunit extension
 *
 *
 */
class EcomDev_PHPUnit_Install extends Mage_Shell_Abstract
{
    const FILE_LOCAL_XML = 'app/etc/local.xml.phpunit';
    const FILE_PHPUNIT_XML = 'phpunit.xml.dist';
    const FILE_CONFIG_XML = 'app/code/community/EcomDev/PHPUnit/etc/config.xml';

    const OLD_FILE_MATCH = '/\\<file\\>UnitTests.php\\<\\/file\\>/';

    /**
     * Config xml value map
     *
     * @var array[]
     */
    protected $_valuesMap = array(
        'db-host' => array(
            'type' => 'string',
            'nullable' => true,
            'path'     => '//global/resources/default_setup/connection/host'
        ),
        'db-user' => array(
            'type' => 'string',
            'nullable' => true,
            'path'     => '//global/resources/default_setup/connection/username'
        ),
        'db-pwd'  => array(
            'type' => 'string',
            'nullable' => true,
            'path'     => '//global/resources/default_setup/connection/password'
        ),
        'db-name' => array(
            'type' => 'string',
            'path'     => '//global/resources/default_setup/connection/dbname'
        ),
        'base-url' => array(
            'type' => 'string',
            'path'     => array(
                '//default/web/secure/base_url',
                '//default/web/unsecure/base_url'
            )
        ),
        'same-db'  => array(
            'type'     => 'boolean',
            'path'     => '//phpunit/allow_same_db'
        ),
        'url-rewrite' => array(
            'type'     => 'boolean',
            'path'     => '//default/web/seo/use_rewrites'
        )
    );

    /**
     * This script doesn't need initialization of Magento
     *
     * @var bool
     */
    protected $_includeMage = false;

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f ecomdev-phpunit.php -- -a <action> <options>

  -h --help                 Shows usage

  --action -a <action>      Perform one of the defined actions below

Defined actions:

  install                    Copies required extension files if they were not created before
    -r --rewrite             Overrides phpunit.xml.dist file, even if it exists

  cache                      Clears magento and phpunit cache directories

  phpunit-config             Copies phpunit.xml.dist file from extension if it doesn't exist
    -r --rewrite             Overrides phpunit.xml.dist file, even if it exists

  magento-config             Updates settings in local.xml.phpunit file to change settings
    --db-host     <string>   Changes test DB host
    --db-name     <string>   Changes test DB name
    --db-user     <string>   Changes test DB username
    --db-pwd      <string>   Changes test DB password
    --same-db     <bool>     Changes same db usage flag for unit tests
    --url-rewrite <bool>     Changes use of url rewrites for unit tests
    --base-url    <string>   Changes base url for controller tests

  show-version               Shows current version of the module

  change-status              Changes status of EcomDev_PHPUnitTest module, that contains built in supplied tests
    --enable                 Used to determine the status of it. If not specified, it will be disabled

  fix-autoloader             Patches Varien_Autoload class to suppress include warning, that breaks class_exists().

USAGE;
    }

    /**
     * Runs scripts itself
     */
    public function run()
    {
        if (!$this->getArg('action') && !$this->getArg('a')) {
            die($this->usageHelp());
        }

        $this->_args['module'] = dirname(dirname(__FILE__));
        $this->_args['project'] = PHPUNIT_MAGE_PATH;

        $action = $this->getArg('action') ?: $this->getArg('a');
        switch ($action) {
            case 'install':
                // Installation is silent
                $this->_copyLocalXml();
                $this->_copyPHPUnitXml();
                $this->_cleanCache();
                break;
            case 'cache':
                $this->_cleanCache();
                echo "Cache was cleared\n";
                break;
            case 'phpunit-config':
                $this->_copyPHPUnitXml();
                echo "phpunit.xml.dist file was copied/updated\n";
                break;
            case 'magento-config':
                $this->_updateLocalXml();
                break;
            case 'change-status':
                $this->_changeBuiltInTestStatus($this->getArg('enable'));
                $this->_cleanCache();
                echo "EcomDev_PHPUnitTest module status was changed\n";
                break;
            case 'fix-autoloader':
                $this->_fixAutoloader();
                break;
            case 'show-version':
                $version = $this->_retrieveVersion();
                $this->_cleanCache();
                echo "EcomDev_PHPUnit module version is {$version} \n";
                break;
            default:
                $this->_showHelp();
                break;
        }
    }

    /**
     * Copies local XML file from phpunit extension folder
     *
     */
    protected function _copyLocalXml()
    {
        if (!file_exists($this->getArg('project') . DIRECTORY_SEPARATOR . self::FILE_LOCAL_XML)) {
            copy($this->getArg('module') . DIRECTORY_SEPARATOR . self::FILE_LOCAL_XML,
                 $this->getArg('project') . DIRECTORY_SEPARATOR . self::FILE_LOCAL_XML);
        }
    }

    /**
     * Checks existence of phpunit.xml.dist file, if file is outdated,
     * it just replaces the content of it.
     *
     */
    protected function _copyPHPUnitXml()
    {
        if ($this->getArg('rewrite') || $this->getArg('r')
            || !file_exists($this->getArg('project') . DIRECTORY_SEPARATOR . self::FILE_PHPUNIT_XML)
            || preg_match(self::OLD_FILE_MATCH,
                          file_get_contents($this->getArg('project') . DIRECTORY_SEPARATOR . self::FILE_PHPUNIT_XML))) {
            copy($this->getArg('module') . DIRECTORY_SEPARATOR . self::FILE_PHPUNIT_XML,
                $this->getArg('project') . DIRECTORY_SEPARATOR . self::FILE_PHPUNIT_XML);
        }
    }

    /**
     * Clears cache of the magento project
     *
     *
     */
    protected function _cleanCache()
    {
        if (is_dir($this->getArg('project') . '/var/cache')) {
            shell_exec('rm -rf ' . $this->getArg('project') . '/var/cache');
        }

        if (is_dir($this->getArg('project') . '/var/phpunit.cache')) {
            shell_exec('rm -rf ' . $this->getArg('project') . '/var/phpunit.cache');
        }
    }

    /**
     * Changes extension suite internal tests status
     *
     * @param bool $status
     */
    protected function _changeBuiltInTestStatus($status)
    {
        if (!file_exists($this->getArg('project') . '/app/etc/modules/EcomDev_PHPUnitTest.xml')) {
            die('Cannot find EcomDev_PHPUnitTest.xml file in app/etc/modules directory');
        }
        $disableFile = $this->getArg('project') . '/app/etc/modules/ZDisable_EcomDev_PHPUnitTest.xml';
        if ($status && file_exists($disableFile)) {
            unlink($disableFile);
        } elseif (!$status && !file_exists($disableFile))  {
            $fileContent = new Varien_Simplexml_Element('<config />');
            $fileContent->addChild('modules')
                ->addChild('EcomDev_PHPUnitTest')
                ->addChild('active', 'false');
            $fileContent->asNiceXml($disableFile);
        }
    }

    /**
     * Updates local.xml.phpunit values
     *
     */
    protected function _updateLocalXml()
    {
        $localXml = $this->getArg('project') . DIRECTORY_SEPARATOR . self::FILE_LOCAL_XML;
        if (!file_exists($localXml)) {
            die('Cannot find local.xml.phpunit file in app/etc directory');
        }

        /* @var $localXmlConfig Varien_Simplexml_Element */
        $localXmlConfig = simplexml_load_file($localXml,  'Varien_Simplexml_Element');

        foreach ($this->_args as $name => $value) {
            if (isset($this->_valuesMap[$name])) {
                $info = $this->_valuesMap[$name];
                if (empty($info['nullable']) && $value === true) {
                    die("--$name value should be specified\n".$this->usageHelp());
                }
                if (!is_array($info['path'])) {
                    $info['path'] = array($info['path']);
                }
                foreach ($info['path'] as $path) {
                    /** @var $currentElement Varien_Simplexml_Element */
                    $currentElement = current($localXmlConfig->xpath($path));

                    if (!$currentElement) {
                        if ($value === true) {
                            continue;
                        }

                        $parents = explode('/', ltrim($path, '/'));
                        // Remove last item, since it is our element
                        $currentElementName = array_pop($parents);

                        $parentElement = $localXmlConfig;
                        foreach ($parents as $parent) {
                            if (!isset($parentElement->$parent)) {
                                $parentElement->$parent = null;
                            }
                            $parentElement = $parentElement->$parent;
                        }
                    } else {
                        $parentElement = $currentElement->getParent();
                        $currentElementName = $currentElement->getName();
                    }

                    if ($currentElement) {
                        unset($parentElement->$currentElementName);
                    }

                    if ($value !== true) {
                        if ($info['type'] === 'boolean') {
                            $value = $value ? '1' : '0';
                        }

                        $parentElement->$currentElementName = $value;
                        printf("Changed value to %s for %s node\n", $value, $path);
                    } else {
                        printf("Removed %s node\n", $path);
                    }
                }
            }
        }

        if (isset($currentElement)) {
            $localXmlConfig->asNiceXml($localXml);
            printf("Saved updated configuration at %s\n", $localXml);
        }
    }

    /**
     * Fixes Varien_Autoloader problem on phpunit test cases
     *
     *
     */
    protected function _fixAutoloader()
    {
        $autoloaderFile = $this->getArg('project') . DIRECTORY_SEPARATOR . 'lib/Varien/Autoload.php';

        file_put_contents(
            $autoloaderFile,
            str_replace(
                'return include $classFile;',
                'return @include $classFile;',
                file_get_contents($autoloaderFile)
            )
        );

        echo "Varien_Autoloader was patched\n";
    }

    /**
     * Retrieve current module version from the config.xml file
     *
     * @return string
     */
    protected function _retrieveVersion()
    {
        if (!file_exists($this->getArg('project') . DS . self::FILE_CONFIG_XML)) {
            die('Cannot find module config file!');
        }

        $configFilePath = $this->getArg('project') . DS . self::FILE_CONFIG_XML;

        /** @var $moduleConfigXml Varien_Simplexml_Element */
        $moduleConfigXml = simplexml_load_file($configFilePath,  'Varien_Simplexml_Element');

        if (!isset($moduleConfigXml->modules->EcomDev_PHPUnit->version)) {
            die('Cannot retrieve module version!');
        }
        $version = $moduleConfigXml->modules->EcomDev_PHPUnit->version;

        return $version;
    }
}

$shell = new EcomDev_PHPUnit_Install();
$shell->run();
