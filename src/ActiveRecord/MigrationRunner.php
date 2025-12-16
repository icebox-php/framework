<?php

namespace Icebox\ActiveRecord;

/**
 * MigrationRunner - Handles database migration execution
 */
class MigrationRunner
{
    private $migrationsDir;
    private $connection;
    
    /**
     * Constructor
     *
     * @param string|null $migrationsDir Path to migrations directory
     */
    public function __construct(?string $migrationsDir = null)
    {
        if ($migrationsDir === null) {
            if (!defined('ROOT_DIR')) {
                throw new \RuntimeException('ROOT_DIR constant is not defined');
            }
            $this->migrationsDir = ROOT_DIR . '/db/migrations';
        } else {
            $this->migrationsDir = $migrationsDir;
        }
        $this->connection = Config::getConnection();
    }
    
    /**
     * Create schema_migrations table if it doesn't exist
     *
     * @return bool True if table was created or already exists
     */
    public function createSchemaMigrationsTable(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS schema_migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        try {
            $this->connection->exec($sql);
            return true;
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to create schema_migrations table: " . $e->getMessage());
        }
    }
    
    /**
     * Get all migration files from migrations directory
     *
     * @return array Sorted list of migration file paths
     */
    private function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsDir)) {
            return [];
        }
        
        $files = glob($this->migrationsDir . '/*.php');
        sort($files); // Sort by filename (includes timestamp)
        return $files;
    }
    
    /**
     * Get executed migrations from schema_migrations table
     *
     * @return array List of migration names that have been executed
     */
    private function getExecutedMigrations(): array
    {
        $this->createSchemaMigrationsTable();
        
        $sql = "SELECT migration FROM schema_migrations ORDER BY executed_at";
        $stmt = $this->connection->query($sql);
        
        $migrations = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $migrations[] = $row['migration'];
        }
        $stmt->closeCursor();
        
        return $migrations;
    }
    
    /**
     * Extract migration name from filename
     *
     * @param string $filePath Full path to migration file
     * @return string Migration name (without timestamp and .php extension)
     */
    private function getMigrationNameFromFile(string $filePath): string
    {
        $filename = basename($filePath, '.php');
        // Remove timestamp prefix (format: YYYYMMDDHHMMSS_migration_name)
        if (preg_match('/^\d+_(.+)$/', $filename, $matches)) {
            return $matches[1];
        }
        return $filename;
    }
    
    /**
     * Execute a migration file
     *
     * @param string $filePath Path to migration file
     * @param string $direction 'up' or 'down'
     * @return bool True if successful
     * @throws \RuntimeException If migration fails
     */
    private function executeMigration(string $filePath, string $direction): bool
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Migration file not found: $filePath");
        }
        
        // Include the migration file
        require_once $filePath;
        
        // Get migration class name from filename
        $filename = basename($filePath, '.php');
        $className = 'Migration' . str_replace(' ', '_', ucwords(preg_replace('/[^a-zA-Z0-9]/', ' ', $filename)));
        
        // Check if class exists
        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class '$className' not found in file: $filePath");
        }
        
        // Instantiate migration
        $migration = new $className();
        
        // Validate migration has required methods
        if (!method_exists($migration, 'up') || !method_exists($migration, 'down')) {
            throw new \RuntimeException("Migration class '$className' must have both up() and down() methods");
        }
        
        // Execute migration
        if ($direction === 'up') {
            $migration->up();
        } else {
            $migration->down();
        }
        
        return true;
    }
    
    /**
     * Run pending migrations
     *
     * @return array List of migrated files
     * @throws \RuntimeException If any migration fails (stops entire process)
     */
    public function migrate(): array
    {
        $this->createSchemaMigrationsTable();
        
        $migrationFiles = $this->getMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        $migrated = [];
        
        foreach ($migrationFiles as $filePath) {
            $migrationName = $this->getMigrationNameFromFile($filePath);
            
            // Skip already executed migrations
            if (in_array($migrationName, $executedMigrations)) {
                continue;
            }
            
            // Execute migration
            $this->executeMigration($filePath, 'up');
            
            // Record migration
            $sql = "INSERT INTO schema_migrations (migration) VALUES (:migration)";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':migration', $migrationName, \PDO::PARAM_STR);
            $stmt->execute();
            
            $migrated[] = $filePath;
        }
        
        return $migrated;
    }
    
    /**
     * Rollback migrations
     *
     * @param int $steps Number of migrations to rollback (default: 1)
     * @return array List of rolled back files
     * @throws \RuntimeException If any rollback fails (stops entire process)
     */
    public function rollback(int $steps = 1): array
    {
        $this->createSchemaMigrationsTable();
        
        if ($steps < 1) {
            throw new \InvalidArgumentException("Steps must be at least 1");
        }
        
        // Get executed migrations in reverse order (newest first)
        $sql = "SELECT migration FROM schema_migrations ORDER BY executed_at DESC LIMIT :limit";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':limit', $steps, \PDO::PARAM_INT);
        $stmt->execute();
        
        $migrationsToRollback = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $migrationsToRollback[] = $row['migration'];
        }
        $stmt->closeCursor();
        
        if (empty($migrationsToRollback)) {
            return [];
        }
        
        $rolledBack = [];
        
        foreach ($migrationsToRollback as $migrationName) {
            // Find migration file
            $migrationFiles = $this->getMigrationFiles();
            $filePath = null;
            
            foreach ($migrationFiles as $file) {
                if ($this->getMigrationNameFromFile($file) === $migrationName) {
                    $filePath = $file;
                    break;
                }
            }
            
            if (!$filePath) {
                throw new \RuntimeException("Migration file not found for: $migrationName");
            }
            
            // Execute rollback
            $this->executeMigration($filePath, 'down');
            
            // Remove from schema_migrations
            $sql = "DELETE FROM schema_migrations WHERE migration = :migration";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':migration', $migrationName, \PDO::PARAM_STR);
            $stmt->execute();
            
            $rolledBack[] = $filePath;
        }
        
        return $rolledBack;
    }
    
    /**
     * Rollback all migrations
     *
     * @return array List of all rolled back files
     */
    public function reset(): array
    {
        $this->createSchemaMigrationsTable();
        
        // Get count of executed migrations
        $sql = "SELECT COUNT(*) as count FROM schema_migrations";
        $stmt = $this->connection->query($sql);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $count = $row['count'];
        $stmt->closeCursor();
        
        return $this->rollback($count);
    }
    
    /**
     * Get migration status
     *
     * @return array Associative array with 'executed' and 'pending' migrations
     */
    public function status(): array
    {
        $this->createSchemaMigrationsTable();
        
        $migrationFiles = $this->getMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        $executed = [];
        $pending = [];
        
        foreach ($migrationFiles as $filePath) {
            $migrationName = $this->getMigrationNameFromFile($filePath);
            
            if (in_array($migrationName, $executedMigrations)) {
                $executed[] = [
                    'migration' => $migrationName,
                    'file' => basename($filePath)
                ];
            } else {
                $pending[] = [
                    'migration' => $migrationName,
                    'file' => basename($filePath)
                ];
            }
        }
        
        return [
            'executed' => $executed,
            'pending' => $pending,
            'total_executed' => count($executed),
            'total_pending' => count($pending)
        ];
    }
    
    /**
     * Get the migrations directory path
     *
     * @return string
     */
    public function getMigrationsDir(): string
    {
        return $this->migrationsDir;
    }
}
