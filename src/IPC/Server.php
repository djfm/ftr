<?php

namespace djfm\ftr\IPC;

use djfm\ftr\Exception\CouldNotBindToSocketException;

class Server
{
	private $server;
	private $address;

	public function bind($ip = '0.0.0.0', $port = 55555, $maxPort = 100000)
	{
		$server = null;
		$address = null;
		for ($p = $port; !$server && $p < $maxPort; $p++) {
			$address = 'tcp://' . $ip . ':' . $p;
			$server = stream_socket_server($address);
		}

		if ($server) {
			$this->server = $server;
			$this->address = $address;
			return $address;
		} else {
			throw new CouldNotBindToSocketException();
		}
	}

	public function listen()
	{
		for (;;) {
			$client = stream_socket_accept($this->server);
		}
	}

	public function stop()
	{
		if ($this->server) {
			fclose($this->server);
		}

		$this->server = null;
		$this->address = null;

		return $this;
	}
}