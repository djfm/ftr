<?php

namespace djfm\ftr;

use Serializable;

interface ExecutionPlanInterface extends ArraySerializableInterface
{
	public function runBefore();
	public function run();
	public function runAfter();
	public function getTestsCount();
}