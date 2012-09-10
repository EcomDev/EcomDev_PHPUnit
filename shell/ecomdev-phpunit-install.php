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
 * @copyright  Copyright (c) 2012 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

require_once 'abstract.php';

/**
 * Shell script for autoinstalling of required files for phpunit extension
 *
 *
 */
class EcomDev_PHPUnit_Install extends Mage_Shell_Abstract
{
    const FILE_LOCAL_XML = 'app/etc/local.xml.phpunit';
    const FILE_PHPUNIT_XML = 'phpunit.xml.dist';

    const OLD_FILE_MATCH = '/\\<file\\>UnitTests.php\\<\\/file\\>/';

    /**
     * This script doesn't need initialization of Magento
     *
     * @var bool
     */
    protected $_includeMage = false;

    /**
     * Runs scripts itself
     */
    public function run()
    {
        if (!$this->getArg('module') || !$this->getArg('project')) {
            die($this->usageHelp());
        }

        $this->_copyLocalXml();
        $this->_copyPHPUnitXml();
    }

    /**
     * Copies local XML file from phpunit extension folder
     *
     */
    protected function _copyLocalXml()
    {
        if (!file_exists($this->getArg('project') . DIRECTORY_SEPARATOR . self::FILE_LOCAL_XML)) {
            copy($this->getArg('module') . DIRECTORY_SEPARATOR . self::FILE_LOCAL_XML,
                 $this->getArg('project') . DIRECTORY_SEPARATOR . self::FILE_PHPUNIT_XML);
        }
    }

    /**
     * Checks existence of phpunit.xml.dist file, if file is outdated,
     * it just replaces the content of it.
     *
     */
    protected function _copyPHPUnitXml()
    {
        if (!file_exists($this->getArg('project') . DIRECTORY_SEPARATOR . self::FILE_PHPUNIT_XML)
            || preg_match(self::OLD_FILE_MATCH,
                          file_get_contents($this->getArg('project') . DIRECTORY_SEPARATOR . self::FILE_PHPUNIT_XML))) {
            copy($this->getArg('module') . DIRECTORY_SEPARATOR . self::FILE_PHPUNIT_XML,
                $this->getArg('project') . DIRECTORY_SEPARATOR . self::FILE_PHPUNIT_XML);
        }
    }
}
