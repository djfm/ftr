<?php

namespace djfm\ftr\tests;

use djfm\ftr\TestClassLoader;

class TestClassLoaderTest extends \PHPUnit_Framework_TestCase
{
	public function testLoaderRecognizesTestClass()
	{
		$loader = new TestClassLoader();

		$this->assertTrue((bool)$loader->loadFile(__DIR__ . '/fixtures/ASimpleTest.php'));
	}

	public function testLoaderFindsTestsFromASimpleTestClass()
	{
		$loader = new TestClassLoader();

		$plan = $loader->loadFile(__DIR__ . '/fixtures/ASimpleTest.php');

		$executionPlans = $plan->getExecutionPlans();

		$this->assertEquals(1, count($executionPlans));
		$this->assertEquals(1, count($executionPlans[0]));
		$this->assertEquals(3, $plan->getTestsCount());
	}

	public function testLoaderFindsTestsFromAClassWithDataProviderNotParallel()
	{
		$loader = new TestClassLoader();

		$plan = $loader->loadFile(__DIR__ . '/fixtures/ATestWithDataProviderTest.php');

		$executionPlans = $plan->getExecutionPlans();

		$this->assertEquals(1, count($executionPlans));
		$this->assertEquals(1, count($executionPlans[0]));
	}
}