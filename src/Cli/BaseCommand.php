<?php

namespace Icebox\Cli;

/**
 * Base class for all CLI commands
 */
abstract class BaseCommand
{
    /**
     * Get the command name
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Get the command description
     *
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * Execute the command
     *
     * @param array $args Command arguments
     * @return int Exit code (0 for success, non-zero for error)
     */
    abstract public function execute(array $args): int;

    /**
     * Show help for this command
     */
    public function help(): void
    {
        echo "Usage: php icebox " . $this->getName() . "\n\n";
        echo $this->getDescription() . "\n";
    }

    /**
     * Print a success message
     *
     * @param string $message
     */
    protected function success(string $message): void
    {
        echo "✓ " . $message . "\n";
    }

    /**
     * Print an error message
     *
     * @param string $message
     */
    protected function error(string $message): void
    {
        echo "✗ " . $message . "\n";
    }

    /**
     * Print an info message
     *
     * @param string $message
     */
    protected function info(string $message): void
    {
        echo $message . "\n";
    }
}
