<?php

namespace djfm\ftr\tests;

use Exception;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use djfm\ftr\app\Command\RunCommand;

class FTRTest extends \PHPUnit_Framework_TestCase
{
    public function executeRun(array $options)
    {
        ob_start(); // something is doing output, cannot figure out what exacly - just kill it,
                    // because it messes up the display of test results

        $application = new Application();
        $application->add(new RunCommand());

        $command = $application->find('run');
        $commandTester = new CommandTester($command);

        $defaults = [
            'command' => $command->getName()
        ];

        $commandTester->execute(array_merge($defaults, $options));

        $res = $command->getLastRunData();

        ob_end_clean(); // the rest of the program is well behaved regarding output,
                        // turn it on again.

        if (!is_array($res)) {
            throw new Exception("Seems like the command failed to run.\n" . $commandTester->getDisplay());
        }

        return $res;
    }

    public function testAFailureAndAnOK()
    {
        $res = $this->executeRun(['test' => __DIR__ . '/fixtures/ASimpleFailingTest.php']);

        $this->assertArrayHasKey('summary', $res);

        $this->assertEquals([
            'ok' => 1,
            'ko' => 1,
            'skipped' => 0,
            'unknown' => 0
        ], $res['summary']['status']);
    }

    public function testADataProvider()
    {
        $res = $this->executeRun(['test' => __DIR__ . '/fixtures/ADataProviderTest.php', '--processes' => 4]);

        $this->assertArrayHasKey('summary', $res);

        $this->assertEquals([
            'ok' => 3,
            'ko' => 0,
            'skipped' => 0,
            'unknown' => 0
        ], $res['summary']['status']);
    }

    public function testADepends()
    {
        $res = $this->executeRun(['test' => __DIR__ . '/fixtures/ADependsTest.php']);

        $this->assertArrayHasKey('summary', $res);

        $this->assertEquals([
            'ok' => 2,
            'ko' => 1,
            'skipped' => 1,
            'unknown' => 0
        ], $res['summary']['status']);
    }

    public function testAMiscTest()
    {
        $res = $this->executeRun(['test' => __DIR__ . '/fixtures/AMiscTest.php']);

        $this->assertArrayHasKey('summary', $res);

        $this->assertEquals([
            'ok' => 1,
            'ko' => 0,
            'skipped' => 0,
            'unknown' => 0
        ], $res['summary']['status']);
    }

    public function testExtraTestFunctionsAreCalled()
    {
        $res = $this->executeRun(['test' => __DIR__ . '/fixtures/ExtraTestFunctionsAreCalledTest.php']);

        $this->assertArrayHasKey('summary', $res);

        $this->assertEquals([
            'ok' => 2,
            'ko' => 0,
            'skipped' => 0,
            'unknown' => 0
        ], $res['summary']['status']);
    }

    public function testFailingSetupBeforeClassSkipsAll()
    {
        $res = $this->executeRun(['test' => __DIR__ . '/fixtures/FailingSetupBeforeClassTest.php']);

        $this->assertArrayHasKey('summary', $res);

        $this->assertEquals([
            'ok' => 0,
            'ko' => 0,
            'skipped' => 2,
            'unknown' => 0
        ], $res['summary']['status']);
    }

    public function testDyingTest()
    {
        $res = $this->executeRun(['test' => __DIR__ . '/fixtures/DyingTest.php']);

        $this->assertArrayHasKey('summary', $res);

        $this->assertEquals([
            'ok' => 0,
            'ko' => 0,
            'skipped' => 0,
            'unknown' => 2
        ], $res['summary']['status']);
    }
}
