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
 * Interface for assertions in layout configuration
 *
 */
interface EcomDev_PHPUnit_Design_PackageInterface
{
    /**
     * Asserts layout file existence in design packages,
     * and returns actual and expected file names as result
     *
     * @param string $fileName
     * @param string $area
     * @param string|null $designPackage if not specified any theme will be used
     * @param string|null $theme if not specified any theme will be used
     * @return array of 'expected' and 'actual' file names
     */
    public function getLayoutFileAssertion($fileName, $area, $designPackage = null, $theme = null);

}
