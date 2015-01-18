<?php

namespace djfm\ftr;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Process\Process;

use djfm\ftr\Loader\Loader;
use djfm\ftr\Exception\NoSuchFileOrDirectoryException;
use djfm\ftr\TestPlan\ParallelTestPlan;
use djfm\ftr\Helper\Process as ProcessHelper;
use djfm\ftr\ExecutionPlan\ExecutionPlanHelper;
use djfm\ftr\Helper\ExceptionHelper;
use djfm\ftr\Test\TestResult;
use djfm\SocketRPC\Server;

use Exception;

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
    private $dispatchedPlans = [];
    private $finishedPlans = [];
    private $spawnedClients = [];
    private $dispatchedCount = 0;
    private $testsCount = 0;
    private $bootstrap = '';
    private $stress = 1;
    private $startedAt;
    private $server;

    private $results = [
        'summary' => [
            'status' => [
                'ok' => 0,
                'ko' => 0,
                'skipped' => 0,
                'unknown' => 0
            ],
            'runTime' => 0
        ]
    ];

    public function addResultToSummary(TestResult $testResult)
    {
        $status = $testResult->getStatus();
        if (!array_key_exists($status, $this->results['summary']['status'])) {
            $status = 'unknown';
        }

        ++$this->results['summary']['status'][$status];

        $this->results['summary']['runTime'] += $testResult->getRunTime();

        return $this;
    }

    public function setTest($test)
    {
        $this->test = $test;

        return $this;
    }

    public function setStress($level)
    {
        $this->stress = $level;

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

    public function setBootstrap($path)
    {
        $this->bootstrap = $path;

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
        if ($this->server) {
            $this->server->stop();
        }

        $this->startedAt = time();
        $this->loadTests();
        if ($this->informationOnly) {
            $this->writeln("Information only mode - not going to actually run the tests.");
        } else {
            $this->server = new Server();
            $this->server->bind();
            $this->writeln(sprintf('<info>Started test server on %s</info>', $this->server->getAddress()));
            $this->setupServer($this->server);
            $this->server->run();
        }

        return $this->results;
    }

    public function setupServer(Server $server)
    {
        $server
        ->on('query', function ($data, $respond) {
            if (isset($data['type'])) {
                switch ($data['type']) {
                    case 'getPlan':
                        $respond($this->dispatchPlan());
                    break;
                }
            };
        })
        ->on('send', function ($data) {
            if (isset($data['type'])) {
                switch ($data['type']) {
                    case 'finishedPlan':
                        $this->onPlanFinished($data['planToken']);
                    break;
                    case 'testStart':
                        $this->log('<comment>... Starting test  `' . $data['testIdentifier'] . '`</comment>');
                    break;
                    case 'testEnd':
                        $this->handleTestEnd($data);
                    break;
                    case 'exception':
                        if (!isset($this->dispatchedPlans[$data['planToken']]['exception'])) {
                            $this->dispatchedPlans[$data['planToken']]['exception'] = [];
                        }

                        $this->dispatchedPlans[$data['planToken']]['exception'][] = $data['exception'];

                        $this->printException($data['exception']);
                    break;
                }
            };
        })
        ->on('tick', function () {
            $this->tick();
        });
    }

    public function handleTestEnd(array $message)
    {
        $testResult = new TestResult();
        $testResult->fromArray($message['testResult']);

        $this->handleResult($testResult);

        $status = $testResult->getStatus();

        $this->dispatchedPlans[$message['planToken']]['plan']->setTestResult(
            $message['testNumber'],
            $testResult
        );

        if ($status === 'ok') {
            $statusSymbol = '<fg=green;bg=white>:-)</fg=green;bg=white>';
            $statusString = 'OK     ';
        } elseif ($status === 'ko') {
            $statusSymbol = '<fg=red;bg=black>:<(</fg=red;bg=black>';
            $statusString = 'ERROR  ';
        } elseif ($status === 'skipped') {
            $statusSymbol = '<fg=black;bg=yellow>xxx</fg=black;bg=yellow>';
            $statusString = 'SKIPPED';
        } else {
            $statusSymbol = '<fg=black;bg=yellow>O_o</fg=black;bg=yellow>';
            $statusString = 'UNKNWON';
        }

        $this->log($statusSymbol . ' ' . $statusString . ': `' . $message['testIdentifier'] . '`');

        foreach ($testResult->getExceptions() as $exception) {
            $this->printException($exception);
        }
    }

    public function handleResult(TestResult $testResult)
    {
        $this->dumpResultToHistory($testResult);
        $this->addResultToSummary($testResult);
    }

    public function dumpResultToHistory(TestResult $testResult)
    {
        $testData = $testResult->toArray(false); // false: don't include zipped artefacts

        $historyRoot = 'test-history';

        if (!is_dir($historyRoot)) {
            mkdir($historyRoot, 0777, true);
        }

        $infoFile = $historyRoot . DIRECTORY_SEPARATOR . 'info.json';
        $historyFile = $historyRoot . DIRECTORY_SEPARATOR . 'history.json.stream';

        $relativeArtefactsRoot = 'artefacts';
        $absoluteArtefactsRoot = $historyRoot . DIRECTORY_SEPARATOR . $relativeArtefactsRoot;

        if (!is_dir($absoluteArtefactsRoot)) {
            mkdir($absoluteArtefactsRoot, 0777, true);
        }

        $infoHandle = fopen($infoFile, 'c+');

        if (!$infoHandle) {
            throw new Exception('Could not create file: ' + $infoFile);
        }

        if (!flock($infoHandle, LOCK_EX)) {
            throw new Exception('Could not acquire exclusive lock on file: ' + $infoFile);
        }

        try {
            clearstatcache();
            $size = filesize($infoFile);
            $info = $size > 0 ? json_decode(fread($infoHandle, $size), true) : null;

            if (!$info) {
                $info = [
                    'historySize' => 0
                ];
            }

            $nextId = $info['historySize']++;

            $absoluteArtefacts = $absoluteArtefactsRoot . DIRECTORY_SEPARATOR . $nextId;
            $artefacts = $relativeArtefactsRoot . DIRECTORY_SEPARATOR . $nextId;

            $testData['historyId'] = $nextId;
            $testData['artefacts'] = $artefacts;

            $testResult->unpackArtefactsDir($absoluteArtefacts);

            file_put_contents($historyFile, json_encode($testData) . "\n", FILE_APPEND | LOCK_EX);

            ftruncate($infoHandle, 0);
            rewind($infoHandle);

            fwrite($infoHandle, json_encode($info));
        } finally {
            flock($infoHandle, LOCK_UN);
            fclose($infoHandle);
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
        $planToken = count($this->executionPlans);
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
                $testResult = $plan->getTest($i)->getInitializedResult();
                $testResult->setStatus('unknown');
                $plan->setTestResult($i, $testResult);
                $this->handleResult($testResult);
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
        $this->server->stop();
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
                switch ($result->getStatus()) {
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
                $testResult     = $finishedPlan['plan']->getTestResult($i);
                $testIdentifier = $finishedPlan['plan']->getTest($i)->getTestIdentifier();

                foreach ($testResult->getExceptions() as $exception) {
                    $this->writeln('In test `' . $testIdentifier . '`:');
                    $this->printException($exception, '');
                }

                if ($testResult->getStatus() === 'skipped') {
                    $skipped[] = $testIdentifier;
                } elseif ($testResult->getStatus() === 'unknown') {
                    $unknown[] = $testIdentifier;
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
        ksort($this->finishedPlans);

        $this->writeln("\n======================\n");

        $this->showErrorsSkippedAndUnknown();

        $this->showDots();

        echo "\n\n";

        $this->writeln(
            sprintf(
                'Ran %1$d tests, <fg=%6$s>%2$d OK</fg=%6$s>, <fg=%7$s>%3$d KO</fg=%7$s>, <fg=%8$s>%4$d SKIPPED</fg=%8$s>, <fg=%9$s>%5$d UNKNWON</fg=%9$s>.',
                $this->testsCount,
                $this->results['summary']['status']['ok'],
                $this->results['summary']['status']['ko'],
                $this->results['summary']['status']['skipped'],
                $this->results['summary']['status']['unknown'],
                $this->results['summary']['status']['ok'] > 0 ? 'green' : 'red',
                $this->results['summary']['status']['ko'] > 0 ? 'red' : 'green',
                $this->results['summary']['status']['skipped'] > 0 ? 'red' : 'green',
                $this->results['summary']['status']['unknown'] > 0 ? 'red' : 'green'
            )
        );

        $realTime = $this->results['summary']['runTime'];
        $realDuration = sprintf("%.2f", $realTime) . 's';

        $userTime = time() - $this->startedAt;
        $userDuration = sprintf("%.2f", $userTime) . 's';

        $this->writeln(sprintf('[Took %s (%s real, unparallelized time)]', $userDuration, $realDuration));
    }

    public function spawnClient()
    {
        $this->log('<comment>### Spawning a new client.</comment>');

        $pathToWorker = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'worker';

        if (!is_file($pathToWorker)) {
            throw new Exception('Did not find worker script: `' . $pathToWorker . '`');
        }

        $settings = json_encode([
            'serverAddress' => $this->server->getAddress(),
            'environment' => [],
            'bootstrap' => $this->bootstrap
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

        $loader->setBootstrap($this->bootstrap);
        $loader->setDataProviderFilter($this->dataProviderFilter);
        $loader->setFilter($this->filter);

        $testPlan = new ParallelTestPlan();
        foreach ($files as $file) {
            for ($n = 0; $n < $this->stress; ++$n) {
                $plan = $loader->loadFile($file);
                if ($plan) {
                    $testPlan->addTestPlan($plan);
                }
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
