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
    public function loadFile($filePath)
    {
        $classes = ClassDiscoverer::getDeclaredClasses($filePath);

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

        return $testMethod;
    }
}