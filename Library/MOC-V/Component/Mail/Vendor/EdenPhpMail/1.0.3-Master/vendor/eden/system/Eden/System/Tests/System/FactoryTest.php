<?php
namespace Eden\Utility;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-01-20 at 08:38:01.
 */
class Eden_System_Tests_System_FactoryTest extends \PHPUnit_Framework_TestCase
{

    public function testFile()
    {

        $class = eden('system')->file('/some/file');
        $this->assertInstanceOf('Eden\\System\\File', $class);
    }

    public function testFolder()
    {

        $class = eden('system')->folder('/some/folder');
        $this->assertInstanceOf('Eden\\System\\Folder', $class);
    }

    public function testPath()
    {

        $class = eden('system')->path('/some/path');
        $this->assertInstanceOf('Eden\\System\\Path', $class);
    }
}
