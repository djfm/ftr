<?php

namespace djfm\ftr;

use djfm\ftr\ExecutionPlan\ExecutionPlanHelper;
use djfm\ftr\ExecutionPlan\ExecutionPlanInterface;

use djfm\SocketRPC\Client;

class Worker
{
    private $planToken = null;
    private $serverAddress;
    private $client;

    public function setup(array $settings)
    {
        $this->serverAddress = $settings['serverAddress'];

        $this->client = new Client();
        $this->client->connect($this->serverAddress);

        if ($settings['bootstrap']) {
            require_once $settings['bootstrap'];
        }
    }

    public function run()
    {
        $maybePlan = $this->client->query(['type' => 'getPlan']);

        if (isset($maybePlan['plan'])) {
            $plan = ExecutionPlanHelper::unserialize($maybePlan['plan']);
            $this->planToken = $maybePlan['planToken'];

            $this->processPlan($plan);
        }
    }

    public function processPlan(ExecutionPlanInterface $plan)
    {
        register_shutdown_function(function () {
            $this->client->send([
                'type' => 'finishedPlan',
                'planToken' => $this->planToken
            ]);
        });

        $reporter = new Reporter($this->client, $this->planToken);

        $plan->setReporter($reporter);

        $plan->run();
    }
}
