<?php

interface EcomDev_PHPUnit_Model_Mysql4_Db_InfoInterface
{
    /**
     * Provide an adapter.
     *
     * @param Varien_Db_Adapter_Interface $adapter
     *
     * @return mixed
     */
    public function setAdapter($adapter);


    /**
     * Get the current used adapter.
     *
     * @return Varien_Db_Adapter_Interface|Zend_Db_Adapter_Abstract|null
     */
    public function getAdapter();


    /**
     * Get dependencies for a single table.
     *
     * @param $tableName
     *
     * @return array Will return the tables that depend on the given one.
     */
    public function getTableDependencies($tableName);


    /**
     * Before fetching information about a table.
     *
     * @return $this
     */
    public function fetch();


    /**
     * Reset dependencies information.
     *
     * @return $this
     */
    public function reset();
}
