<?php


/**
 * The interface that will provide interface of the loader
 * for a fixture processor
 * 
 * This one should work like a single loader for each kind of data 
 */
interface EcomDev_PHPUnit_Model_Fixture_LoaderInterface
{    
    /**
     * Overrides the data in the system for specified data
     * 
     * It should realize default functionality 
     * for a default fixture processors 
     * 
     * @param array $data
     * @return $this
     * @throws InvalidArgumentException
     */
    public function override($data);

    /**
     * This one should realize possibility to merge data with existing Magento data
     * 
     * @param $data
     * @return mixed
     */
    public function merge($data);

    /**
     * Flushes the data that was added before loading
     * 
     * @return mixed
     */
    public function flush();

    /**
     * Restores original data, that was modified by fixture flushing
     * 
     * @return mixed
     */
    public function restore();

}
