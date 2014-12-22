<?php

namespace djfm\ftr\TestPlan;

interface TestPlanInterface
{
    /**
	 * @return array [p1, p2, p3, ...] where p* is an ExecutionPlan
	 */
    public function getExecutionPlans();

    public function getTestsCount();
}
