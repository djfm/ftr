<?php

namespace djfm\ftr\TestClass;

use ReflectionClass, Exception, ReflectionException;

use djfm\ftr\Exception\NotAnExecutionPlanException;
use djfm\ftr\ExecutionPlan\ExecutionPlanInterface;
use djfm\ftr\TestPlan\TestPlanInterface;
use djfm\ftr\Test\TestResult;
use djfm\ftr\Reporter;
use djfm\ftr\Helper\ExceptionHelper;

class TestClassExecutionPlan implements ExecutionPlanInterface, TestPlanInterface
{
    private $testables = [];

    private $classFilePath;
    private $className;
    private $isExecutionPlan = false;
    private $reporter;

    private $values = [];
    private $results = [];

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

    public function getPlanIdentifier()
    {
        return $this->className;
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
            $plan->isExecutionPlan = true;
            foreach ($testables as $testable) {
                $plan->addTestMethod($testable);
            }

            return $plan;
        }, $pars);
    }

    public function getTestable($testNumber)
    {
        return $this->testables[$testNumber];
    }

    public function getTest($testNumber)
    {
        return $this->getTestable($testNumber);
    }

    public function setTestResult($testNumber, TestResult $testResult)
    {
        $this->results[$testNumber] = $testResult;

        return $this;
    }

    public function getTestResult($testNumber)
    {
        return isset($this->results[$testNumber]) ? $this->results[$testNumber] : null;
    }

    public function setValue($testName, $value)
    {
        $this->values[$testName] = $value;

        return $this;
    }

    public function hasValue($testName)
    {
        return array_key_exists($testName, $this->values);
    }

    public function getValue($testName)
    {
        if (!$this->hasValue($testName)) {
            throw new Exception("No recorded value for `$testName`.");
        }

        return $this->values[$testName];
    }

    public function runBefore()
    {
        if (!$this->isExecutionPlan) {
            throw new NotAnExecutionPlanException();
        }

        try {
            $this->call('setUpBeforeClass');
        } catch (ReflectionException $e) {
            // ok
        }
    }

    public function run()
    {
        if (!$this->isExecutionPlan) {
            throw new NotAnExecutionPlanException();
        }

        $beforeOK = true;
        try {
            $this->runBefore();
        } catch (Exception $e) {
            $beforeOK = false;
            $this->reporter->exception($e);
        }

        foreach ($this->testables as $test) {
            if ($beforeOK) {
                $this->reporter->start($test);
                $this->reporter->end($test, $test->run());
            } else {
                $testResult = new TestResult();
                $testResult->setStatus('skipped');
                $this->reporter->end($test, $testResult);
            }
        }

        $afterOK = true;
        try {
            $this->runAfter();
        } catch (Exception $e) {
            $afterOK = false;
            $this->reporter->exception($e);
        }

        return $beforeOK && $afterOK;
    }

    public function runAfter()
    {
        if (!$this->isExecutionPlan) {
            throw new NotAnExecutionPlanException();
        }

        try {
            $this->call('tearDownAfterClass');
        } catch (ReflectionException $e) {
            // ok
        }
    }

    public function addTestMethod(TestMethod $testMethod)
    {
        $testMethod->setTestNumber(count($this->testables));
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
        $testsCount = 0;
        foreach ($this->testables as $testable) {
            if (is_array($testable)) {
                $testsCount += count($testable);
            } else {
                $testsCount += 1;
            }
        }

        return $testsCount;
    }

    public function makeInstance()
    {
        if (!class_exists($this->className)) {
            $this->includeClassFile();
        }

        return new $this->className();
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
            $mThis = new $this->className();
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
            'className' => $this->className,
            'isExecutionPlan' => $this->isExecutionPlan
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
        $this->isExecutionPlan = $arr['isExecutionPlan'];

        return $this;
    }

    public function setReporter(Reporter $reporter)
    {
        $this->reporter = $reporter;

        return $this;
    }
}
