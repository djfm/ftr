<?php

namespace djfm\ftr\tests;

use Exception;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Tester\CommandTester;

use djfm\ftr\app\Command\RunCommand;

class FTRTest extends \PHPUnit_Framework_TestCase
{
	public function executeRun(array $options)
	{
		$application = new Application();
		$application->add(new RunCommand());

		$command = $application->find('run');
		$commandTester = new CommandTester($command);
		
		$defaults = [
			'command' => $command->getName()
		];

		$commandTester->execute(array_merge($defaults, $options));

		$res = $command->getLastRunData();

		if (!is_array($res)) {
			throw new Exception("Seems like the command failed to run.\n".$commandTester->getDisplay());
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
		], $res['summary']);
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
		], $res['summary']);
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
		], $res['summary']);
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
		], $res['summary']);
	}
}