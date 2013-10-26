<?php

use EcomDev_Utils_Reflection as ReflectionUtil;

class EcomDev_PHPUnitTest_Test_Model_Fixture_Loader_Container
    extends PHPUnit_Framework_TestCase
{
    /**
     * @var EcomDev_PHPUnit_Model_Fixture_Loader_Container
     */
    protected $_factory;
    
    protected function setUp()
    {
        $this->_factory = new EcomDev_PHPUnit_Model_Fixture_Loader_Container();
    }
    
    public function testItHasLoadersProperty()
    {
        $this->assertObjectHasAttribute('_loaders', $this->_factory); 
    }

    public function testItAddsMultipleLoaders()
    {
        $loaders = $this->_generateLoaders(3);
        
        foreach ($loaders as $code => $loader) {
            $this->_factory->add($code, $loader);
        }
        
        $this->assertAttributeEquals($loaders, '_loaders', $this->_factory);
    }
    
    public function testItRemovesLoaderByCode()
    {
        $loaders = $this->_stubLoaders(3);
        
        $this->_factory->remove('my_loader_2');
        
        $this->assertAttributeEquals(
            array(
                'my_loader_1' => $loaders['my_loader_1'],
                'my_loader_3' => $loaders['my_loader_3']
            ),
            '_loaders',
            $this->_factory
        );
    }
    
    public function testItChecksExistanceOfLoaderByCode()
    {
        $this->_stubLoaders(3);
        
        $this->assertTrue($this->_factory->has('my_loader_1'));
        $this->assertTrue($this->_factory->has('my_loader_2'));
        $this->assertTrue($this->_factory->has('my_loader_3'));
        
        $this->_factory->remove('my_loader_2');

        $this->assertTrue($this->_factory->has('my_loader_1'));
        $this->assertFalse($this->_factory->has('my_loader_2'));
        $this->assertTrue($this->_factory->has('my_loader_3'));
    }
    
    
    public function testItReturnsLoaderByCode()
    {
        $loaders = $this->_stubLoaders(3);
        $this->assertSame(
            $loaders['my_loader_3'],
            $this->_factory->get('my_loader_3')
        );
    }
    
    public function testItReturnsFalseIfLoaderDoesNotExist()
    {
        $this->assertFalse(
            $this->_factory->get('my_loader_1')
        );
    }

    /**
     * Generates loaders for tests
     *
     * @param int $count
     * @return EcomDev_PHPUnit_Model_Fixture_Loader_Interface[]
     */
    protected function _generateLoaders($count)
    {
        $loaders = array();
        for ($i = 1; $i <= $count; $i ++) {
            $loaders['my_loader_' . $i] = $this->getMockForAbstractClass(
                'EcomDev_PHPUnit_Model_Fixture_LoaderInterface'
            );
        }
        
        return $loaders;
    }

    /**
     * Generates and stubs loaders for tests
     *
     * @param int $count
     * @return EcomDev_PHPUnit_Model_Fixture_LoaderInterface[]
     */
    protected function _stubLoaders($count)
    {
        $loaders = $this->_generateLoaders($count);
        ReflectionUtil::setRestrictedPropertyValue(
            $this->_factory, '_loaders', $loaders
        );
        return $loaders;
    }

}