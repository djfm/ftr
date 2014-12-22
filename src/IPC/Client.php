<?php

namespace djfm\ftr\IPC;

class Client
{
    private $address;

    public function __construct($address = null)
    {
        $this->setAddress($address);
    }

    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    public function get($path = '/')
    {
        return $this->request('GET', $path);
    }

    public function post($path = '/', array $payload = null)
    {
        return $this->request('POST', $path, $payload);
    }

    private function request($method, $path, array $payload = null)
    {
        $url = rtrim($this->address, '/') . '/' . ltrim($path, '/');
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }

        if (is_array($payload)) {
            $json = json_encode($payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $header_size);

        curl_close($ch);

        if (preg_match('#/json$#', $contentType)) {
            $body = json_decode($body, true);
        }

        return $body;
    }
}
