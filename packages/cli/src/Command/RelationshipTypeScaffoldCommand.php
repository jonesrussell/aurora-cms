<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'scaffold:relationship',
    description: 'Generate deterministic relationship-type scaffold JSON',
)]
final class RelationshipTypeScaffoldCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Relationship type machine name')
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Relationship type label')
            ->addOption('directionality', null, InputOption::VALUE_REQUIRED, 'directed or bidirectional', 'directed')
            ->addOption('inverse', null, InputOption::VALUE_REQUIRED, 'Optional inverse relationship type ID')
            ->addOption('default-status', null, InputOption::VALUE_REQUIRED, 'Default publication status (0/1)', '1');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = trim((string) $input->getOption('id'));
        $label = trim((string) $input->getOption('label'));
        $directionality = strtolower(trim((string) $input->getOption('directionality')));
        $inverse = trim((string) $input->getOption('inverse'));
        $defaultStatus = (int) $input->getOption('default-status') === 1 ? 1 : 0;

        if ($id === '' || $label === '') {
            $output->writeln('<error>--id and --label are required.</error>');
            return Command::INVALID;
        }
        if (!in_array($directionality, ['directed', 'bidirectional'], true)) {
            $output->writeln('<error>--directionality must be "directed" or "bidirectional".</error>');
            return Command::INVALID;
        }

        $payload = [
            'relationship_type' => [
                'id' => $id,
                'label' => $label,
                'directionality' => $directionality,
                'inverse' => $inverse !== '' ? $inverse : null,
                'default_status' => $defaultStatus,
            ],
        ];

        $output->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }
}
