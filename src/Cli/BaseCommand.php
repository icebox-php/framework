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

    /**
     * Check if help is requested in arguments and show help if so
     *
     * @param array $args Command arguments
     * @return bool True if help was shown, false otherwise
     */
    protected function showHelpIfRequested(array $args): bool
    {
        // Skip script name (index 0) and command name (index 1)
        $filteredArgs = array_slice($args, 2);
        
        if (in_array('--help', $filteredArgs) || in_array('-h', $filteredArgs) || in_array('help', $filteredArgs)) {
            $this->help();
            return true;
        }
        
        return false;
    }
}
