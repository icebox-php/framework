<?php

namespace Icebox\Cli\Commands;

use Icebox\Cli\BaseCommand;

/**
 * Command to start interactive PHP console (REPL)
 */
class ConsoleCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'console';
    }

    public function getDescription(): string
    {
        return 'Start interactive PHP console (REPL)';
    }

    public function execute(array $args): int
    {
        // Check if PsySH is available
        $psysh = 'vendor/bin/psysh';
        if (!file_exists($psysh)) {
            $this->error("Error: PsySH not found at $psysh. Run 'composer install' first.");
            return 1;
        }

        // Show message and load PsySH directly
        $this->info("Type 'exit' to quit, 'help' for help.");
        $this->info("");

        // Run PsySH directly without bootstrap config
        // The application will handle autoloading via its own bootstrap
        passthru(escapeshellcmd($psysh), $returnVar);

        return $returnVar;
    }

    public function help(): void
    {
        echo "Usage:\n";
        echo "  php icebox console\n";
        echo "  php icebox c\n\n";
        echo "Start an interactive PHP console (REPL) with Icebox framework loaded.\n";
        echo "This provides access to all Icebox classes and your application models.\n\n";
        echo "Examples:\n";
        echo "  php icebox console          # Start the console\n";
        echo "  php icebox c                # Short alias\n";
    }
}
