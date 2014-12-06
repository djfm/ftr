<?php

namespace djfm\ftr\TestPlan;

class ParallelTestPlan implements TestPlanInterface
{
	use TestPlanTrait;
	
	private $plans = [];

	public function addTestPlan(TestPlanInterface $plan)
	{
		$this->plans[] = $plan;

		return $this;
	}

	public function getExecutionPlans()
	{
		$para = array_map(function ($plan) {
			return $plan->getExecutionPlans();
		}, $this->plans);

		return call_user_func_array('array_merge', $para);
	}
}