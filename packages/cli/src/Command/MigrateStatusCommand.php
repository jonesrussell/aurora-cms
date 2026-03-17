<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Waaseyaa\Foundation\Migration\Migrator;

#[AsCommand(
    name: 'migrate:status',
    description: 'Show the status of each migration',
)]
final class MigrateStatusCommand extends Command
{
    /** @var \Closure(): array<string, array<string, \Waaseyaa\Foundation\Migration\Migration>> */
    private \Closure $migrationsProvider;

    /**
     * @param \Closure(): array<string, array<string, \Waaseyaa\Foundation\Migration\Migration>> $migrationsProvider
     */
    public function __construct(
        private readonly Migrator $migrator,
        \Closure $migrationsProvider,
    ) {
        $this->migrationsProvider = $migrationsProvider;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $migrations = ($this->migrationsProvider)();
        $status = $this->migrator->status($migrations);

        $completedByName = [];
        foreach ($status['completed'] as $entry) {
            $completedByName[$entry['migration']] = $entry;
        }

        $rows = [];
        foreach ($status['completed'] as $entry) {
            $rows[] = [$entry['migration'], $entry['package'], 'Ran', (string) $entry['batch']];
        }
        foreach ($status['pending'] as $name) {
            $package = str_contains($name, ':') ? substr($name, 0, (int) strpos($name, ':')) : 'unknown';
            $rows[] = [$name, $package, 'Pending', ''];
        }

        $table = new Table($output);
        $table->setHeaders(['Migration', 'Package', 'Status', 'Batch']);
        $table->setRows($rows);
        $table->render();

        return self::SUCCESS;
    }
}
