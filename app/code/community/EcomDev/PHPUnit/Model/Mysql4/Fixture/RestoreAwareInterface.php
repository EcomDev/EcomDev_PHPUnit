<?php

interface EcomDev_PHPUnit_Model_Mysql4_Fixture_RestoreAwareInterface
{
    /**
     * Saves data for restoring it after fixture has been cleaned up
     *
     * @param string $code storage code
     * @return $this
     */
    public function saveData($code);

    /**
     * Restored saved data
     *
     * @param string $code storage code
     * @return $this
     */
    public function restoreData($code);

    /**
     * Clears storage from stored backup data
     * 
     * @param $code
     * @return $this
     */
    public function clearData($code);
}