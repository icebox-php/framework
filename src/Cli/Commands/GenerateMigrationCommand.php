<?php

namespace Icebox\Cli\Commands;

use Icebox\Cli\BaseCommand;
use Icebox\Generator\MigrationGenerator;

/**
 * Command to generate database migrations
 */
class GenerateMigrationCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'generate:migration';
    }

    public function getDescription(): string
    {
        return 'Generate database migration';
    }

    public function execute(array $args): int
    {
        if ($this->showHelpIfRequested($args)) {
            return 0;
        }

        if (!isset($args[3])) {
            $this->error("Migration name is required");
            $this->help();
            return 1;
        }

        $migrationName = $args[3];
        $columns = array_slice($args, 4);

        $this->info("Migration generator started");

        // Create migration file
        $migrationsDir = ROOT_DIR . '/db/migrations';
        $filePath = MigrationGenerator::createMigrationFile($migrationName, $columns, $migrationsDir);

        // Get relative path for display
        $relativePath = str_replace(ROOT_DIR . '/', '', $filePath);
        $this->info("create  $relativePath");
        $this->success("Migration created successfully!");

        return 0;
    }

    public function help(): void
    {
        echo "Usage:\n";
        echo "  php icebox generate:migration <name> [columns]\n\n";
        echo "Examples:\n";
        echo "  php icebox generate:migration create_posts_table\n";
        echo "  php icebox generate:migration create_posts_table title:string content:text\n";
        echo "  php icebox generate:migration add_user_id_to_posts user_id:integer\n";
    }
}
