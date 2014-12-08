<?php

namespace djfm\ftr\IPC;

use djfm\ftr\Exception\CouldNotConnectToServerException;

class Client
{
	private $address;

	public function connect($address)
	{
		$errno = null;
		$errorMessage = null;

		$client = stream_socket_client($address, $errno, $errorMessage);
		if (!$client) {
			throw new CouldNotConnectToServerException($errorMessage, $errno);
		} else {
			$this->address = $address;
			$this->client = $client;
		}

		return $this;
	}

	public function disconnect()
	{
		if ($this->client) {
			fclose($this->client);
		}

		$this->client = null;
		$this->address = null;

		return $this;
	}
}