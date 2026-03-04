<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'scaffold:workflow',
    description: 'Generate deterministic workflow scaffold JSON',
)]
final class WorkflowScaffoldCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Workflow machine name')
            ->addOption('bundle', null, InputOption::VALUE_REQUIRED, 'Bundle ID the workflow applies to')
            ->addOption('state', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'State IDs (repeatable)')
            ->addOption(
                'transition',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Transition in id:from:to:permission form (repeatable)',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = trim((string) $input->getOption('id'));
        $bundle = trim((string) $input->getOption('bundle'));
        if ($id === '' || $bundle === '') {
            $output->writeln('<error>--id and --bundle are required.</error>');
            return Command::INVALID;
        }

        $states = $this->parseStates($input->getOption('state'));
        $transitions = $this->parseTransitions($input->getOption('transition'));
        if ($transitions === null) {
            $output->writeln('<error>Invalid --transition format. Use id:from:to:permission.</error>');
            return Command::INVALID;
        }

        if ($states === []) {
            $states = ['draft', 'review', 'published', 'archived'];
        }
        if ($transitions === []) {
            $transitions = [
                ['id' => 'submit_review', 'from' => 'draft', 'to' => 'review', 'permission' => 'submit article for review'],
                ['id' => 'publish', 'from' => 'review', 'to' => 'published', 'permission' => 'publish article content'],
                ['id' => 'archive', 'from' => 'published', 'to' => 'archived', 'permission' => 'archive article content'],
            ];
        }

        sort($states);
        usort($transitions, static fn(array $a, array $b): int => strcmp((string) $a['id'], (string) $b['id']));

        $payload = [
            'workflow' => [
                'id' => $id,
                'bundle' => $bundle,
                'states' => array_values(array_unique($states)),
                'transitions' => $transitions,
            ],
        ];

        $output->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function parseStates(mixed $rawStates): array
    {
        if (!is_array($rawStates)) {
            return [];
        }

        $states = [];
        foreach ($rawStates as $state) {
            if (!is_string($state)) {
                continue;
            }
            $normalized = strtolower(trim($state));
            if ($normalized !== '') {
                $states[] = $normalized;
            }
        }

        return $states;
    }

    /**
     * @return list<array{id: string, from: string, to: string, permission: string}>|null
     */
    private function parseTransitions(mixed $rawTransitions): ?array
    {
        if (!is_array($rawTransitions)) {
            return [];
        }

        $transitions = [];
        foreach ($rawTransitions as $raw) {
            if (!is_string($raw)) {
                return null;
            }
            $parts = explode(':', $raw);
            if (count($parts) !== 4) {
                return null;
            }
            [$id, $from, $to, $permission] = array_map(static fn(string $v): string => trim($v), $parts);
            if ($id === '' || $from === '' || $to === '' || $permission === '') {
                return null;
            }
            $transitions[] = [
                'id' => $id,
                'from' => $from,
                'to' => $to,
                'permission' => $permission,
            ];
        }

        return $transitions;
    }
}
