<?php

namespace djfm\ftr\IPC;

use djfm\ftr\Exception\CouldNotConnectToServerException;

class Client
{
	private $address;

	public function __construct($address)
	{
		$this->address = $address;
	}

	public function get($path = '/')
	{
		return $this->request('GET', $path);
	}

	private function request($method, $path, $payload = null)
	{
		$url = rtrim($this->address, '/') . '/' . ltrim($path, '/');
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}
}