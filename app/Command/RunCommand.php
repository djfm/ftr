<?php

namespace djfm\ftr\app\Command;

use Exception;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use djfm\ftr\Runner;

class RunCommand extends Command
{
    private $lastRunData;

    protected function configure()
    {
        $this
        ->setName('run')
        ->setDescription('Run test(s).')
        ;

        $this
        ->addArgument('test', InputArgument::OPTIONAL, 'Choose which test(s) to run - may be a folder or a file.', 'tests')
        ;

        $this
        ->addOption('info', 'i', InputOption::VALUE_NONE, 'Only display information about what would be done.')
        ->addOption('processes', 'p', InputOption::VALUE_REQUIRED, 'Run with at most `p` parallel processes.')
        ->addOption('shallow', 's', InputOption::VALUE_NONE, 'When running tests from a folder, do not recurse.')
        ->addOption('filter', 'f', InputOption::VALUE_REQUIRED, 'Filter the tests to be run.')
        ->addOption('data-provider-filter', 'z', InputOption::VALUE_REQUIRED, 'Filter the data returned by data providers.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $runner = new Runner();
        $runner
        ->setTest($input->getArgument('test'))
        ->setInformationOnly($input->getOption('info'))
        ->setMaxProcesses($input->getOption('processes'))
        ->setShallow($input->getOption('shallow'))
        ->setFilter($input->getOption('filter'))
        ->setDataProviderFilter($input->getOption('data-provider-filter'))
        ->setOutputInterface($output)
        ;
        try {
            $this->lastRunData = $runner->run();
        } catch (Exception $e) {
            $message = trim($e->getMessage());

            if (!$message) {
                $message = 'Sorry, an unspecified error occurred.';
            }

            $output->writeln('<error>' . get_class($e) . ': ' . $message . '</error>');
        }
    }

    public function getLastRunData()
    {
        return $this->lastRunData;
    }
}
