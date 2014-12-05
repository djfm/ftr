<?php

namespace djfm\ftr;

use ReflectionClass;
use ReflectionMethod;

class TestClassLoader implements LoaderInterface
{
	public function loadFile($filePath)
	{
		$classes = ClassDiscoverer::getDeclaredClasses($filePath);

		$testPlan = new ParallelTestPlan();

		foreach ($classes as $class) {
			$testPlan->addTestPlan($this->makeTestPlan($filePath, $class));
		}

		return $testPlan;
	}

	public function makeTestPlan($filePath, $className)
	{
		$executionPlan = new TestClassExecutionPlan();
		$executionPlan->setClassFilePath($filePath)->setClassName($className);
		
		$refl = new ReflectionClass($className);


		foreach ($refl->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
			$dcp = new DocCommentParser($method->getDocComment());

			$testMethod = new TestMethod();
			$testMethod->setClassFilePath($filePath)->setClassName($className);

			$expectedInputArgumentNames = array_map(function ($parameter) {
				return $parameter->getName();
			}, $method->getParameters());

			$testMethod->setExpectedInputArgumentNames($expectedInputArgumentNames);

			$executionPlan->addTestMethod($testMethod);
		}

		return $executionPlan;
	}
}