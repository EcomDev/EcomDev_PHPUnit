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
 * Helper for stubbing event observer in Magento
 *
 *
 */
class EcomDev_PHPUnit_Test_Case_Helper_Observer extends EcomDev_PHPUnit_AbstractHelper
{
    /**
     * Generates observer object
     *
     * @param array $eventData
     * @param string $eventName
     *
     * @return Varien_Event_Observer
     */
    public function helperGenerateObserver(
        $eventData, $eventName = null)
    {
        $event = new Varien_Event($eventData);
        $observer = new Varien_Event_Observer();
        $observer->setEvent($event);

        if ($eventName) {
            $event->setName($eventName);
            $observer->setEventName($eventName);
        }

        $observer->addData($eventData);
        return $observer;
    }
}
