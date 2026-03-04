<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'fixture:generate',
    description: 'Generate deterministic fixture scenario JSON from topology templates',
)]
final class FixtureGenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Template: fanout, chain, mixed-workflow')
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'Node count', '8')
            ->addOption('prefix', null, InputOption::VALUE_REQUIRED, 'Node key prefix', 'fixture')
            ->addOption('bundle', null, InputOption::VALUE_REQUIRED, 'Node bundle/type', 'article')
            ->addOption('timestamp', null, InputOption::VALUE_REQUIRED, 'Deterministic base timestamp', '1735689600')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Optional output file path (.json)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $template = strtolower(trim((string) $input->getOption('template')));
        $count = max(2, (int) $input->getOption('count'));
        $prefix = trim((string) $input->getOption('prefix'));
        $bundle = trim((string) $input->getOption('bundle'));
        $timestamp = max(0, (int) $input->getOption('timestamp'));

        if ($template === '' || $prefix === '' || $bundle === '') {
            $output->writeln('<error>--template, --prefix, and --bundle are required.</error>');
            return Command::INVALID;
        }
        if (!in_array($template, ['fanout', 'chain', 'mixed-workflow'], true)) {
            $output->writeln('<error>Unknown --template. Allowed: fanout, chain, mixed-workflow.</error>');
            return Command::INVALID;
        }

        $scenario = match ($template) {
            'fanout' => $this->fanoutScenario($count, $prefix, $bundle, $timestamp),
            'chain' => $this->chainScenario($count, $prefix, $bundle, $timestamp),
            'mixed-workflow' => $this->mixedWorkflowScenario($count, $prefix, $bundle, $timestamp),
            default => null,
        };
        if (!is_array($scenario)) {
            $output->writeln('<error>Failed to generate scenario.</error>');
            return Command::FAILURE;
        }

        ksort($scenario['nodes']);
        usort($scenario['relationships'], static fn(array $a, array $b): int => strcmp((string) ($a['key'] ?? ''), (string) ($b['key'] ?? '')));

        $json = json_encode($scenario, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $outputPath = $input->getOption('output');
        if (is_string($outputPath) && trim($outputPath) !== '') {
            $dir = dirname($outputPath);
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                $output->writeln(sprintf('<error>Unable to create output directory: %s</error>', $dir));
                return Command::FAILURE;
            }
            file_put_contents($outputPath, $json . PHP_EOL);
            $output->writeln(sprintf('Fixture scenario written: %s', $outputPath));
            return Command::SUCCESS;
        }

        $output->writeln($json);

        return Command::SUCCESS;
    }

    /**
     * @return array{nodes: array<string, array<string, mixed>>, relationships: list<array<string, mixed>>}
     */
    private function fanoutScenario(int $count, string $prefix, string $bundle, int $timestamp): array
    {
        $nodes = [];
        $relationships = [];
        $anchor = sprintf('%s_%03d', $prefix, 1);

        for ($i = 1; $i <= $count; $i++) {
            $key = sprintf('%s_%03d', $prefix, $i);
            $nodes[$key] = $this->node($i, $bundle, $timestamp, $i === 1 ? 'published' : 'published');
            if ($i > 1) {
                $relationships[] = $this->relationship(
                    key: sprintf('%s_to_%s_related', $anchor, $key),
                    from: $anchor,
                    to: $key,
                    relationshipType: 'related',
                    status: 1,
                    startDate: $timestamp - ($i * 60),
                );
            }
        }

        return ['nodes' => $nodes, 'relationships' => $relationships];
    }

    /**
     * @return array{nodes: array<string, array<string, mixed>>, relationships: list<array<string, mixed>>}
     */
    private function chainScenario(int $count, string $prefix, string $bundle, int $timestamp): array
    {
        $nodes = [];
        $relationships = [];
        for ($i = 1; $i <= $count; $i++) {
            $key = sprintf('%s_%03d', $prefix, $i);
            $nodes[$key] = $this->node($i, $bundle, $timestamp, 'published');
            if ($i < $count) {
                $to = sprintf('%s_%03d', $prefix, $i + 1);
                $relationships[] = $this->relationship(
                    key: sprintf('%s_to_%s_related', $key, $to),
                    from: $key,
                    to: $to,
                    relationshipType: 'related',
                    status: 1,
                    startDate: $timestamp - ($i * 120),
                );
            }
        }

        return ['nodes' => $nodes, 'relationships' => $relationships];
    }

    /**
     * @return array{nodes: array<string, array<string, mixed>>, relationships: list<array<string, mixed>>}
     */
    private function mixedWorkflowScenario(int $count, string $prefix, string $bundle, int $timestamp): array
    {
        $nodes = [];
        $relationships = [];
        $states = ['published', 'review', 'draft', 'archived'];

        for ($i = 1; $i <= $count; $i++) {
            $key = sprintf('%s_%03d', $prefix, $i);
            $state = $states[($i - 1) % count($states)];
            $nodes[$key] = $this->node($i, $bundle, $timestamp, $state);
            if ($i < $count) {
                $to = sprintf('%s_%03d', $prefix, $i + 1);
                $relationships[] = $this->relationship(
                    key: sprintf('%s_to_%s_supports', $key, $to),
                    from: $key,
                    to: $to,
                    relationshipType: 'supports',
                    status: $state === 'published' ? 1 : 0,
                    startDate: $timestamp - ($i * 180),
                );
            }
        }

        return ['nodes' => $nodes, 'relationships' => $relationships];
    }

    /**
     * @return array<string, mixed>
     */
    private function node(int $position, string $bundle, int $timestamp, string $workflowState): array
    {
        return [
            'title' => sprintf('Fixture Node %03d', $position),
            'type' => $bundle,
            'uid' => 1,
            'created' => $timestamp + ($position * 60),
            'changed' => $timestamp + ($position * 60),
            'status' => $workflowState === 'published' ? 1 : 0,
            'workflow_state' => $workflowState,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function relationship(
        string $key,
        string $from,
        string $to,
        string $relationshipType,
        int $status,
        int $startDate,
    ): array {
        return [
            'key' => $key,
            'relationship_type' => $relationshipType,
            'from' => $from,
            'to' => $to,
            'status' => $status,
            'start_date' => $startDate,
            'end_date' => null,
        ];
    }
}
