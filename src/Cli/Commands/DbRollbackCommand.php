<?php

namespace Icebox\Cli\Commands;

use Icebox\Cli\BaseCommand;
use Icebox\ActiveRecord\MigrationRunner;

/**
 * Command to rollback migrations
 */
class DbRollbackCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'db:rollback';
    }

    public function getDescription(): string
    {
        return 'Rollback last N migrations (default: 1)';
    }

    public function execute(array $args): int
    {
        $steps = 1;

        // Parse steps parameter
        foreach ($args as $arg) {
            if (strpos($arg, 'steps=') === 0) {
                $steps = (int) substr($arg, 6);
                if ($steps < 1) {
                    $this->error("Error: steps must be at least 1");
                    return 1;
                }
            }
        }

        $this->info("Rolling back $steps migration(s)...");

        try {
            $runner = new MigrationRunner();
            $rolledBack = $runner->rollback($steps);

            if (empty($rolledBack)) {
                $this->info("No migrations to rollback.");
                return 0;
            } else {
                $this->success("Successfully rolled back " . count($rolledBack) . " migration(s):");
                foreach ($rolledBack as $file) {
                    $this->info("  - " . basename($file));
                }
                return 0;
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    public function help(): void
    {
        echo "Usage:\n";
        echo "  php icebox db:rollback [steps=N]\n\n";
        echo "Rollback the last N migrations (default: 1).\n";
        echo "Migrations are rolled back in reverse order of execution.\n\n";
        echo "Examples:\n";
        echo "  php icebox db:rollback          # Rollback 1 migration\n";
        echo "  php icebox db:rollback steps=2  # Rollback 2 migrations\n";
    }
}
