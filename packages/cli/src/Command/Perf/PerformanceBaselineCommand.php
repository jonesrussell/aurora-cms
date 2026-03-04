<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Command\Perf;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'perf:baseline',
    description: 'Generate a versioned performance baseline artifact',
)]
final class PerformanceBaselineCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('contract-version', null, InputOption::VALUE_REQUIRED, 'Baseline contract version', 'v1.0')
            ->addOption('surface', null, InputOption::VALUE_REQUIRED, 'Baseline surface ID', 'performance_regression_gate')
            ->addOption('snapshot-hash', null, InputOption::VALUE_REQUIRED, 'Snapshot hash to lock')
            ->addOption(
                'threshold',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Latency threshold in surface:ms form (repeatable)',
            )
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Optional output file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $contractVersion = trim((string) $input->getOption('contract-version'));
        $surface = trim((string) $input->getOption('surface'));
        $snapshotHash = trim((string) $input->getOption('snapshot-hash'));
        $thresholds = $this->parseThresholds($input->getOption('threshold'));

        if ($contractVersion === '' || $surface === '' || $snapshotHash === '') {
            $output->writeln('<error>--contract-version, --surface, and --snapshot-hash are required.</error>');
            return Command::INVALID;
        }
        if ($thresholds === null || $thresholds === []) {
            $output->writeln('<error>At least one valid --threshold surface:ms value is required.</error>');
            return Command::INVALID;
        }

        ksort($thresholds);
        $payload = [
            'contract_version' => $contractVersion,
            'surface' => $surface,
            'snapshot_hash' => $snapshotHash,
            'thresholds_ms' => $thresholds,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $outputPath = $input->getOption('output');
        if (is_string($outputPath) && trim($outputPath) !== '') {
            $dir = dirname($outputPath);
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                $output->writeln(sprintf('<error>Unable to create output directory: %s</error>', $dir));
                return Command::FAILURE;
            }
            file_put_contents($outputPath, $json . PHP_EOL);
            $output->writeln(sprintf('Performance baseline written: %s', $outputPath));
            return Command::SUCCESS;
        }

        $output->writeln($json);

        return Command::SUCCESS;
    }

    /**
     * @return array<string, float>|null
     */
    private function parseThresholds(mixed $rawThresholds): ?array
    {
        if (!is_array($rawThresholds)) {
            return null;
        }

        $thresholds = [];
        foreach ($rawThresholds as $raw) {
            if (!is_string($raw)) {
                return null;
            }
            $parts = explode(':', $raw);
            if (count($parts) !== 2) {
                return null;
            }
            $surface = trim($parts[0]);
            $msRaw = trim($parts[1]);
            if ($surface === '' || !is_numeric($msRaw)) {
                return null;
            }
            $thresholds[$surface] = max(0.0, (float) $msRaw);
        }

        return $thresholds;
    }
}
