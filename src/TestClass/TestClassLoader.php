<?php

namespace djfm\ftr\TestClass;

use ReflectionClass;
use ReflectionMethod;

use djfm\ftr\Helper\DocCommentParser;
use djfm\ftr\Helper\ClassDiscoverer;
use djfm\ftr\TestPlan\ParallelTestPlan;
use djfm\ftr\Loader\LoaderInterface;

class TestClassLoader implements LoaderInterface
{
    private $bootstrap = '';
    private $dataProviderFilter = '';
    private $filter = '';

    public function setBootstrap($filePath)
    {
        $this->bootstrap = $filePath;

        return $this;
    }

    public function setDataProviderFilter($filter)
    {
        $this->dataProviderFilter = $filter;

        return $this;
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;

        return $this;
    }

    public function loadFile($filePath)
    {
        if ($this->bootstrap) {
            require_once $this->bootstrap;
        }

        if (!preg_match('/[tT]est\.php$/', $filePath)) {
            return false;
        }

        $classes = array_filter(
            ClassDiscoverer::getDeclaredClasses($filePath),
            [$this, 'isTestClass']
        );

        if (empty($classes)) {
            return false;
        }

        $testPlan = new ParallelTestPlan();

        foreach ($classes as $class) {
            $testPlan->addTestPlan($this->makeTestPlan($filePath, $class));
        }

        return $testPlan;
    }

    private function isTestMethod(ReflectionMethod $method, DocCommentParser $dcp)
    {
        return preg_match('/^test/', $method->getName());
    }

    public function isTestClass($className)
    {
        return preg_match('/Test$/', $className);
    }

    public function makeTestPlan($filePath, $className)
    {
        // We need the following line for the subtle case where makeTestPlan
        // would be called bfore loadFile - we may instanciate $className
        // in this function, so we first make sure that the ClassDiscoverer
        // knows about the class as it can only discover it before it is
        // required for the first time.
        ClassDiscoverer::getDeclaredClasses($filePath);

        $executionPlan = new TestClassExecutionPlan();
        $executionPlan->setClassFilePath($filePath)->setClassName($className);

        $refl = new ReflectionClass($className);

        // Prepare dataProviderFilter
        $dataProviderFilter = null;
        if ($this->dataProviderFilter) {
            list ($methodFilter, $argumentsFilter) = explode(':', $this->dataProviderFilter, 2);
            $dataProviderFilter = [
                'methodFilter' => $methodFilter,
                'argumentsFilter' => explode(',', $argumentsFilter)
            ];
        }

        foreach ($refl->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $dcp = new DocCommentParser($method->getDocComment());

            if (!$this->isTestMethod($method, $dcp)) {
                continue;
            }

            $dataProviderArguments = [[]];

            if ($dcp->hasOption('dataprovider')) {
                $dataProviderArguments = $executionPlan->call(
                    $dcp->getOption('dataprovider')
                );

                if ($dataProviderFilter && $method->getName() === $dataProviderFilter['methodFilter']) {
                    $dataProviderArguments = array_filter(
                        $dataProviderArguments,
                        function ($arguments) use ($dataProviderFilter) {
                            foreach ($dataProviderFilter['argumentsFilter'] as $pos => $filter) {
                                if ($pos >= count($arguments)) {
                                    return false;
                                }
                                if (!is_scalar($arguments[$pos])) {
                                    return false;
                                }

                                $regexp = '/' . $filter . '/';

                                if (!preg_match($regexp, (string)$arguments[$pos])) {
                                    return false;
                                }

                                return true;
                            }
                        }
                    );
                }
            }

            $testMethods = [];

            foreach ($dataProviderArguments as $arguments) {
                $testMethod = $this->newTestMethod($filePath, $className, $method, $dcp);
                $testMethod->setDataProviderArguments($arguments);
                $testMethods[] = $testMethod;
            }

            if ($dcp->hasOption('parallelize')) {
                $executionPlan->addTestMethods($testMethods);
            } else {
                foreach ($testMethods as $testMethod) {
                    $executionPlan->addTestMethod($testMethod);
                }
            }
        }

        return $executionPlan;
    }

    private function newTestMethod($filePath, $className, ReflectionMethod $method, DocCommentParser $dcp)
    {
        $testMethod = new TestMethod();
        $testMethod->setClassFilePath($filePath)->setClassName($className);

        $expectedInputArgumentNames = array_map(function ($parameter) {
            return $parameter->getName();
        }, $method->getParameters());

        $testMethod->setExpectedInputArgumentNames($expectedInputArgumentNames);

        $testMethod->setName($method->getName());

        if ($dcp->hasOption('depends')) {
            $on = array_reverse($dcp->getArrayOption('depends'));
            foreach ($on as $offset => $methodName) {
                $argumentName = $expectedInputArgumentNames[count($expectedInputArgumentNames) - 1 - $offset];
                $testMethod->setDependency($argumentName, $methodName);
            }
        }

        if ($dcp->hasOption('expectedException')) {
            $expectedException = $dcp->getOption('expectedException');
            $testMethod->setExpectedException($expectedException);
        }

        return $testMethod;
    }
}
