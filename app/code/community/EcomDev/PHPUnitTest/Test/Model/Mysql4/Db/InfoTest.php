<?php

class EcomDev_PHPUnitTest_Test_Model_Mysql4_Db_InfoTest extends EcomDev_PHPUnit_Test_Case
{
    /** @var EcomDev_PHPUnit_Model_Mysql4_Db_InfoInterface */
    protected $_factory;


    /**
     * Set up unit testing.
     *
     * @return void
     */
    public function setUp()
    {
        $this->_factory = new EcomDev_PHPUnit_Model_Mysql4_Db_Info();

        parent::setUp();
    }


    /**
     * Tear down unit testing.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->_factory = null;

        parent::tearDown();
    }


    /**
     * Check if the model return the correct dependencies.
     *
     * @return void
     */
    public function testGetTheDependenciesForASpecificTable()
    {
        $this->_factory->setAdapter($this->_getMockedAdapter());
        $this->_factory->fetch();

        $this->assertEquals(array('mother'), $this->_factory->getTableDependencies('child'));
        $this->assertEquals(null, $this->_factory->getTableDependencies('some_unknown'));
    }


    /**
     * check if the model resets the information correct.
     *
     * @return void
     */
    public function testItCanResetTheFetchedInformation()
    {
        // write something to the field via reflection
        $reflect  = $this->_getReflection();
        $property = $reflect->getProperty('_information');
        $property->setAccessible(true);
        $property->setValue($this->_factory, array(uniqid()));

        $this->_factory->reset();

        $this->assertEmpty($property->getValue($this->_factory));
    }


    /**
     * Check if the fetched information about a table is correct.
     *
     * @return void
     */
    public function testItFetchesInformationAboutATable()
    {
        $adapterMock = $this->_getMockedAdapter();

        // check the fetched data
        $this->_factory->setAdapter($adapterMock);
        $this->_factory->fetch();

        $reflectObject = $this->_getReflection();
        $property      = $reflectObject->getProperty('_information');
        $property->setAccessible(true);
        $information = $property->getValue($this->_factory);

        $this->assertEquals(array_keys($information), $adapterMock->listTables());

        /** @var Varien_Object $child */
        $child = $information['child'];
        $this->assertNotNull($child->getDependencies());
        $this->assertEquals(array('mother'), $child->getDependencies());
    }


    /**
     * Check whether an adapter can be set and get.
     *
     * @return null
     */
    public function testYouNeedToProvideAnAdapter()
    {
        /** @var Varien_Db_Adapter_Interface $adapterMock */
        $adapterMock = $this->getMock('Varien_Db_Adapter_Pdo_Mysql', array(), array(), '', false);
        $this->assertTrue($adapterMock instanceof Varien_Db_Adapter_Pdo_Mysql);

        $this->_factory->setAdapter($adapterMock);

        $this->assertSame($adapterMock, $this->_factory->getAdapter());

        return null;
    }


    /**
     * Mock the adapter without any configuration.
     *
     * @return Varien_Db_Adapter_Pdo_Mysql|PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getMockedAdapter()
    {
        /** @var Varien_Db_Adapter_Pdo_Mysql|PHPUnit_Framework_MockObject_MockObject $adapterMock Mock without connecting to a server. */
        $adapterMock = $this->getMock(
                            'Varien_Db_Adapter_Pdo_Mysql',
                            array(
                                 'listTables',
                                 'describeTable',
                                 'getForeignKeys',
                            ),
                            array(),
                            '',
                            false // ignore original constructor
        );

        $this->assertTrue($adapterMock instanceof Varien_Db_Adapter_Pdo_Mysql);

        // mock listTables: with two tables that depend on each other
        $listTablesReturn = array('child', 'mother');
        $adapterMock->expects($this->any())
                    ->method('listTables')
                    ->will(
                    $this->returnValue($listTablesReturn)
            );

        $this->assertEquals($adapterMock->listTables(), $listTablesReturn);

        // mock describeTable
        $describeTableReturn = array(
            array(
                'SCHEMA_NAME'      => 'test',
                'TABLE_NAME'       => 'foo',
                'COLUMN_NAME'      => 'bar',
                'COLUMN_POSITION'  => '0',
                'DATA_TYPE'        => 'int',
                'DEFAULT'          => '',
                'NULLABLE'         => '',
                'LENGTH'           => '',
                'SCALE'            => '',
                'UNSIGNED'         => true,
                'PRIMARY'          => false,
                'PRIMARY_POSITION' => null,
                'IDENTITY'         => false,
            ),
        );

        $adapterMock->expects($this->any())
                    ->method('describeTable')
                    ->will(
                    $this->returnValue($describeTableReturn)
            );

        $this->assertEquals($adapterMock->describeTable('child'), $describeTableReturn);

        // mock adapter::getForeignKeys
        $getForeignKeysReturn = array(
            'child'  => array(
                'fk_mother' => array(
                    'FK_NAME'         => 'idMother',
                    'SCHEMA_NAME'     => 'test',
                    'TABLE_NAME'      => 'child',
                    'COLUMN_NAME'     => 'kf_mother',
                    'REF_SHEMA_NAME'  => 'test',
                    'REF_TABLE_NAME'  => 'mother',
                    'REF_COLUMN_NAME' => 'idMother',
                    'ON_DELETE'       => '',
                    'ON_UPDATE'       => ''
                ),
            ),
            'mother' => array(),
        );

        $adapterMock->expects($this->any())
                    ->method('getForeignKeys')
                    ->will(
                    $this->returnCallback(
                         function ($tableName) use ($getForeignKeysReturn)
                         {
                             return $getForeignKeysReturn[$tableName];
                         }
                    )
            );

        $this->assertEquals($adapterMock->getForeignKeys('child'), $getForeignKeysReturn['child']);
        $this->assertEquals(
             $adapterMock->getForeignKeys('mother'),
             $getForeignKeysReturn['mother']
        );

        return $adapterMock;
    }


    /**
     * Reflect the object.
     *
     * @return ReflectionObject
     */
    protected function _getReflection()
    {
        $reflect = new ReflectionObject($this->_factory);

        return $reflect;
    }
} 
