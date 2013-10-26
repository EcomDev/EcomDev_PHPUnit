<?php


/**
 * Interface for helpers that support setUp() and tearDown() methods
 *
 * These methods are invoked when test setUp() or tearDown() is executed
 *
 */
interface EcomDev_PHPUnit_Helper_ListenerInterface 
    extends EcomDev_PHPUnit_HelperInterface
{
    public function setUp();

    public function tearDown();
}
