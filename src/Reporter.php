<?php

namespace djfm\ftr;

use Exception;

use djfm\ftr\Test\TestInterface;
use djfm\ftr\Test\TestResult;
use djfm\ftr\IPC\Client;
use djfm\ftr\Helper\ExceptionHelper;

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
            'type'           => 'testStart',
            'planToken'      => $this->planToken,
            'testIdentifier' => $test->getTestIdentifier(),
            'testNumber'     => $test->getTestNumber()
        ]);

        return $this;
    }

    public function end(TestInterface $test, TestResult $testResult)
    {
        $this->client->post('/messages', [
            'type'           => 'testEnd',
            'planToken'      => $this->planToken,
            'testIdentifier' => $test->getTestIdentifier(),
            'testNumber'     => $test->getTestNumber(),
            'testResult'     => $testResult->toArray()
        ]);

        return $this;
    }

    public function exception(Exception $exception)
    {
        $this->client->post('/messages', [
            'type' => 'exception',
            'planToken' => $this->planToken,
            'exception' => ExceptionHelper::toArray($exception)
        ]);
    }
}
