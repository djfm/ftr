<?php

namespace djfm\ftr\ExecutionPlan;

use djfm\ftr\Helper\ArraySerializableInterface;

interface ExecutionPlanInterface extends ArraySerializableInterface
{
	public function runBefore();
	public function run();
	public function runAfter();
	public function getTestsCount();
}