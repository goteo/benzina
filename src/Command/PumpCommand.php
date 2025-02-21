<?php

namespace Goteo\Benzina\Command;

use Goteo\Benzina\Benzina;
use Goteo\Benzina\Source\PdoSource;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;

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
            'offset',
            null,
            InputOption::VALUE_OPTIONAL,
            'An offset to start sourcing records from',
            0
        );

        $this->addOption(
            'database',
            null,
            InputOption::VALUE_OPTIONAL,
            'The address of the database to read from',
            'mysql://goteo:goteo@mariadb:3306/benzina'
        );

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NEGATABLE,
            'A dry run will perform all steps except the actual pumping',
            false
        );

        $this->addUsage('benzina:pump --no-debug user');
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
        $source = new PdoSource(
            $input->getOption('database'),
            $input->getArgument('table'),
            $input->getOption('offset')
        );

        $sourceSize = $source->size();
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
        $pumpsSection->listing(\array_map(fn($p) => $p::class, $pumps));

        $progressSection = $output->section();
        $progressSection->writeln("Pumping...");
        $progressBar = new ProgressBar($progressSection);
        $progressBar->start($sourceSize);

        $stopwatch = new Stopwatch(true);
        $stopwatch->start('PUMPED');

        foreach ($source->records() as $record) {
            foreach ($pumps as $pump) {
                if (!$input->getOption('dry-run')) {
                    $pump->pump($record);
                }
            }

            $progressBar->advance();
        }

        $pumped = $stopwatch->stop('PUMPED');

        $endSection = new SymfonyStyle($input, $output->section());
        $endSection->write([
            "\n\n",
            \sprintf('Time: %sms, Memory: %s bytes', $pumped->getDuration(), $pumped->getMemory()),
            "\n\n",
            \sprintf('<fg=black;bg=green>OK (%d pumps, %d records)</>', $pumpsCount, $sourceSize)
        ]);

        return Command::SUCCESS;
    }
}
