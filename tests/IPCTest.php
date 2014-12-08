<?php

namespace djfm\ftr\tests;

use djfm\ftr\IPC\Server;
use djfm\ftr\IPC\Client;
use djfm\ftr\Exception\CouldNotConnectToServerException;
use djfm\ftr\Helper\Process as ProcessHelper;


use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Process;

class IPCTest extends \PHPUnit_Framework_TestCase
{
	public function spawnServer($class = 'djfm\ftr\IPC\Server')
	{
		$builder = new ProcessBuilder([PHP_BINARY, __DIR__ . DIRECTORY_SEPARATOR . 'spawnServer.php', $class]);
		$process = $builder->getProcess();
		$process->start();
		$address = null;

		while ($process->isRunning() && !$address) {
			$address = $process->getOutput();
			sleep(1);
		}

		$stop = function () use ($process) {
			if ($process->isRunning()) {
				ProcessHelper::killChildren($process);
				$process->stop();
			}
		};

		register_shutdown_function($stop);

		return [
			'address' => $address,
			'stop' => $stop
		];
	}

	public function testICanConnectToServer()
	{
		$server = $this->spawnServer();

		$client = new Client($server['address']);

		$this->assertEquals('ftrftrftr', $client->get());

		$server['stop']();
	}
}