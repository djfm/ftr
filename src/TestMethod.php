<?php

namespace djfm\ftr;

class TestMethod implements TestInterface
{
	private $inputArguments = [];
	private $classFilePath;
	private $className;
	private $executionPlan;
	private $expectedInputArgumentNames;
	private $name;
	private $dependencies = [];

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

	/**
	 * Methods from TestInterface
	 */
	
	public function setExecutionPlan(TestClassExecutionPlan $plan)
	{
		$this->executionPlan = $plan;

		return $this;
	}

	public function getExpectedInputArgumentNames()
	{
		return $this->expectedInputArgumentNames;
	}

	public function setExpectedInputArgumentNames(array $names)
	{
		$this->expectedInputArgumentNames = $names;		

		return $this;
	}

	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	public function setInputArgumentValue($argumentName, $value)
	{
		$this->inputArguments[$argumentName] = $value;
		return $this;
	}

	public function getInputArguments()
	{
		return $this->inputArguments();
	}

	public function setDataProviderArguments(array $args)
	{
		foreach ($args as $pos => $value) {
			$this->setInputArgumentValue($this->expectedInputArgumentNames[$pos], $value);
		}
	}

	public function setDependency($argumentName, $testName)
	{
		$this->dependencies[$argumentName] = $testName;

		return $this;
	}

	public function setDependencies(array $deps)
	{
		$this->dependencies = $deps;

		return $this;
	}

	public function getDependencies()
	{
		return $this->dependencies;
	}

	public function run()
	{

	}

	public function getOutputValue()
	{

	}

	public function getArtifacts()
	{

	}

	public function getTestIdentifier()
	{
		return $this->className . '::' . $this->name;
	}

	/**
	 * Methods from ArraySerializableInterface
	 */

	public function toArray()
	{
		return [
			'classFilePath' => $this->classFilePath,
			'className' => $this->className,
			'expectedInputArgumentNames' => $this->expectedInputArgumentNames,
			'name' => $this->name,
			'dependencies' => $this->dependencies,
			'inputArguments' => $this->inputArguments
		];
	}

	public function fromArray(array $arr)
	{
		$this->classFilePath = $arr['classFilePath'];
		$this->className = $arr['className'];
		$this->expectedInputArgumentNames = $arr['expectedInputArgumentNames'];
		$this->name = $arr['name'];
		$this->dependencies = $arr['dependencies'];
		$this->inputArguments = $arr['inputArguments'];

		return $this;
	}
}