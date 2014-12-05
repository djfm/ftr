<?php

namespace djfm\ftr;

class TestMethod implements TestInterface
{
	private $inputArguments;
	private $classFilePath;
	private $className;
	private $executionPlan;
	private $expectedInputArgumentNames;

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

	public function setInputArguments(array $inputArguments = array())
	{
		$this->inputArguments = $inputArguments;

		return $this;
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

	public function getUniqueIdentifier()
	{

	}

	public function getHumanIdentifier()
	{

	}

	public function getTags()
	{

	}

	/**
	 * Methods from ArraySerializableInterface
	 */

	public function toArray()
	{
		return [
			'classFilePath' => $this->classFilePath,
			'className' => $this->className,
			'expectedInputArgumentNames' => $this->expectedInputArgumentNames
		];
	}

	public function fromArray(array $arr)
	{
		$this->classFilePath = $arr['classFilePath'];
		$this->className = $arr['className'];
		$this->expectedInputArgumentNames = $arr['expectedInputArgumentNames'];

		return $this;
	}
}