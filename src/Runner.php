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
use djfm\ftr\Helper\ExceptionHelper;

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
    private $finishedPlans = [];
    private $spawnedClients = [];
    private $dispatchedCount = 0;
    private $testsCount = 0;

    private $results = [
        'summary' => [
            'ok' => 0,
            'ko' => 0,
            'skipped' => 0,
            'unknown' => 0
        ]
    ];

    public function setTest($test)
    {
        $this->test = $test;

        return $this;
    }

    public function setMaxProcesses($n = 1)
    {
        $this->maxProcesses = max((int) $n, 1);

        return $this;
    }

    public function setInformationOnly($i = true)
    {
        $this->informationOnly = (bool) $i;

        return $this;
    }

    public function setShallow($s = true)
    {
        $this->shallow = (bool) $s;

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
        $time = '<comment>[' . date('d M Y H:i:s') . ']</comment>';
        $this->writeln($time . ' ' . $data);

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

        return $this->results;
    }

    public function reply($request, $response)
    {
        // don't trust clients to be consistent
        $path = rtrim($request->getPath(), '/');

        $method = $request->getMethod();

        if ($method === 'POST') {
            $match = [];
            if ($path === '/executionPlans/get') {
                $response->writeHead(200, array('Content-Type' => 'application/json'));
                $response->end(json_encode($this->dispatchPlan()));
                return;
            } elseif (preg_match('#^/executionPlans/(\d+)/done$#', $path, $match)) {
                $planToken = (int) $match[1];
                $this->onPlanFinished($planToken);
                $response->end();
                return;
            } elseif ($path === '/messages') {
                $this->drain($request, function ($body) use ($response) {
                    $data = json_decode($body, true);
                    $this->handleMessage($data);
                    $response->writeHead(200, array('Content-Type' => 'text/plain'));
                    $response->end();
                });
                return;
            }
        }

        $response->writeHead(404, array('Content-Type' => 'text/plain'));
        $response->end("ftrftrftr");
    }

    public function handleMessage(array $message)
    {
        if ($message['type'] === 'testStart') {
            $this->log('<comment>... Starting test  `' . $message['testIdentifier'] . '`</comment>');
        } elseif ($message['type'] === 'testEnd') {
            $status = $message['testResult']['status'];

            $this->dispatchedPlans[$message['planToken']]['plan']->setTestResult(
                $message['testNumber'],
                $message['testResult']
            );

            if ($status === 'ok') {
                $statusSymbol = '<fg=green;bg=white>:-)</fg=green;bg=white>';
                $statusString = 'OK     ';
                ++$this->results['summary']['ok'];
            } elseif ($status === 'ko') {
                $statusSymbol = '<fg=red;bg=black>:<(</fg=red;bg=black>';
                $statusString = 'ERROR  ';
                ++$this->results['summary']['ko'];
            } elseif ($status === 'skipped') {
                $statusSymbol = '<fg=black;bg=yellow>xxx</fg=black;bg=yellow>';
                $statusString = 'SKIPPED';
                ++$this->results['summary']['skipped'];
            } else {
                $statusSymbol = '<fg=black;bg=yellow>O_o</fg=black;bg=yellow>';
                $statusString = 'UNKNWON';
                ++$this->results['summary']['unknown'];
            }

            $this->log($statusSymbol . ' ' . $statusString . ': `' . $message['testIdentifier'] . '`');

            if (isset($message['testResult']['exception'])) {
                $this->printException($message['testResult']['exception']);
            }
        } elseif ($message['type'] === 'exception') {
            if (!isset($this->dispatchedPlans[$message['planToken']]['exception'])) {
                $this->dispatchedPlans[$message['planToken']]['exception'] = [];
            }

            $this->dispatchedPlans[$message['planToken']]['exception'][] = $message['exception'];
            
            $this->printException($message['exception']);
        }
    }

    public function printException(array $exception, $padding = '                       ')
    {
        $text = ExceptionHelper::toString($exception, $padding);
        $this->writeln('<fg=red>' . $text . '</fg=red>');
        $this->writeln("");

        return $this;
    }

    public function dispatchPlan()
    {
        if (empty($this->executionPlans)) {
            return [];
        }

        $data = [];

        $plan = array_shift($this->executionPlans);
        $this->dispatchedCount++;
        $planToken = $this->dispatchedCount;
        $this->dispatchedPlans[$planToken] = [
            'dispatchedAt' => time(),
            'plan' => $plan
        ];
        $this->log('<comment>>>> Dispatching plan ' . $planToken . '</comment>');

        $data['planToken'] = $planToken;
        $data['plan'] = ExecutionPlanHelper::serialize($plan);

        return $data;
    }

    public function onPlanFinished($planToken)
    {
        $this->log('<comment><<< Finished plan ' . $planToken . '</comment>');
        $this->finishedPlans[$planToken] = $this->dispatchedPlans[$planToken];
        unset($this->dispatchedPlans[$planToken]);

        $plan = $this->finishedPlans[$planToken]['plan'];

        for ($i = 0; $i < $plan->getTestsCount(); ++$i) {
            if (!$plan->getTestResult($i)) {

                $test = $plan->getTest($i);

                $plan->setTestResult($i, [
                    'testIdentifier' => $test->getTestIdentifier(),
                    'status' => 'unknown'
                ]);

                ++$this->results['summary']['unknown'];
            }
        }

        $this->spawnClients();
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

        $this->spawnClients();

        if (empty($this->dispatchedPlans) && empty($this->executionPlans)) {
            $this->done();
        }
    }

    public function spawnClients()
    {
        while (count($this->spawnedClients) < $this->maxProcesses && !empty($this->executionPlans)) {
            $this->spawnClient();
        }

        return $this;
    }

    public function done()
    {
        $this->loop->stop();
        $this->log('Done!');
        $this->summarizeResults();
    }

    public function showDots()
    {
        foreach ($this->finishedPlans as $finishedPlan) {
            for ($i = 0; $i < $finishedPlan['plan']->getTestsCount(); ++$i) {
                $result = $finishedPlan['plan']->getTestResult($i);
                $statusChar = '?';
                $color = 'red';
                switch ($result['status']) {
                    case 'ok':
                        $statusChar = '.';
                        $color = 'green';
                        break;
                    case 'ko':
                        $statusChar = 'E';
                        break;
                    case 'skipped':
                        $statusChar = 'S';
                        break;
                    case 'unknown':
                        $statusChar = '?';
                        break;
                }

                $this->write('<fg=' . $color . '>' . $statusChar . '</fg=' . $color . '>');
            }
        }
    }

    public function showErrorsSkippedAndUnknown()
    {
        $skipped = [];
        $unknown = [];

        foreach ($this->finishedPlans as $finishedPlan) {

            if (isset($finishedPlan['exception'])) {
                foreach ($finishedPlan['exception'] as $exception) {
                    $this->writeln('In plan `' . $finishedPlan['plan']->getPlanIdentifier() . '`:');
                    $this->printException($exception, '');
                }
            }

            

            for ($i = 0; $i < $finishedPlan['plan']->getTestsCount(); ++$i) {
                $result = $finishedPlan['plan']->getTestResult($i);

                if (!$result) {
                    $unknown[] = $finishedPlan['plan']->getTest($i)->getTestIdentifier();
                    continue;
                }
                
                if (isset($result['exception'])) {
                    $this->writeln('In test `' . $result['testIdentifier'] . '`:');
                    $this->printException($result['exception'], '');
                } elseif ($result['status'] === 'skipped') {
                    $skipped[] = $result['testIdentifier'];
                }
            }
        }

        if (!empty($skipped)) {
            $this->writeln(sprintf('%d test(s) skipped:', count($skipped)));
            foreach ($skipped as $name) {
                $this->writeln('<fg=red>' . $name . '</fg=red>');
            }
            $this->writeln("");
        }

        if (!empty($unknown)) {
            $this->writeln(sprintf('%d test(s) with unknown status:', count($unknown)));
            foreach ($unknown as $name) {
                $this->writeln('<fg=red>' . $name . '</fg=red>');
            }
            $this->writeln("");
        }
    }

    public function summarizeResults()
    {
        $this->writeln("\n======================\n");

        $this->showErrorsSkippedAndUnknown();

        $this->showDots();

        echo "\n\n";

        $this->writeln(
            sprintf(
                'Ran %1$d tests, <fg=%6$s>%2$d OK</fg=%6$s>, <fg=%7$s>%3$d KO</fg=%7$s>, <fg=%8$s>%4$d SKIPPED</fg=%8$s>, <fg=%9$s>%5$d UNKNWON<fg=%9$s>.',
                $this->testsCount,
                $this->results['summary']['ok'],
                $this->results['summary']['ko'],
                $this->results['summary']['skipped'],
                $this->results['summary']['unknown'],
                $this->results['summary']['ok'] > 0 ? 'green' : 'red',
                $this->results['summary']['ko'] > 0 ? 'red' : 'green',
                $this->results['summary']['skipped'] > 0 ? 'red' : 'green',
                $this->results['summary']['unknown'] > 0 ? 'red' : 'green'
            )
        );
    }

    public function spawnClient()
    {
        $this->log('<comment>### Spawning a new client.</comment>');

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
            $buffer = trim($buffer);
            if ($buffer) {
                if (Process::ERR === $type) {
                    $this->writeln('<error>' . $buffer . '</error>');
                } else {
                    $this->writeln('<info>' . $buffer . '</info>');
                }
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

    public function listTestFiles()
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
        } elseif (is_file($this->test)) {
            $files[] = $this->test;
        } else {
            throw new NoSuchFileOrDirectoryException($this->test);
        }

        return $files;
    }

    public function loadTests()
    {
        $files = $this->listTestFiles();

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

        $this->testsCount = $testsCount;
    }
}
