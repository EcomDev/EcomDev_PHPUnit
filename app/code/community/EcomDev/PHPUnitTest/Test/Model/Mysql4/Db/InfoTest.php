<?php

use EcomDev_Utils_Reflection as ReflectionUtil;

class EcomDev_PHPUnitTest_Test_Model_Mysql4_Db_InfoTest extends EcomDev_PHPUnit_Test_Case
{
    /** @var EcomDev_PHPUnit_Model_Mysql4_Db_Info */
    protected $_info;
    
    /** @var  Varien_Db_Adapter_Pdo_Mysql|PHPUnit_Framework_MockObject_MockObject */
    protected $_adapter; 


    /**
     * Set up unit testing.
     *
     * @return void
     */
    public function setUp()
    {
        $this->_info = new EcomDev_PHPUnit_Model_Mysql4_Db_Info();
        $this->_adapter = $this->getMockBuilder('Varien_Db_Adapter_Pdo_Mysql')
                               ->disableOriginalConstructor()
                               ->getMock();
        $this->_info->setAdapter($this->_adapter);

        parent::setUp();
    }

    /**
     * Check if the model return the correct dependencies.
     *
     * @dataProvider dataProvider
     * @dataProviderFile tableStructure
     */
    public function testGetTheDependenciesForASpecificTable($tables)
    {
        $this->_stubAdapter($tables);
        
        $this->assertEquals(array('mother'), $this->_info->getTableDependencies('child'));
        $this->assertEquals(null, $this->_info->getTableDependencies('some_unknown'));
    }

    /**
     * Stubs adapter method calls
     * 
     * @param array $tables
     * @return $this
     */
    protected function _stubAdapter($tables)
    {
        $this->_adapter->expects($this->any())
            ->method('listTables')
            ->will($this->returnValue(array_keys($tables)));
        
        $columnsMap = array();
        $foreignKeyMap = array();
        
        foreach ($tables as $tableName => $info) {
            $columnsMap[] = array($tableName, null, $info['columns']);
            $foreignKeyMap[] = array($tableName, null, $info['foreign_keys']);
        }
        
        $this->_adapter->expects($this->any())
            ->method('describeTable')
            ->will($this->returnValueMap($columnsMap));

        $this->_adapter->expects($this->any())
            ->method('getForeignKeys')
            ->will($this->returnValueMap($foreignKeyMap));
        
        return $this;
    }


    /**
     * check if the model resets the information correct.
     *
     * @return void
     */
    public function testItCanResetTheFetchedInformation()
    {
        ReflectionUtil::setRestrictedPropertyValue(
            $this->_info, 
            '_information', 
            array(uniqid())
        );

        $this->_info->reset();

        $this->assertAttributeSame(
            null,
            '_information',
            $this->_info
        );
    }


    /**
     * Check if the fetched information about a table is correct.
     *
     * @param $tables
     * @return void
     * @dataProvider dataProvider
     * @dataProviderFile tableStructure
     * @loadExpectation fetchData
     */
    public function testItFetchesInformationAboutATable($tables)
    {
        $this->_stubAdapter($tables);
        $this->_info->fetch();

        $information = $this->readAttribute($this->_info, '_information'); 
        $tables = $this->expected()->getTables();
        
        foreach ($tables as $tableName => $data) {
            $this->assertArrayHasKey($tableName, $information);
            $this->assertEquals($data, $information[$tableName]->getData());
        }
    }


    /**
     * Check whether an adapter can be set and get.
     *
     * @return null
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Adapter should be an instance of Zend_Db_Adapter_Abstract
     */
    public function testYouNeedToProvideAnAdapter()
    {
        $this->_info->setAdapter(null);
    }

    /**
     * Tests that setters and getters for adapter are working correctly
     * 
     */
    public function testItSetsAnAdapter()
    {
        ReflectionUtil::setRestrictedPropertyValue(
            $this->_info,
            '_adapter',
            null
        );
        
        $this->_info->setAdapter($this->_adapter);
        $this->assertSame($this->_adapter, $this->_info->getAdapter());
    }


} 
