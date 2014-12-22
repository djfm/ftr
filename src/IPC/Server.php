<?php

namespace djfm\ftr\IPC;

use djfm\ftr\Exception\CouldNotBindToSocketException;

use React\Socket\ConnectionException;
use React\EventLoop\Factory as EventLoopFactory;
use React\Socket\Server as SocketServer;
use React\Http\Server as HttpServer;

class Server
{
    private $server;
    private $address;

    protected $loop;

    public function bind($ip = 'localhost', $port = 1024, $maxPort = 2048)
    {
        $loop = EventLoopFactory::create();
        $socket = new SocketServer($loop);
        $http = new HttpServer($socket, $loop);

        $http->on('request', [$this, 'reply']);

        for ($p = $port; $p <= $maxPort; $p++) {
            try {
                $socket->listen($p, $ip);
                break;
            } catch (ConnectionException $e) {
                if ($p === $maxPort) {
                    throw new CouldNotBindToSocketException($e->getMessage(), 0, $e);
                }
            }
        }

        $this->http = $http;
        $this->loop = $loop;

        $this->address = "http://$ip:$p";

        return $this->address;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function run()
    {
        $this->loop->addPeriodicTimer(1, [$this, 'tick']);
        $this->loop->run();
    }

    public function reply($request, $response)
    {
        $response->writeHead(200, array('Content-Type' => 'text/plain'));
        $response->end("ftrftrftr");
    }

    public function tick()
    {
    }
}
