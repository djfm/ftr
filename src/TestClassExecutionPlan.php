<?php

namespace djfm\ftr;

use ReflectionClass;

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
		$seqOfParMethods = array_map(function ($testable) {
			if (is_array($testable)) {
				return $testable;
			} else {
				return [$testable];
			}
		}, $this->testables);

		$pars = [[]];

		foreach ($seqOfParMethods as $parMethods) {
			$newPars = [];
			foreach ($pars as $sequenceSoFar) {
				foreach ($parMethods as $method) {
					$seq = $sequenceSoFar;
					$seq[] = clone $method;
					$newPars[] = $seq;
				}
			}
			$pars = $newPars;
		}

		return array_map(function ($testables) {
			$plan = $this->shallowClone();
			foreach ($testables as $testable) {
				$plan->addTestMethod($testable);
			}
			return [$plan];
		}, $pars);
	}

	public function getTestable($n)
	{
		return $this->testables[$n];
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

	public function call($name, array $arguments = array())
	{
		if (!class_exists($this->className)) {
			$this->includeClassFile();
		}
		$refl = new ReflectionClass($this->className);
		$method = $refl->getMethod($name);

		$mThis = null;
		if (!$method->isStatic()) {
			$mThis = new $this->className;
		}

		return $method->invokeArgs($mThis, $arguments);
	}

	public function includeClassFile()
	{
		include $this->classFilePath;
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