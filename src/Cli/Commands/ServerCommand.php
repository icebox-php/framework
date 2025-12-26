<?php

namespace Icebox\Cli\Commands;

use Icebox\Cli\BaseCommand;
use Icebox\App;

/**
 * Command to start PHP built-in web server
 */
class ServerCommand extends BaseCommand
{
    public function getName(): string
    {
        return 'server';
    }

    public function getDescription(): string
    {
        return 'Start PHP built-in web server';
    }

    public function execute(array $args): int
    {
        if ($this->showHelpIfRequested($args)) {
            return 0;
        }

        # $host and $port default value
        $host = 'localhost';
        $port = '8800';

        # Parse command line arguments
        foreach ($args as $key => $arg) {
            if (strpos($arg, '-p=') === 0 || strpos($arg, '--port=') === 0) {
                $port = substr($arg, strpos($arg, '=') + 1);
                unset($args[$key]);
            } elseif (strpos($arg, '-h=') === 0 || strpos($arg, '--host=') === 0) {
                $host = substr($arg, strpos($arg, '=') + 1);
                unset($args[$key]);
            }
        }

        # Re-index the array after unsetting elements
        $args = array_values($args);

        $this->info("Starting PHP built-in web server...");
        $this->info("Document root: public");
        $this->info("URL: http://$host:$port");
        $this->info("Press Ctrl+C to stop the server");
        $this->info("");

        $publicDir = App::basePath('public');

        # Check if public directory exists
        if (!is_dir($publicDir)) {
            $this->info("Warning: 'public' directory not found in current working directory.");
            $this->info("The server may not work correctly.");
            $this->info("");
        }

        # Build the command
        $command = "php -S $host:$port -t " . $publicDir;

        # Pass through any additional arguments (e.g., for router script)
        if (count($args) > 2) {
            for ($i = 2; $i < count($args); $i++) {
                $command .= ' ' . escapeshellarg($args[$i]);
            }
        }

        $this->info($command);

        passthru($command, $returnVar);
        return $returnVar;
    }

    public function help(): void
    {
        echo "Usage:\n";
        echo "  php icebox server [options]\n";
        echo "  php icebox serve [options]\n";
        echo "  php icebox s [options]\n\n";
        echo "Start PHP built-in web server with document root set to 'public' directory.\n";
        echo "The server runs on http://localhost:8800 by default.\n\n";
        echo "Options:\n";
        echo "  -p, --port=PORT      Set the port number (default: 8800)\n";
        echo "  -h, --host=HOST      Set the host address (default: localhost)\n\n";
        echo "Examples:\n";
        echo "  php icebox server          # Start the web server with default settings\n";
        echo "  php icebox serve           # Short alias\n";
        echo "  php icebox s               # Short alias\n";
        echo "  php icebox server -p=8011 --host=localhost\n";
        echo "  php icebox serve -p=8011 --host=localhost\n";
        echo "  php icebox s --port=8011 -h=localhost\n\n";
        echo "Note: The server will look for a 'public' directory in the current working directory.\n";
        echo "If the directory doesn't exist, the server may not work correctly.\n";
    }
}
