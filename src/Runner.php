<?php

namespace djfm\ftr;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\OutputInterface;

use djfm\ftr\Loader\Loader;
use djfm\ftr\Exception\NoSuchFileOrDirectoryException;
use djfm\ftr\TestPlan\ParallelTestPlan;

class Runner
{
	private $test;
	private $informationOnly = false;
	private $maxProcesses = 1;
	private $shallow = false;
	private $filter;
	private $dataProviderFilter;
	private $outputInterface;
	private $executionPlans = [];

	public function setTest($test)
	{
		$this->test = $test;

		return $this;
	}

	public function setMaxProcesses($n = 1)
	{
		$this->maxProcesses = max((int)$n, 1);

		return $this;
	}

	public function setInformationOnly($i = true)
	{
		$this->informationOnly = (bool)$i;

		return $this;
	}

	public function setShallow($s = true)
	{
		$this->shallow = (bool)$s;

		return $this;
	}

	public function setFilter($f)
	{
		$this->filter = $f;

		return $this;
	}

	public function setDataProviderFilter($z)
	{
		$this->dataProviderFilter = $z;

		return $this;
	}

	public function setOutputInterface(OutputInterface $outputInterface)
	{
		$this->outputInterface = $outputInterface;

		return $this;
	}

	public function write($text)
	{
		if ($this->outputInterface) {
			$this->outputInterface->write($text);
		} else {
			echo $text;
		}

		return $this;
	}

	public function writeln($text)
	{
		if ($this->outputInterface) {
			$this->outputInterface->writeln($text);
		} else {
			echo $text . "\n";
		}

		return $this;
	}

	public function run()
	{
		$this->loadTests();
	}

	public function loadTests()
	{
		$files = [];
		if (is_dir($this->test)) {
			$finder = new Finder();
			$finder->files()->in($this->test);

			if ($this->shallow) {
				$finder->depth(0);
			}

			foreach ($finder as $file) {
				$files[] = $file->getRealPath();
			}
		} else if (is_file($this->test)) {
			$files[] = $this->test;
		} else {
			throw new NoSuchFileOrDirectoryException($this->test);
		}

		$loader = new Loader();
		$testPlan = new ParallelTestPlan();
		foreach ($files as $file) {
			$plan = $loader->loadFile($file);
			if ($plan) {
				$testPlan->addTestPlan($plan);
			}
		}

		$testsCount = $testPlan->getTestsCount();
		$countMessage = $testsCount > 1 ? 'Found %d tests to run.' : 'Found %d test to run.';
		$this->writeln(sprintf("<info>$countMessage</info>", $testsCount));

		$this->executionPlans = $testPlan->getExecutionPlans();

		$epsCount = count($this->executionPlans);
		$epsMessage = $testsCount > 1 ? 'Tests are split into %d execution plans.' : 'There is %d execution plan.';
		$this->writeln(sprintf("<comment>$epsMessage</comment>", $epsCount));
	}
}