<?php

namespace djfm\ftr\tests;

use djfm\ftr\IPC\Server;
use djfm\ftr\IPC\Client;
use djfm\ftr\Exception\CouldNotConnectToServerException;

use djfm\ftr\Process\ProcessBuilder;
use djfm\ftr\Process\Process;

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
		return [
			'address' => $address,
			'stop' => function () use ($process) {
				echo "stopping process";
				$process->stop();
			}
		];
	}

	public function tesICanHazSocketz()
	{
		$server = new Server();
		$address = $server->bind();
		$this->assertInternalType('string', $address);
		$server->stop();
	}

	public function testICanConnectToServer()
	{
		$server = $this->spawnServer();

		//$server['stop']();
	}
}