<?php

/**
 * A test case for testing script assertions
 *
 * @loadSharedFixture files
 */
class EcomDev_PHPUnitTest_Test_Lib_Constraint_Config_Resource_Script extends EcomDev_PHPUnit_Test_Case
{
    /**
     * @var EcomDev_PHPUnit_Constraint_Config_Resource_Script
     */
    protected $constraint = null;

    protected function setUp()
    {
        $this->constraint = $this->getMockBuilder('EcomDev_PHPUnit_Constraint_Config_Resource_Script')
                ->disableOriginalConstructor()
                ->setMethods(null)
                ->getMock();
    }

    /**
     * Returns path within vfs stream
     *
     * @param string[]|string $directories
     * @return string[]|string
     */
    protected function getVirtualPath($directories)
    {
        if (!is_array($directories)) {
            $directories = array($directories);
        }

        $virtualPath = array();
        foreach ($directories as $directory) {
            $virtualPath[] = $this->getFixture()->getVfs()->url($directory);
        }

        if (count($virtualPath) === 1) {
            $virtualPath = current($virtualPath);
        }
        return $virtualPath;
    }

    /**
     *
     * @param $directories
     * @dataProvider dataProvider
     */
    public function testParseVersions($directories)
    {
        $virtualPath = $this->getVirtualPath($directories);

        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod($this->constraint, 'parseVersions', array($virtualPath));

        $this->assertEquals($this->expected('auto')->getVersions(), $result);
    }

    /**
     * Test version
     *
     * @param string[]|string $directories
     * @param string $type
     * @param string $from
     * @param string $to
     *
     * @return void
     * @dataProvider dataProvider
     */
    public function testGetVersionScriptsDiff($directories, $type, $from, $to)
    {
        $virtualPath = $this->getVirtualPath($directories);
        $versions = EcomDev_Utils_Reflection::invokeRestrictedMethod($this->constraint, 'parseVersions', array(
            $virtualPath
        ));

        $result = EcomDev_Utils_Reflection::invokeRestrictedMethod($this->constraint, 'getVersionScriptsDiff', array(
            $versions[$type], $from, $to, $type === 'data' ? 'data-' : ''
        ));

        $this->assertEquals($this->expected('auto')->getDiff(), $result);
    }
}