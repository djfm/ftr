<?php

namespace djfm\ftr;

class SequentialTestPlan implements TestPlanInterface
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
		$plans = [[]];

		$seqOfParSeqs = array_map(function ($plan) {
			return $plan->getExecutionPlans();
		}, $this->plans);

		foreach ($seqOfParSeqs as $parSeqs) {
			$newPlans = [];
			foreach ($plans as $seq) {
				foreach ($parSeqs as $newSeq) {
					$newPlans[] = array_merge($seq, $newSeq);
				}
			}
			$plans = $newPlans;
		}

		return $plans;
	}
}