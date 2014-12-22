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
		$maybePlans = $this->post('/executionPlans/get');

		if (isset($maybePlans['plans'])) {

			$plans = ExecutionPlanHelper::unserializeSequence($maybePlans['plans']);
			$this->planToken = $maybePlans['planToken'];

			$this->processPlans($plans);
		}
	}

	public function processPlans($plans)
	{
		foreach ($plans as $plan) {
			$this->processPlan($plan);
		}

		$this->post('/executionPlans/'.$this->planToken.'/done');
	}

	public function processPlan(ExecutionPlanInterface $plan) {

		$reporter = new Reporter($this);

		$plan->setReporter($reporter);

		return $plan->run();
	}
}