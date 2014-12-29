<?php

namespace djfm\ftr\TestClass;

use djfm\ftr\Test\TestInterface;
use djfm\ftr\Test\TestResult;

use Exception;

class TestMethod implements TestInterface
{
    private $inputArguments = [];
    private $classFilePath;
    private $className;
    private $executionPlan;
    private $expectedInputArgumentNames;
    private $name;
    private $dependencies = [];
    private $expectedException;
    private $testNumber;

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

    public function setExpectedException($expectedException)
    {
        $this->expectedException = $expectedException;

        return $this;
    }

    public function getDependencies()
    {
        return $this->dependencies;
    }

    public function run()
    {
        $testResult = new TestResult();

        $startedAt = microtime(true);
        $arguments = [];

        foreach ($this->getExpectedInputArgumentNames() as $argumentName) {
            if (array_key_exists($argumentName, $this->getDependencies())) {
                $dependsOn = $this->getDependencies()[$argumentName];
                if ($this->executionPlan->hasValue($dependsOn)) {
                    $arguments[$argumentName] = $this->executionPlan->getValue($dependsOn);
                } else {
                    $testResult->setStatus('skipped');
                    return $testResult;
                }
            } elseif (array_key_exists($argumentName, $this->inputArguments)) {
                $arguments[$argumentName] = $this->inputArguments[$argumentName];
            } else {
                throw new Exception("No argument value found for `$argumentName`.");
            }
        }

        $instance = $this->executionPlan->makeInstance();

        $aboutToStart = [$instance, 'aboutToStart'];
        $testResult->setTags($arguments);

        if (is_callable($aboutToStart)) {
            try {
                $aboutToStart($this->name, $arguments);
            } catch (Exception $e) {
                $testResult->addException($e);
            }
        }

        $before     = [$instance, 'setUp'];
        $after      = [$instance, 'tearDown'];

        $beforeOK = true;
        if (is_callable($before)) {
            try {
                $before();
            } catch (Exception $e) {
                $beforeOK = false;
                $testResult->addException($e);
            }
        }

        $testOK = false;
        if ($beforeOK) {
            try {
                $value = call_user_func_array([$instance, $this->name], $arguments);
                $this->executionPlan->setValue($this->name, $value);
                $testOK = true;
            } catch (Exception $e) {
                if ($this->expectedException && $e instanceof $this->expectedException) {
                    $testOK = true;
                } else {
                    $testResult->addException($e);
                }
            }
        }

        $getArtefactsDir = [$instance, 'getArtefactsDir'];

        if (is_callable($getArtefactsDir)) {
            try {
                $artefactsDir = $getArtefactsDir();
                $testResult->addArtefactsDir($artefactsDir);
            } catch (Exception $e) {
                $testResult->addException($e);
            }
        }

        $afterOK = true;
        if (is_callable($after)) {
            try {
                $after();
            } catch (Exception $e) {
                $afterOK = false;
                $testResult->addException($e);
            }
        }

        $runTime = microtime(true) - $startedAt;
        $testResult->setRunTime($runTime);

        if ($beforeOK && $afterOK && $testOK) {
                $testResult->setStatus('ok');
        } else {
            $testResult->setStatus('ko');
        }

        return $testResult;
    }

    public function getRunTime()
    {
        return $this->runTime;
    }

    public function getTestIdentifier()
    {
        return $this->className . '::' . $this->name;
    }

    public function setTestNumber($testNumber)
    {
        $this->testNumber = $testNumber;

        return $this;
    }

    public function getTestNumber()
    {
        return $this->testNumber;
    }

    public function toArray()
    {
        return [
            'classFilePath' => $this->classFilePath,
            'className' => $this->className,
            'expectedInputArgumentNames' => $this->expectedInputArgumentNames,
            'name' => $this->name,
            'dependencies' => $this->dependencies,
            'inputArguments' => $this->inputArguments,
            'expectedException' => $this->expectedException
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
        $this->expectedException = $arr['expectedException'];

        return $this;
    }
}
