<?php

namespace Icebox\Cli\Commands;

use Icebox\Cli\BaseCommand;
use Icebox\ActiveRecord\MigrationRunner;

/**
 * Command to run database migrations
 */
class DbMigrateCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'db:migrate';
    }

    public function getDescription(): string
    {
        return 'Run pending database migrations';
    }

    public function execute(array $args): int
    {
        if ($this->showHelpIfRequested($args)) {
            return 0;
        }

        try {
            $runner = new MigrationRunner();
            $migrated = $runner->migrate();

            if (empty($migrated)) {
                $this->info("No pending migrations.");
                return 0;
            } else {
                $this->success("Successfully ran " . count($migrated) . " migration(s):");
                foreach ($migrated as $file) {
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
        echo "  php icebox db:migrate\n\n";
        echo "Run all pending database migrations.\n";
        echo "Migrations are executed in the order they were created.\n";
    }
}
