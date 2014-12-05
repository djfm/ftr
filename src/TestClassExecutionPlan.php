<?php

namespace djfm\ftr;

class TestClassExecutionPlan implements ExecutionPlanInterface, TestPlanInterface
{
	private $testables = [];

	private $classFilePath;
	private $className;

	public function setClassFilePath($path)
	{
		$this->classFilePath = $path;

		return $this;
	}

	public function setClassName($name)
	{
		$this->className = $name;

		return $this;
	}

	private function shallowClone()
	{
		$clone = new static();
		$clone->setClassFilePath($this->classFilePath);
		$clone->setClassName($this->className);

		return $clone;
	}

	public function getExecutionPlans()
	{
		$testablesChains = [[]];

		foreach ($this->testables as $testables) {
			if (!is_array($testables)) {
				$testables = [$testables];
			}
			$newTestablesChain = [];
			foreach ($testablesChains as $chain) {
				foreach ($testables as $testable) {
					$newChain = $chain;
					$newChain[] = $testable;
					$newTestablesChain[] = $newChain;
				}
			}
			$testablesChains = $newTestablesChain;
		}

		$plans = [];

		foreach ($testablesChains as $chain) {
			$plan = $this->shallowClone();
			foreach ($chain as $testable) {
				$plan->addTestMethod($testable);
			}
			$plans[] = $plan;
		}

		return [$plans];
	}

	public function runBefore()
	{

	}

	public function run()
	{

	}

	public function runAfter()
	{
		
	}

	public function addTestMethod(TestMethod $testMethod)
	{
		$this->testables[] = $testMethod;
		$testMethod->setExecutionPlan($this);

		return $this;
	}

	public function addTestMethods(array $testMethods)
	{
		$this->testables[] = $testMethods;

		return $this;
	}

	public function getTestsCount()
	{
		$n = 0;
		foreach ($this->testables as $testable) {
			if (is_array($testable)) {
				$n += count($testable);
			} else {
				$n += 1;
			}
		}

		return $n;
	}

	public function toArray()
	{
		$testables = array_map(function ($testable) {
			return $testable->toArray();
		}, $this->testables);

		return [
			'testables' => $testables,
			'classFilePath' => $this->classFilePath,
			'className' => $this->className
		];
	}

	public function fromArray(array $arr)
	{
		foreach ($arr['testables'] as $testable) {
			$testMethod = new TestMethod();
			$testMethod->fromArray($testable);
			$this->addTestMethod($testMethod);
		}

		$this->classFilePath = $arr['classFilePath'];
		$this->className = $arr['className'];

		return $this;
	}
}