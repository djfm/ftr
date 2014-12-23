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

        if (isset($maybePlan['plan'])) {
            $plan = ExecutionPlanHelper::unserialize($maybePlan['plan']);
            $this->planToken = $maybePlan['planToken'];

            $this->processPlan($plan);
        }
    }

    public function processPlan(ExecutionPlanInterface $plan)
    {
        register_shutdown_function(function () {
            $this->post('/executionPlans/'.$this->planToken.'/done');
        });
        
        $reporter = new Reporter($this, $this->planToken);

        $plan->setReporter($reporter);

        $plan->run();
    }
}
