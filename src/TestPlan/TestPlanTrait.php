<?php

namespace djfm\ftr\TestPlan;

trait TestPlanTrait
{
    public function getTestsCount()
    {
        $n = 0;
        foreach ($this->getExecutionPlans() as $plan) {
            $n += $plan->getTestsCount();
        }

        return $n;
    }
}
