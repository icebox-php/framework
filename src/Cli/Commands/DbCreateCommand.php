<?php

namespace Icebox\Cli\Commands;

use Icebox\Cli\BaseCommand;
use Icebox\ActiveRecord\DatabaseCreator;
use Icebox\Utils;

/**
 * Command to create database from DATABASE_URL
 */
class DbCreateCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'db:create';
    }

    public function getDescription(): string
    {
        return 'Create database from DATABASE_URL';
    }

    public function execute(array $args): int
    {
        $this->info("Creating database from DATABASE_URL...");

        // Get DATABASE_URL from environment
        $databaseUrl = Utils::env('DATABASE_URL');

        if (!$databaseUrl) {
            $this->error("DATABASE_URL environment variable is not set.");
            $this->error("Set DATABASE_URL before running this command.");
            $this->error("Example: DATABASE_URL=sqlite:/path/to/database.sqlite");
            return 1;
        }

        $this->info("Using DATABASE_URL: " . $databaseUrl);

        $result = DatabaseCreator::createDatabase($databaseUrl);

        if ($result['success']) {
            $this->success($result['message']);
            $this->success("Database created successfully!");
            return 0;
        } else {
            $this->error($result['message']);
            $this->error("Failed to create database.");
            return 1;
        }
    }

    public function help(): void
    {
        echo "Usage:\n";
        echo "  php icebox db:create\n\n";
        echo "Create database from DATABASE_URL environment variable.\n";
        echo "This command creates the database if it doesn't exist.\n\n";
        echo "Supported database drivers:\n";
        echo "  - SQLite: Creates database file and directories\n";
        echo "  - MySQL: Creates database on MySQL server\n";
        echo "  - PostgreSQL: Creates database on PostgreSQL server\n\n";
        echo "Examples:\n";
        echo "  # SQLite\n";
        echo "  DATABASE_URL=sqlite:/path/to/database.sqlite\n";
        echo "  php icebox db:create\n\n";
        echo "  # MySQL\n";
        echo "  DATABASE_URL=mysql://user:pass@localhost:3306/database_name\n";
        echo "  php icebox db:create\n\n";
        echo "  # PostgreSQL\n";
        echo "  DATABASE_URL=pgsql://user:pass@localhost:5432/database_name\n";
        echo "  php icebox db:create\n\n";
        echo "Note: The command reads DATABASE_URL from environment variables.\n";
        echo "Make sure DATABASE_URL is set before running the command.\n";
    }
}
