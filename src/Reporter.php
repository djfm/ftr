<?php

namespace djfm\ftr;

use djfm\ftr\Test\TestInterface;
use djfm\ftr\IPC\Client;

class Reporter
{
	private $client;

	public function __construct(Client $client)
	{
		$this->client = $client;
	}

	public function start(TestInterface $test)
	{
		$this->client->post('/messages', [
			'type' => 'testStart',
			'testIdentifier' => $test->getTestIdentifier()
		]);

		return $this;
	}

	public function end(TestInterface $test, $status)
	{
		$this->client->post('/messages', [
			'type' => 'testEnd',
			'testIdentifier' => $test->getTestIdentifier(),
			'status' => $status
		]);

		return $this;
	}
}