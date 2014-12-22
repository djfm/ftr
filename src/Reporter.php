<?php

namespace djfm\ftr;

use djfm\ftr\Test\TestInterface;
use djfm\ftr\IPC\Client;

class Reporter
{
    private $client;
    private $planToken;

    public function __construct(Client $client, $planToken)
    {
        $this->client = $client;
        $this->planToken = $planToken;
    }

    public function start(TestInterface $test)
    {
        $this->client->post('/messages', [
            'type' => 'testStart',
            'planToken' => $this->planToken,
            'testIdentifier' => $test->getTestIdentifier()
        ]);

        return $this;
    }

    public function end(TestInterface $test, $status)
    {
        $this->client->post('/messages', [
            'type' => 'testEnd',
            'planToken' => $this->planToken,
            'testIdentifier' => $test->getTestIdentifier(),
            'status' => $status
        ]);

        return $this;
    }
}
