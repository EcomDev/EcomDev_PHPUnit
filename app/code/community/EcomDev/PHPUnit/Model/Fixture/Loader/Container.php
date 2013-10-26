<?php

class EcomDev_PHPUnit_Model_Fixture_Loader_Container
{
    /**
     * List of loaders by code
     * 
     * @var EcomDev_PHPUnit_Model_Fixture_LoaderInterface[]
     */
    protected $_loaders = array();

    /**
     * Returns loader by code
     * 
     * If loader does not exists, it returns false
     * 
     * @param $code
     * @return bool|EcomDev_PHPUnit_Model_Fixture_LoaderInterface
     */
    public function get($code)
    {
        if (!$this->has($code)) {
            return false;
        }
        
        return $this->_loaders[$code];
    }

    /**
     * Adds a new loader to the container
     * 
     * @param string $code
     * @param EcomDev_PHPUnit_Model_Fixture_LoaderInterface $loader
     * @return $this
     */
    public function add($code, EcomDev_PHPUnit_Model_Fixture_LoaderInterface $loader)
    {
        $this->_loaders[$code] = $loader;
        return $this;
    }

    /**
     * Removes a loader by code from container
     * 
     * @param string $code
     * @return $this
     */
    public function remove($code)
    {
        unset($this->_loaders[$code]);
        return $this;
    }

    /**
     * Checks existance of the loader by container
     * 
     * @param string $code
     * @return bool
     */
    public function has($code)
    {
        return isset($this->_loaders[$code]);
    }
}