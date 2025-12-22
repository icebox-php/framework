<?php

namespace Icebox\Cli;

/**
 * Registry for CLI commands
 */
class CommandRegistry
{
    /**
     * @var array<string, BaseCommand> Registered commands
     */
    private array $commands = [];

    /**
     * @var array<string, string> Command aliases
     */
    private array $aliases = [];

    /**
     * Register a command
     *
     * @param BaseCommand $command
     * @param array $aliases Additional aliases for the command
     */
    public function register(BaseCommand $command, array $aliases = []): void
    {
        $name = $command->getName();
        $this->commands[$name] = $command;

        // Register aliases
        foreach ($aliases as $alias) {
            $this->aliases[$alias] = $name;
        }
    }

    /**
     * Get a command by name or alias
     *
     * @param string $name Command name or alias
     * @return BaseCommand|null
     */
    public function get(string $name): ?BaseCommand
    {
        // Check direct command name
        if (isset($this->commands[$name])) {
            return $this->commands[$name];
        }

        // Check aliases
        if (isset($this->aliases[$name])) {
            $actualName = $this->aliases[$name];
            return $this->commands[$actualName] ?? null;
        }

        return null;
    }

    /**
     * Check if a command exists
     *
     * @param string $name Command name or alias
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->commands[$name]) || isset($this->aliases[$name]);
    }

    /**
     * Get all registered commands
     *
     * @return array<string, BaseCommand>
     */
    public function getAll(): array
    {
        return $this->commands;
    }

    /**
     * Get all command names with their descriptions
     *
     * @return array<string, string>
     */
    public function getCommandDescriptions(): array
    {
        $descriptions = [];
        foreach ($this->commands as $name => $command) {
            $descriptions[$name] = $command->getDescription();
        }
        return $descriptions;
    }

    /**
     * Dispatch a command
     *
     * @param string $name Command name or alias
     * @param array $args Command arguments
     * @return int Exit code
     */
    public function dispatch(string $name, array $args): int
    {
        $command = $this->get($name);
        if (!$command) {
            $this->showUnknownCommandError($name);
            return 1;
        }

        try {
            return $command->execute($args);
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            return 1;
        }
    }

    /**
     * Show help for all commands
     */
    public function showHelp(): void
    {
        echo "Icebox Framework CLI Tool\n";
        echo "=========================\n\n";
        
        echo "Usage:\n";
        echo "  php icebox <command> [options] [arguments]\n\n";

        echo "Available Commands:\n";
        foreach ($this->getCommandDescriptions() as $name => $description) {
            echo "  " . str_pad($name, 20) . " # " . $description . "\n";
        }
        echo "\n";

        echo "Options:\n";
        echo "  -h, --help, help             # Show this help message and quit\n";
        echo "  -h, --help, help <command>   # Show help for a specific command\n";
        echo "                               # Example: php icebox help db:create\n";
        echo " <command> -h, --help, help    # Alias: Show help for a specific command\n";
        echo "                               # Example: php icebox db:create help\n";
        echo "\n";

        echo "For more information about a command, use:\n";
        echo "  php icebox help <command>\n";
        echo "  php icebox <command> help\n";
        echo "\n";
    }

    /**
     * Show unknown command error
     *
     * @param string $name Unknown command name
     */
    private function showUnknownCommandError(string $name): void
    {
        echo "Unknown command: {$name}\n";
        echo "Try: php icebox -h\n";
    }
}
