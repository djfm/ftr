<?php

namespace djfm\ftr\tests;

use djfm\ftr\TestClass\TestClassLoader;
use djfm\ftr\ExecutionPlan\ExecutionPlanHelper;

class TestClassLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoaderRecognizesTestClass()
    {
        $loader = new TestClassLoader();

        $this->assertTrue((bool) $loader->loadFile(__DIR__ . '/fixtures/ASimpleTest.php'));
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

    public function testExecutionPlanSerialization()
    {
        $loader = new TestClassLoader();

        $plan = $loader->loadFile(__DIR__ . '/fixtures/ASimpleTest.php');

        $executionPlans = $plan->getExecutionPlans();

        $this->assertEquals(1, count($executionPlans));

        $executionPlan = $executionPlans[0];

        $json = ExecutionPlanHelper::serialize($executionPlan);

        $unserialized = ExecutionPlanHelper::unSerialize($json);

        $this->assertEquals($executionPlan->toArray(), $unserialized->toArray());
    }

    public function testLoaderFindsTestsFromAClassWithDataProviderNotParallel()
    {
        $loader = new TestClassLoader();

        $plan = $loader->loadFile(__DIR__ . '/fixtures/ATestWithDataProviderTest.php');

        $executionPlans = $plan->getExecutionPlans();

        $this->assertEquals(1, count($executionPlans));
        $this->assertEquals(1, count($executionPlans[0]));
        $this->assertEquals(4, $plan->getTestsCount());
    }

    public function testLoaderFindsTestsFromAClassWithDataProviderParallel()
    {
        $loader = new TestClassLoader();

        $src = __DIR__ . '/fixtures/ATestWithParallelDataProviderTest.php';
        $className = 'djfm\ftr\tests\fixtures\ATestWithParallelDataProviderTest';

        $testPlan = $loader->makeTestPlan($src, $className);
        $this->assertEquals(3, count($testPlan->getExecutionPlans()));

        $plan = $loader->loadFile($src);

        $executionPlans = $plan->getExecutionPlans();

        $this->assertEquals(3, count($executionPlans));
        $this->assertEquals(1, count($executionPlans[0]));
        $this->assertEquals(1, count($executionPlans[1]));
        $this->assertEquals(1, count($executionPlans[2]));
        $this->assertEquals(6, $plan->getTestsCount());
    }

    public function testAtDependsAnnotation()
    {
        $loader = new TestClassLoader();

        $src = __DIR__ . '/fixtures/ASimpleDependsTest.php';
        $className = 'djfm\ftr\tests\fixtures\ASimpleDependsTest';

        $testPlan = $loader->makeTestPlan($src, $className);
        $eps = $testPlan->getExecutionPlans();
        $this->assertEquals(1, count($eps));

        $this->assertEquals(['a' => 'testA'], $eps[0]->getTestable(1)->getDependencies());
        $this->assertEquals(['a' => 'testA', 'b' => 'testB'], $eps[0]->getTestable(2)->getDependencies());
        $this->assertEquals(['b' => 'testB', 'a' => 'testA'], $eps[0]->getTestable(3)->getDependencies());
    }

    public function testAtDependsAnnotationWithDataProvider()
    {
        $loader = new TestClassLoader();

        $src = __DIR__ . '/fixtures/AnAdvancedDependsTest.php';
        $className = 'djfm\ftr\tests\fixtures\AnAdvancedDependsTest';

        $testPlan = $loader->makeTestPlan($src, $className);
        $eps = $testPlan->getExecutionPlans();
        $this->assertEquals(1, count($eps));

        $this->assertEquals(['a' => 'testA'], $eps[0]->getTestable(1)->getDependencies());
        $this->assertEquals(['a' => 'testA'], $eps[0]->getTestable(2)->getDependencies());
        $this->assertEquals(['a' => 'testA'], $eps[0]->getTestable(3)->getDependencies());
    }

    public function testAtDependsAnnotationWithParallelDataProvider()
    {
        $loader = new TestClassLoader();

        $src = __DIR__ . '/fixtures/AnAdvancedDependsParallelTest.php';
        $className = 'djfm\ftr\tests\fixtures\AnAdvancedDependsParallelTest';

        $testPlan = $loader->makeTestPlan($src, $className);
        $eps = $testPlan->getExecutionPlans();
        $this->assertEquals(3, count($eps));

        $this->assertEquals(['a' => 'testA'], $eps[0]->getTestable(1)->getDependencies());
        $this->assertEquals(['a' => 'testA'], $eps[1]->getTestable(1)->getDependencies());
        $this->assertEquals(['a' => 'testA'], $eps[2]->getTestable(1)->getDependencies());
    }

    public function testMultipleDataProviders()
    {
        $loader = new TestClassLoader();

        $src = __DIR__ . '/fixtures/DataProviderMadnessTest.php';

        $testPlan = $loader->loadFile($src);
        $eps = $testPlan->getExecutionPlans();
        $this->assertEquals(6, count($eps));
        $this->assertEquals(18, $testPlan->getTestsCount());
    }
}
