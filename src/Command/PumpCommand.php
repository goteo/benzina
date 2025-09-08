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
        private Benzina $benzina,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('table', InputArgument::REQUIRED)
            ->addOption(
                'offset',
                null,
                InputOption::VALUE_OPTIONAL,
                'An offset to start sourcing records from',
                0
            )
            ->addOption(
                'database',
                null,
                InputOption::VALUE_OPTIONAL,
                'The address of the database to read from',
                'mysql://goteo:goteo@mariadb:3306/benzina'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NEGATABLE,
                'A dry run will perform all steps except the actual pumping',
                false
            )
            ->addUsage('benzina:pump --no-debug user')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> processes the data in the database table and supplies it to the supporting pumps:

    <info>%command.full_name%</info>

You can avoid possible memory leaks caused by the Symfony profiler with the <info>no-debug</info> flag:

    <info>%command.full_name% --no-debug</info>
EOF);
    }

    /**
     * @param ConsoleOutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $source = new PdoSource(
            $input->getOption('database'),
            $input->getArgument('table'),
            $input->getOption('offset')
        );

        $sourceSize = $source->size();

        if ($sourceSize < 1) {
            $io->writeln('No data found at the given source. Skipping execution.');

            return Command::SUCCESS;
        }

        $io->writeln(sprintf('Sourcing %d records.', $sourceSize));

        $pumps = $this->benzina->getPumpsFor($source);
        $pumpsCount = \count($pumps);

        if ($pumpsCount < 1) {
            $io->writeln('No pumps support the sourced sample. Skipping execution.');

            return Command::SUCCESS;
        }

        $io->writeln(sprintf('Pumping with %d pumps.', $pumpsCount));
        $io->listing(\array_map(static fn ($p) => $p::class, $pumps));

        $progressBar = new ProgressBar($output, $sourceSize);
        $progressBar->setRedrawFrequency(max(1, (int) $sourceSize / 100));
        $progressBar->start();

        $stopwatch = new Stopwatch(true);
        $stopwatch->start('PUMPED');

        $context = [
            'count' => 0,
            'source' => $source,
            'options' => $input->getOptions(),
            'arguments' => $input->getArguments(),
            'previous_record' => null,
        ];

        foreach ($source->records() as $record) {
            foreach ($pumps as $pump) {
                $pump->pump($record, $context);
            }

            $context['count'] = $context['count']++;
            $context['previous_record'] = $record;

            $progressBar->advance();
        }

        $pumped = $stopwatch->stop('PUMPED');

        $io->newLine(2);
        $io->writeln(sprintf(
            'Time: %sms, Memory: %s bytes',
            $pumped->getDuration(),
            $pumped->getMemory()
        ));

        $io->success(sprintf('OK (%d pumps, %d records)', $pumpsCount, $sourceSize));

        return Command::SUCCESS;
    }
}
