<?php

namespace djfm\ftr;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Process;

use djfm\ftr\Loader\Loader;
use djfm\ftr\Exception\NoSuchFileOrDirectoryException;
use djfm\ftr\TestPlan\ParallelTestPlan;
use djfm\ftr\IPC\Server;
use djfm\ftr\Helper\Process as ProcessHelper;
use djfm\ftr\ExecutionPlan\ExecutionPlanHelper;

use Exception;

class Runner extends Server
{
	private $test;
	private $informationOnly = false;
	private $maxProcesses = 1;
	private $shallow = false;
	private $filter;
	private $dataProviderFilter;
	private $outputInterface;
	private $executionPlans = [];
	private $dispatchedPlans = [];

	private $spawnedClients = [];

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

	public function log($data)
	{
		$this->writeln($data);

		return $this;
	}

	public function run()
	{
		$this->loadTests();
		if ($this->informationOnly) {
			$this->writeln("Information only mode - not going to actually run the tests.");
		} else {
			$this->bind();
			$this->writeln(sprintf('<info>Started test server on %s</info>', $this->getAddress()));
			parent::run();
		}
	}

	public function reply($request, $response)
	{
		// don't trust clients to be consistent
		$path = rtrim($request->getPath(), '/');

		$method = $request->getMethod();
		
		if ($method === 'POST') {
			$m = [];
			if ($path === '/executionPlans/get') {
				$response->writeHead(200, array('Content-Type' => 'application/json'));

				$data = [];

				if (!empty($this->executionPlans)) {
					$plans = array_shift($this->executionPlans);
					$this->dispatchedPlans[] = $plans;
					$data['planToken'] = count($this->dispatchedPlans) - 1;
					$data['plans'] = ExecutionPlanHelper::serializeSequence($plans);
				}
				$response->end(json_encode($data));
				return;
			} else if (preg_match('#^/executionPlans/(\d+)/done$#', $path, $m)) {
				$planToken = (int)$m[1];
				$this->log('<info>Finished plan ' . $planToken . '</info>');
				$response->end();
				return;
			}
		}

	    $response->writeHead(404, array('Content-Type' => 'text/plain'));
	    $response->end("ftrftrftr");
	}

	public function tick()
	{
		$remainingClients = [];
		foreach ($this->spawnedClients as $client) {
			if ($client['isRunning']()) {
				$remainingClients[] = $client;
			}
		}
		$this->spawnedClients = $remainingClients;

		while (count($this->spawnedClients) < $this->maxProcesses && !empty($this->executionPlans)) {
			$this->log('<info>Spawning a new client.</info>');
			$this->spawnClient();
		}
	}

	public function spawnClient()
	{
		$pathToWorker = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'worker';

		if (!is_file($pathToWorker)) {
			throw new Exception('Did not find worker script: `' . $pathToWorker . '`');
		}

		$settings = json_encode([
			'serverAddress' => $this->getAddress(),
			'environment' => []
		]);

		$builder = new ProcessBuilder([PHP_BINARY, $pathToWorker, $settings]);
		
		$process = $builder->getProcess();
		$process->start(function ($type, $buffer) {
			$buffer = rtrim($buffer);
			if (Process::ERR === $type) {
				$this->writeln('<error>' . $buffer . '</error>');
			} else {
				$this->writeln('<info>' . $buffer . '</info>');
			}
		});

		$stop = function () use ($process) {
			if ($process->isRunning()) {
				ProcessHelper::killChildren($process);
				$process->stop();
			}
		};

		$isRunning = function () use ($process) {
			return $process->isRunning();
		};

		register_shutdown_function($stop);

		$handle = [
			'process' => $process,
			'stop' => $stop,
			'isRunning' => $isRunning
		];

		$this->spawnedClients[] = $handle;
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