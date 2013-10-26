<?php

class EcomDev_PHPUnit_Model_Fixture_Loader_Database implements
    EcomDev_PHPUnit_Model_Fixture_LoaderInterface
{
    /**
     * Flushes the data that was added before loading
     *
     * @return mixed
     */
    public function flush()
    {
        // TODO: Implement flush() method.
    }


    /**
     * This one should realize possibility to merge data with existing Magento data
     *
     * @param $data
     *
     * @return mixed
     */
    public function merge($data)
    {
        // TODO: Implement merge() method.
    }


    /**
     * Overrides the data in the system for specified data
     *
     * It should realize default functionality
     * for a default fixture processors
     *
     * @param array $data
     *
     * @return $this
     * @throws InvalidArgumentException
     */
    public function override($data)
    {
        // TODO: Implement override() method.
    }


    /**
     * Restores original data, that was modified by fixture flushing
     *
     * @return mixed
     */
    public function restore()
    {
        // TODO: Implement restore() method.
    }
}
