<?php


/**
 * Interface for helpers that support setUp() and tearDown() methods
 *
 * These methods are invoked when test setUp() or tearDown() is executed
 *
 */
interface EcomDev_PHPUnit_Helper_Listener_Interface extends EcomDev_PHPunit_Helper_Interface
{
    public function setUp();

    public function tearDown();
}
