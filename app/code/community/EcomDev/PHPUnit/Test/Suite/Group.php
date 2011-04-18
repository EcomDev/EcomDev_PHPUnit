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
 * Test suite for a group of tests (e.g. tests from the same class)
 *
 */
class EcomDev_PHPUnit_Test_Suite_Group extends PHPUnit_Framework_TestSuite
{
    const NO_GROUP_KEYWORD = '__nogroup__';

    /**
     * Contructor adds test groups defined on global level
     * and adds additional logic for test names retrieval
     *
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestSuite::__construct()
     * @param array $groups
     */
    public function __construct($theClass = '', $groups = array())
    {
        if (!$theClass instanceof ReflectionClass) {
            $theClass = EcomDev_Utils_Reflection::getRelflection($theClass);
        }

        // Check annotations for test case name
        $annotations = PHPUnit_Util_Test::parseTestMethodAnnotations(
            $theClass->getName()
        );

        if (isset($annotation['name'])) {
            $name = $annotations['name'];
        } else {
            $name = sprintf('Test suite for %s', $theClass->getName());
        }

        // Creates all test instances
        parent::__construct($theClass, $name);

        // Just sort-out them by our internal groups
        foreach ($groups as $group) {
            $this->groups[$group] = $this->tests();
        }

        foreach ($this->tests() as $test) {
            if ($test instanceof PHPUnit_Framework_TestSuite) {
                /* @todo
                 * Post an issue into PHPUnit bugtracker for
                 * impossiblity for specifying group by parent test case
                 * Becuase it is a very dirty hack :(
                 **/
                $testGroups = array();
                foreach ($groups as $group) {
                    $testGroups[$group] = $test->tests();
                }

                EcomDev_Utils_Reflection::setRestrictedPropertyValue(
                    $test, 'groups', $testGroups
                );
            }
        }

        // Remove ungrouped tests group, if it exists
        if (isset($this->groups[self::NO_GROUP_KEYWORD])) {
            unset($this->groups[self::NO_GROUP_KEYWORD]);
        }
    }
}
