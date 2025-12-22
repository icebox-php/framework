<?php

namespace Icebox\Cli\Commands;

use Icebox\Cli\BaseCommand;
use Icebox\ActiveRecord\MigrationRunner;

/**
 * Command to reset all migrations
 */
class DbResetCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'db:reset';
    }

    public function getDescription(): string
    {
        return 'Rollback all migrations';
    }

    public function execute(array $args): int
    {
        if ($this->showHelpIfRequested($args)) {
            return 0;
        }

        try {
            $runner = new MigrationRunner();
            $rolledBack = $runner->reset();

            if (empty($rolledBack)) {
                $this->info("No migrations to rollback.");
                return 0;
            } else {
                $this->success("Successfully rolled back all " . count($rolledBack) . " migration(s).");
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
        echo "  php icebox db:reset\n\n";
        echo "Rollback all migrations.\n";
        echo "This will undo all migrations that have been executed.\n";
    }
}
