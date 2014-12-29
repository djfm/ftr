<?php

namespace djfm\ftr\ExecutionPlan;

use djfm\ftr\Helper\ArraySerializableInterface;
use djfm\ftr\Reporter;
use djfm\ftr\Test\TestResult;

interface ExecutionPlanInterface extends ArraySerializableInterface
{
    public function run();
    public function getTestsCount();
    public function getTest($testNumber);
    public function setTestResult($testNumber, TestResult $testResult);
    public function getTestResult($testNumber);
    public function getPlanIdentifier();

    public function setReporter(Reporter $reporter);
}
