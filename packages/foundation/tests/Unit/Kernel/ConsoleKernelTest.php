<?php

declare(strict_types=1);

namespace Waaseyaa\Foundation\Tests\Unit\Kernel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Waaseyaa\Foundation\Kernel\ConsoleKernel;

#[CoversClass(ConsoleKernel::class)]
final class ConsoleKernelTest extends TestCase
{
    private string $projectRoot;

    /** @var list<string> */
    private array $originalArgv;

    protected function setUp(): void
    {
        $this->originalArgv = $_SERVER['argv'] ?? [];

        $this->projectRoot = sys_get_temp_dir() . '/waaseyaa_console_test_' . uniqid();
        mkdir($this->projectRoot . '/config', 0755, true);
        mkdir($this->projectRoot . '/storage', 0755, true);

        file_put_contents(
            $this->projectRoot . '/config/waaseyaa.php',
            "<?php return ['database' => ':memory:'];",
        );
        file_put_contents(
            $this->projectRoot . '/config/entity-types.php',
            '<?php return [];',
        );
    }

    protected function tearDown(): void
    {
        $_SERVER['argv'] = $this->originalArgv;

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->projectRoot, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->projectRoot);
    }

    #[Test]
    public function handle_returns_zero_for_list_command(): void
    {
        $_SERVER['argv'] = ['waaseyaa', 'list', '--no-ansi'];

        $kernel = new ConsoleKernel($this->projectRoot);
        $exitCode = $kernel->handle();

        $this->assertSame(0, $exitCode);
    }

    #[Test]
    public function handle_returns_one_when_boot_fails(): void
    {
        // No config, no vendor dir, and an unwritable SQLite path will cause
        // PdoDatabase::createSqlite() to throw when given a non-existent directory path.
        $badRoot = '/nonexistent/path/that/cannot/be/created';
        $kernel = new ConsoleKernel($badRoot);

        ob_start();
        $exitCode = $kernel->handle();
        ob_get_clean();

        $this->assertSame(1, $exitCode);
    }

    #[Test]
    public function handle_returns_zero_for_about_command(): void
    {
        $_SERVER['argv'] = ['waaseyaa', 'about', '--no-ansi'];

        $kernel = new ConsoleKernel($this->projectRoot);
        $exitCode = $kernel->handle();

        $this->assertSame(0, $exitCode);
    }
}
