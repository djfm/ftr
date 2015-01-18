<?php

namespace djfm\ftr;

use Exception;

use djfm\ftr\Test\TestInterface;
use djfm\ftr\Test\TestResult;
use djfm\ftr\Helper\ExceptionHelper;

use djfm\SocketRPC\Client;

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
        $this->client->send([
            'type'           => 'testStart',
            'planToken'      => $this->planToken,
            'testIdentifier' => $test->getTestIdentifier(),
            'testNumber'     => $test->getTestNumber()
        ]);

        return $this;
    }

    public function end(TestInterface $test, TestResult $testResult)
    {
        $this->client->send([
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
        $this->client->send([
            'type' => 'exception',
            'planToken' => $this->planToken,
            'exception' => ExceptionHelper::toArray($exception)
        ]);
    }
}
