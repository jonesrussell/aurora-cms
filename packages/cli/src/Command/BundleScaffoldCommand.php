<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'scaffold:bundle',
    description: 'Generate deterministic bundle scaffold JSON',
)]
final class BundleScaffoldCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Bundle machine name')
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Bundle label')
            ->addOption('entity-type', null, InputOption::VALUE_REQUIRED, 'Entity type ID', 'node')
            ->addOption('workflow', null, InputOption::VALUE_REQUIRED, 'Workflow config ID', 'editorial_default')
            ->addOption(
                'field',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Field definition in name:type:required form (repeatable)',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = trim((string) $input->getOption('id'));
        $label = trim((string) $input->getOption('label'));
        $entityType = trim((string) $input->getOption('entity-type'));
        $workflow = trim((string) $input->getOption('workflow'));

        if ($id === '' || $label === '' || $entityType === '' || $workflow === '') {
            $output->writeln('<error>--id, --label, --entity-type, and --workflow are required.</error>');
            return Command::INVALID;
        }

        $fields = $this->parseFields($input->getOption('field'));
        if ($fields === null) {
            $output->writeln('<error>Invalid --field format. Use name:type:required (required must be 0 or 1).</error>');
            return Command::INVALID;
        }

        if ($fields === []) {
            $fields = [
                ['name' => 'title', 'type' => 'string', 'required' => true],
                ['name' => 'body', 'type' => 'text', 'required' => false],
            ];
        }

        usort($fields, static fn(array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));

        $payload = [
            'bundle' => [
                'id' => $id,
                'label' => $label,
                'entity_type' => $entityType,
                'workflow' => $workflow,
                'fields' => $fields,
            ],
        ];

        $output->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }

    /**
     * @return list<array{name: string, type: string, required: bool}>|null
     */
    private function parseFields(mixed $rawFields): ?array
    {
        if (!is_array($rawFields)) {
            return null;
        }

        $fields = [];
        foreach ($rawFields as $raw) {
            if (!is_string($raw)) {
                return null;
            }

            $parts = explode(':', $raw);
            if (count($parts) !== 3) {
                return null;
            }

            [$name, $type, $required] = array_map(static fn(string $v): string => trim($v), $parts);
            if ($name === '' || $type === '' || !in_array($required, ['0', '1'], true)) {
                return null;
            }

            $fields[] = [
                'name' => $name,
                'type' => $type,
                'required' => $required === '1',
            ];
        }

        return $fields;
    }
}
