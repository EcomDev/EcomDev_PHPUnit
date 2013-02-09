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

use EcomDev_PHPUnit_Test_Case_Util as TestUtil;

class EcomDev_PHPUnit_Test_Case_Helper_Session
    extends EcomDev_PHPUnit_Helper_Abstract
    implements EcomDev_PHPUnit_Helper_Listener_Interface
{
    /**
     * Loaded ACL model for admin session mocks
     *
     * @var Mage_Admin_Model_Acl
     */
    protected $acl;

    /**
     * Creates a mockery for session
     *
     * @param string $classAlias
     * @param array  $methods
     *
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function helperMockSession($classAlias, array $methods = array())
    {
        $sessionMock = EcomDev_PHPUnit_Helper::invoke('mockModel', $classAlias, $methods)
            ->disableOriginalConstructor();

        TestUtil::replaceByMock('singleton', $classAlias, $sessionMock);
        return $sessionMock;
    }

    /**
     * Helper for mocking admin panel session
     *
     * @param array $resources
     * @return EcomDev_PHPUnit_Mock_Proxy
     */
    public function helperAdminSession(array $resources = array())
    {
        $session = $this->helperMockSession('admin/session', array('refreshAcl'));
        $user = $this->createUser();
        $this->loadRules($user, $this->getAcl(), $resources);
        $session->setUser($user);
        return $session;
    }

    public function getAcl()
    {
        if ($this->acl === null) {
            $this->acl = Mage::getModel('admin/acl');
            Mage::getSingleton('admin/config')->loadAclResources($this->acl);
        }
        return $this->acl;
    }

    public function loadRules($user, $acl, array $allowedResources = array())
    {
        $userRole = Mage::getModel('admin/acl_role_user', Mage_Admin_Model_Acl::ROLE_TYPE_USER . $user->getId());
        $acl->addRole($userRole);

        if (empty($allowedResources)) {
            $acl->allow($userRole);
            return $this;
        }

        $allow = array();
        foreach ($allowedResources as $resource) {
            $childResources = array_filter(
                $acl->getResources(),
                function ($entry) use ($resource) {
                    return strpos($entry, 'admin/' . $resource) === 0;
                }
            );

            $allow = array_merge($allow, array($resource), $childResources);
        }

        $deny = array();
        foreach ($acl->getResources() as $resource) {
            if (!in_array($resource, $allow)) {
                $deny[] = $resource;
            }
        }

        $deny;
    }

    public function createUser($entropy = 3)
    {
        $userId = floor(microtime(true)*pow(10, $entropy) - floor(time()/100)*100*pow(10, $entropy));
        return Mage::getModel('admin/user')->setId($userId);
    }

    /**
     * Does nothing during test setup
     *
     *
     */
    public function setUp()
    {

    }

    /**
     * Clean ups acl roles information after test completed
     *
     */
    public function tearDown()
    {
        if ($this->acl !== null) {
            $this->acl->removeRoleAll();
        }
    }
}
