<?php

namespace Icebox\Cli\Commands;

use Icebox\Cli\BaseCommand;
use Icebox\ActiveRecord\MigrationRunner;

/**
 * Command to show migration status
 */
class DbStatusCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'db:migrate:status';
    }

    public function getDescription(): string
    {
        return 'Show migration status';
    }

    public function execute(array $args): int
    {
        $this->info("Migration Status");
        $this->info("================");
        $this->info("");

        try {
            $runner = new MigrationRunner();
            $status = $runner->status();

            $this->info("Executed migrations: " . $status['total_executed']);
            if (!empty($status['executed'])) {
                foreach ($status['executed'] as $migration) {
                    $this->info("  [X] " . $migration['file']);
                }
            }

            $this->info("");
            $this->info("Pending migrations: " . $status['total_pending']);
            if (!empty($status['pending'])) {
                foreach ($status['pending'] as $migration) {
                    $this->info("  [ ] " . $migration['file']);
                }
            } else {
                $this->info("  No pending migrations.");
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    public function help(): void
    {
        echo "Usage:\n";
        echo "  php icebox db:migrate:status\n\n";
        echo "Show the status of all database migrations.\n";
        echo "Displays which migrations have been executed and which are pending.\n";
    }
}
