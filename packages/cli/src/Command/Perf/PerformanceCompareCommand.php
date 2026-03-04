<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Command\Perf;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'perf:compare',
    description: 'Compare current performance measurements against a baseline artifact',
)]
final class PerformanceCompareCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('baseline', null, InputOption::VALUE_REQUIRED, 'Baseline artifact JSON path')
            ->addOption('current', null, InputOption::VALUE_REQUIRED, 'Current measurements JSON path')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Emit JSON result payload');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $baselinePath = trim((string) $input->getOption('baseline'));
        $currentPath = trim((string) $input->getOption('current'));
        if ($baselinePath === '' || $currentPath === '') {
            $output->writeln('<error>--baseline and --current are required.</error>');
            return Command::INVALID;
        }

        $baseline = $this->readJson($baselinePath);
        $current = $this->readJson($currentPath);
        if (!is_array($baseline) || !is_array($current)) {
            $output->writeln('<error>Unable to read baseline/current JSON artifacts.</error>');
            return Command::FAILURE;
        }

        $expectedHash = is_string($baseline['snapshot_hash'] ?? null) ? $baseline['snapshot_hash'] : '';
        $actualHash = is_string($current['snapshot_hash'] ?? null) ? $current['snapshot_hash'] : '';
        $thresholds = is_array($baseline['thresholds_ms'] ?? null) ? $baseline['thresholds_ms'] : [];
        $durations = is_array($current['durations_ms'] ?? null) ? $current['durations_ms'] : [];

        $violations = [];
        if ($expectedHash === '' || $actualHash === '' || $expectedHash !== $actualHash) {
            $violations[] = sprintf('snapshot_hash mismatch (expected %s, got %s)', $expectedHash, $actualHash);
        }

        foreach ($thresholds as $surface => $threshold) {
            if (!is_string($surface) || !is_numeric($threshold)) {
                continue;
            }
            if (!isset($durations[$surface]) || !is_numeric($durations[$surface])) {
                $violations[] = sprintf('missing duration for surface "%s"', $surface);
                continue;
            }

            $duration = (float) $durations[$surface];
            $limit = (float) $threshold;
            if ($duration > $limit) {
                $violations[] = sprintf('%s drifted: %.3fms > %.3fms', $surface, $duration, $limit);
            }
        }

        $result = [
            'status' => $violations === [] ? 'ok' : 'drift_detected',
            'baseline' => $baselinePath,
            'current' => $currentPath,
            'violation_count' => count($violations),
            'violations' => $violations,
        ];

        if ((bool) $input->getOption('json')) {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } elseif ($violations === []) {
            $output->writeln('Performance compare status: ok');
        } else {
            $output->writeln('Performance compare status: drift_detected');
            foreach ($violations as $violation) {
                $output->writeln('- ' . $violation);
            }
        }

        return $violations === [] ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJson(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw)) {
            return null;
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
