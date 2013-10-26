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
 * Request class for usage in the controller test cases
 *
 * By default set for test app instance,
 * you can change to your class,
 * but you should extend it from this one
 *
 */
class EcomDev_PHPUnit_Controller_Request_Http
    extends Mage_Core_Controller_Request_Http
    implements EcomDev_PHPUnit_IsolationInterface,
               EcomDev_PHPUnit_Controller_RequestInterface
{
    /**
     * List of $_SERVER variable changes
     * that were done by test case
     *
     * @var array
     */
    protected $_originalServerValues = array();

    /**
     * List of headers that were set for test case
     *
     * @return array
     */
    protected $_headers = array();

    /**
     * Initializes forward data
     *
     * @see Mage_Core_Controller_Request_Http::initForward()
     */
    public function initForward()
    {
        if (empty($this->_beforeForwardInfo)) {
            parent::initForward();
            $this->_beforeForwardInfo['route_name'] = $this->getRouteName();
            return $this;
        }

        return parent::initForward();
    }

    /**
     * Returns only request uri that was set before
     * 
     * @see Zend_Controller_Request_Http::getRequestUri()
     */
    public function getRequestUri()
    {
        return $this->_requestUri;
    }

    /**
     * Sets cookie value for test,
     *
     * @param string|array $name
     * @param string|null $value
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function setCookie($name, $value)
    {
        $_COOKIE[$name] = $value;
        return $this;
    }

    /**
     * Sets more than one cookie
     *
     * @param array $cookies
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function setCookies(array $cookies)
    {
        $_COOKIE += $cookies;
        return $this;
    }

    /**
     * Resets all cookies for the test request
     *
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function resetCookies()
    {
        $_COOKIE = array();
        return $this;
    }

    /**
     * Resets query for the current request
     *
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function resetQuery()
    {
        $_GET = array();
        return $this;
    }

    /**
     * Resets $_POST super global for test request
     *
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function resetPost()
    {
        $_POST = array();
        return $this;
    }

    /**
     * Resets user defined request params for test request
     *
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function resetParams()
    {
        $this->_params = array();
        return $this;
    }

    /**
     * Resets internal properties to its default values
     *
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function resetInternalProperties()
    {
        // From abstract request
        $this->_dispatched = false;
        $this->_module = null;
        $this->_moduleKey = 'module';
        $this->_controller = null;
        $this->_controllerKey = 'controller';
        $this->_action = null;
        $this->_actionKey = 'action';

        // From Http request
        $this->_paramSources = array('_GET', '_POST');
        $this->_requestUri = null;
        $this->_baseUrl = null;
        $this->_basePath = null;
        $this->_pathInfo = '';
        $this->_rawBody = null;
        $this->_aliases = array();

        // From Magento Http request
        $this->_originalPathInfo = '';
        $this->_storeCode = null;
        $this->_requestString = '';
        $this->_rewritedPathInfo = null;
        $this->_requestedRouteName = null;
        $this->_routingInfo = array();
        $this->_route = null;
        $this->_directFrontNames = null;
        $this->_controllerModule = null;
        return $this;
    }

    /**
     * Sets rawBody property to request
     *
     * @param string $rawBody
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function setRawBody($rawBody)
    {
        $this->_rawBody = $rawBody;
        return $this;
    }

    /**
     * Set custom http header
     *
     * @param string $name
     * @param string $value
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function setHeader($name, $value)
    {
        $name = $this->headerName($name);
        $this->_headers[$name] = $value;
        // Additionally set $_SERVER http header value
        $this->setServer('HTTP_' . $name, $value);
        return $this;
    }

    /**
     * Sets more than one header,
     * headers list is an associative array
     *
     * @param array $headers
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    /**
     * Returns header from test request parameters
     *
     * @see Zend_Controller_Request_Http::getHeader()
     */
    public function getHeader($header)
    {
        $name = $this->headerName($header);
        if (isset($this->_headers[$name])) {
            return $this->_headers[$name];
        }

        return false;
    }

    /**
     * Resets headers in test request
     *
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function resetHeaders()
    {
        $this->_headers = array();
        return $this;
    }

    /**
     * Returns unified header name for internal storage
     *
     * @param string $name
     * @return string
     */
    protected function headerName($name)
    {
        return strtr(strtoupper($name), '-', '_');
    }

    /**
     * Sets value for a particular $_SERVER super global array key for test request
     *
     * Saves original value for returning it back
     *
     * @param string $name
     * @param string $value
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function setServer($name, $value)
    {
        if (!isset($this->_originalServerValues[$name])) {
            $originalValue = (isset($_SERVER[$name]) ? $_SERVER[$name] : null);
            $this->_originalServerValues[$name] = $originalValue;
        }

        $_SERVER[$name] = $value;
        return $this;
    }

    /**
     * Sets multiple values for $_SERVER super global in test request
     *
     * @param array $values
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function setServers(array $values)
    {
        foreach ($values as $name => $value) {
            $this->setServer($name, $value);
        }
        return $this;
    }

    /**
     * Resets $_SERVER super global to previous state
     *
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function resetServer()
    {
        foreach ($this->_originalServerValues as $name => $value) {
            if ($value !== null) {
                $_SERVER[$name] = $value;
            } elseif (isset($_SERVER[$name])) {
                // If original value was not set,
                // then unset the changed value
                unset($_SERVER[$name]);
            }
        }

        $this->_originalServerValues = array();
        return $this;
    }

    /**
     * Sets request method for test request
     *
     * @param string $requestMethod
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function setMethod($requestMethod)
    {
        $this->setServer('REQUEST_METHOD', $requestMethod);
        return $this;
    }

    /**
     * Sets current request scheme for test request,
     * accepts boolean flag for HTTPS
     *
     * @param boolean $flag
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function setIsSecure($flag = true)
    {
        $this->setServer('HTTPS', $flag ? 'on' : null);
        return $this;
    }

    /**
     * Returns HTTP host from base url that were set in the controller
     *
     * @see Mage_Core_Controller_Request_Http::getHttpHost()
     */
    public function getHttpHost($trimPort = false)
    {
        $baseUrl = $this->getBaseUrl();

        if (!$baseUrl) {
            $baseUrl = Mage::app()->getConfig()->getNode(
                EcomDev_PHPUnit_Model_Config::XML_PATH_UNSECURE_BASE_URL
            );
        }

        $parts = parse_url($baseUrl);

        if (!isset($parts['host'])) {
            throw new RuntimeException('Cannot run controller test, because the host is not set for base url.');
        }

        $httpHost = $parts['host'];
        if (!$trimPort && isset($parts['port'])) {
            $httpHost .= ':' . $parts['port'];
        }

        return $httpHost;
    }

    /**
     * Returns only base url that was set before
     *
     * @see Mage_Core_Controller_Request_Http::getBaseUrl()
     */
    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }

    /**
     * Resets all request data for test
     *
     * @return EcomDev_PHPUnit_Controller_Request_Http
     */
    public function reset()
    {
        $this->resetInternalProperties()
            ->resetHeaders()
            ->resetParams()
            ->resetPost()
            ->resetQuery()
            ->resetCookies()
            ->resetServer();

        return $this;
    }
}
