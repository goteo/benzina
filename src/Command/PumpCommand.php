<?php

namespace Goteo\Benzina\Command;

use Goteo\Benzina\Benzina;
use Goteo\Benzina\Iterator\PdoIterator;
use Goteo\Benzina\Pump\PumpInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'benzina:pump',
    description: 'Pump records from a v3 database into a v4 schema.',
)]
class PumpCommand extends Command
{
    public function __construct(
        private Benzina $benzina
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('table', InputArgument::REQUIRED);

        $this->addOption(
            'database',
            null,
            InputOption::VALUE_OPTIONAL,
            'The address of the database to read from',
            'mysql://goteo:goteo@mariadb:3306/benzina'
        );

        $this->addOption(
            'skip-pumped',
            null,
            InputOption::VALUE_NEGATABLE,
            'Skips feeding already pumped records in a batch',
            true
        );

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NEGATABLE,
            'A dry run will perform all steps except the actual pumping',
            false
        );

        $this->addUsage('app:benzina:pump --no-debug user');
        $this->setHelp(
            <<<'EOF'
The <info>%command.name%</info> processes the data in the database table and supplies it to the supporting pumps:

    <info>%command.full_name%</info>

You can avoid possible memory leaks caused by the Symfony profiler with the <info>no-debug</info> flag:

    <info>%command.full_name% --no-debug</info>
EOF
        );
    }

    /**
     * @param ConsoleOutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = new PdoIterator($input->getOption('database'), $input->getArgument('table'));
        $sourceSize = \iterator_count($source);
        $sourceSection = new SymfonyStyle($input, $output->section());

        if ($sourceSize < 1) {
            $sourceSection->writeln('No data found at the given source. Skipping execution.');

            return Command::SUCCESS;
        }

        $sourceSection->writeln(sprintf('Sourcing %d records.', $sourceSize));

        $pumps = $this->benzina->getPumpsFor($source);
        $pumpsCount = \count($pumps);
        $pumpsSection = new SymfonyStyle($input, $output->section());

        if ($pumpsCount < 1) {
            $pumpsSection->writeln('No pumps support the sourced sample. Skipping execution.');

            return Command::SUCCESS;
        }

        $pumpsSection->writeln(sprintf('Pumping with %d pumps.', $pumpsCount));
        $pumpsSection->listing(\array_map(function (PumpInterface $pump) {
            return $pump::class;
        }, $pumps));

        $progressSection = $output->section();
        $progressSection->writeln("Pumping:");
        $progressBar = new ProgressBar($progressSection);
        $progressBar->start($sourceSize);

        $source->rewind();
        foreach ($source as $record) {
            foreach ($pumps as $pump) {
                $pump->setConfig([
                    'skip-pumped' => $input->getOption('skip-pumped'),
                ]);

                if (!$input->getOption('dry-run')) {
                    $pump->pump($record);
                }
            }

            $progressBar->advance();
        }

        $endSection = new SymfonyStyle($input, $output->section());
        $endSection->success('Data processed successfully!');

        return Command::SUCCESS;
    }
}
