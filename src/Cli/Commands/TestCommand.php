<?php

namespace Icebox\Cli\Commands;

use Icebox\Cli\BaseCommand;

/**
 * Command to run PHPUnit tests
 */
class TestCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'test';
    }

    public function getDescription(): string
    {
        return 'Run PHPUnit tests';
    }

    public function execute(array $args): int
    {
        // Remove the first two arguments: script name and 'test'
        $testArgs = array_slice($args, 2);

        // Build PHPUnit command
        $phpunit = __DIR__ . '/../../../vendor/bin/phpunit';
        if (!file_exists($phpunit)) {
            $this->error("Error: PHPUnit not found at $phpunit. Run 'composer install' first.");
            return 1;
        }

        $command = escapeshellcmd($phpunit);

        // If no arguments provided, default to tests folder
        if (empty($testArgs)) {
            $testArgs[] = 'tests';
        }

        foreach ($testArgs as $arg) {
            $command .= ' ' . escapeshellarg($arg);
        }

        $this->info("Running: $command");
        passthru($command, $returnVar);
        return $returnVar;
    }

    public function help(): void
    {
        echo "Usage:\n";
        echo "  php icebox test [test-file] [options]\n\n";
        echo "Run PHPUnit tests. If no arguments are provided, runs all tests in the 'tests' folder.\n";
        echo "You can specify a test file (e.g., tests/create_user_test.php) or a folder.\n";
        echo "Additional options are passed directly to PHPUnit.\n\n";
        echo "Examples:\n";
        echo "  php icebox test                    # Run all tests\n";
        echo "  php icebox test tests/UserTest.php # Run specific test file\n";
        echo "  php icebox test --filter testLogin # Run tests with filter\n";
    }
}
