<?php

namespace djfm\ftr\ExecutionPlan;

use djfm\ftr\Helper\ArraySerializableInterface;
use djfm\ftr\Reporter;

interface ExecutionPlanInterface extends ArraySerializableInterface
{
	public function run();
	public function getTestsCount();

	public function setReporter(Reporter $reporter);
}