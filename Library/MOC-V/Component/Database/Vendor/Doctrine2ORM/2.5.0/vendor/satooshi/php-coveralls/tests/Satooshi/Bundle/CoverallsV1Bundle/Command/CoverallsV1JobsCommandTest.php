<?php
namespace Satooshi\Bundle\CoverallsV1Bundle\Command;

use Satooshi\ProjectTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers Satooshi\Bundle\CoverallsV1Bundle\Command\CoverallsV1JobsCommand
 *
 * @author Kitamura Satoshi <with.no.parachute@gmail.com>
 */
class CoverallsV1JobsCommandTest extends ProjectTestCase
{

    /**
     * @test
     */
    public function shouldExecuteCoverallsV1JobsCommand()
    {

        $this->makeProjectDir(null, $this->logsDir);
        $this->dumpCloverXml();

        $command = new CoverallsV1JobsCommand();
        $command->setRootDir($this->rootDir);

        $app = new Application();
        $app->add($command);

        $command = $app->find('coveralls:v1:jobs');
        $commandTester = new CommandTester($command);

        $_SERVER['TRAVIS'] = true;
        $_SERVER['TRAVIS_JOB_ID'] = 'command_test';

        $actual = $commandTester->execute(
            array(
                'command'   => $command->getName(),
                '--dry-run' => true,
                '--config'  => 'coveralls.yml',
                '--env'     => 'test',
            )
        );

        $this->assertEquals(0, $actual);
    }

    protected function dumpCloverXml()
    {

        file_put_contents($this->cloverXmlPath, $this->getCloverXml());
    }

    protected function getCloverXml()
    {

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="1365848893">
  <project timestamp="1365848893">
    <file name="%s/test.php">
      <class name="TestFile" namespace="global">
        <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
      </class>
      <line num="5" type="method" name="__construct" crap="1" count="0"/>
      <line num="7" type="stmt" count="0"/>
    </file>
    <package name="Hoge">
      <file name="%s/test2.php">
        <class name="TestFile" namespace="Hoge">
          <metrics methods="1" coveredmethods="0" conditionals="0" coveredconditionals="0" statements="1" coveredstatements="0" elements="2" coveredelements="0"/>
        </class>
        <line num="6" type="method" name="__construct" crap="1" count="0"/>
        <line num="8" type="stmt" count="0"/>
      </file>
    </package>
  </project>
</coverage>
XML;
        return sprintf($xml, $this->srcDir, $this->srcDir);
    }

    protected function setUp()
    {

        $this->projectDir = realpath(__DIR__.'/../../../..');

        $this->setUpDir($this->projectDir);
    }

    protected function tearDown()
    {

        $this->rmFile($this->cloverXmlPath);
        $this->rmFile($this->jsonPath);
        $this->rmDir($this->logsDir);
        $this->rmDir($this->buildDir);
    }
}
