<?php

namespace djfm\ftr\TestClass;

use djfm\ftr\Test\TestInterface;

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
        $arguments = [];

        foreach ($this->getExpectedInputArgumentNames() as $argumentName) {
            if (array_key_exists($argumentName, $this->getDependencies())) {
                $dependsOn = $this->getDependencies()[$argumentName];
                if ($this->executionPlan->hasValue($dependsOn)) {
                    $arguments[] = $this->executionPlan->getValue($dependsOn);
                } else {
                    return 'skipped';
                }
            } elseif (array_key_exists($argumentName, $this->inputArguments)) {
                $arguments[] = $this->inputArguments[$argumentName];
            } else {
                throw new Exception("No argument value found for `$argumentName`.");
            }
        }

        $instance = $this->executionPlan->makeInstance();

        $before = [$instance, 'setUp'];
        $after    = [$instance, 'tearDown'];

        $beforeOK = true;
        if (is_callable($before)) {
            try {
                $before();
            } catch (Exception $e) {
                $beforeOK = false;
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
                }
            }
        }

        $afterOK = true;
        if (is_callable($after)) {
            try {
                $after();
            } catch (Exception $e) {
                $afterOK = false;
            }
        }

        if ($beforeOK && $afterOK) {
            if ($testOK) {
                return 'ok';
            } else {
                return 'ko';
            }
        } else {
            return 'error';
        }
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
