<?php

namespace djfm\ftr;

use djfm\ftr\IPC\Client;
use djfm\ftr\ExecutionPlan\ExecutionPlanHelper;
use djfm\ftr\ExecutionPlan\ExecutionPlanInterface;

class Worker extends Client
{
    private $planToken = null;

    public function setup(array $settings)
    {
        $this->setAddress($settings['serverAddress']);
    }

    public function run()
    {
        $maybePlan = $this->post('/executionPlans/get');

        if (isset($maybePlan['plans'])) {

            $plans = ExecutionPlanHelper::unserializeSequence($maybePlan['plan']);
            $this->planToken = $maybePlan['planToken'];

            $this->processPlans($plans);
        }
    }

    public function processPlan(ExecutionPlanInterface $plan)
    {
        $reporter = new Reporter($this);

        $plan->setReporter($reporter);

        $plan->run();

        $this->post('/executionPlans/'.$this->planToken.'/done');
    }
}
