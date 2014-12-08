<?php

namespace djfm\ftr\TestPlan;

use djfm\ftr\Exception\TestPlanException;

interface TestPlanInterface
{
	/**
	 * @return array [seq, seq, seq] where seq is an array of Execution Plans
	 */
	public function getExecutionPlans();
		
	public function getTestsCount();
}