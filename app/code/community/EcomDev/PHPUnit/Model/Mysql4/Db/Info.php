<?php

class EcomDev_PHPUnit_Model_Mysql4_Db_Info implements EcomDev_PHPUnit_Model_Mysql4_Db_InfoInterface
{
    /** @var Varien_Db_Adapter_Interface|null Current used adapter. */
    protected $_adapter = null;

    /** @var  array Information about the magento database [table => [...]]. */
    protected $_information;


    /**
     * Before fetching information about a table.
     *
     * @return $this
     */
    public function fetch()
    {
        // reset information
        $this->reset();

        // iterate over each available table
        $listTables = $this->getAdapter()->listTables();
        foreach ($listTables as $tableName)
        {
            // describe the table
            $data = new Varien_Object();
            $data->setData($this->getAdapter()->describeTable($tableName));

            $foreignKeys = $this->getAdapter()->getForeignKeys($tableName);

            $dependency = array();
            if (is_array($foreignKeys))
            {
                // add each depending table
                foreach ($foreignKeys as $keyData)
                {
                    $dependency[] = $keyData['REF_TABLE_NAME'];
                }
            }
            $data->setDependencies($dependency);

            $this->_information[$tableName] = $data;
        }
    }


    /**
     * Get the current used adapter.
     *
     * @return Varien_Db_Adapter_Interface|Zend_Db_Adapter_Abstract|null
     */
    public function getAdapter()
    {
        return $this->_adapter;
    }


    /**
     * Get dependencies for a single table.
     *
     * @param $tableName
     *
     * @return array|null Will return the tables that depend on the given one.
     */
    public function getTableDependencies($tableName)
    {
        if (isset($this->_information[$tableName])
            && $this->_information[$tableName] instanceof Varien_Object
        )
        {
            return $this->_information[$tableName]->getDependencies();
        }

        return null;
    }


    /**
     * Reset dependencies information.
     *
     * @return $this
     */
    public function reset()
    {
        $this->_information = array();
    }


    /**
     * Provide an adapter.
     *
     * @param Varien_Db_Adapter_Interface $adapter
     *
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function setAdapter($adapter)
    {
        if (!($adapter instanceof Varien_Db_Adapter_Interface))
        {
            throw new InvalidArgumentException('Unsupported adapter ' . get_class($adapter));
        }

        $this->_adapter = $adapter;
    }
}
