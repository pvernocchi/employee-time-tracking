<?php

namespace App\Core;

use PDO;

class MigrationManager
{
    private string $migrationsPath;

    public function __construct(string $migrationsPath)
    {
        $this->migrationsPath = rtrim($migrationsPath, '/');
    }

    public function getAvailableMigrations(): array
    {
        $files = glob($this->migrationsPath . '/*.php') ?: [];
        sort($files, SORT_STRING);

        $migrations = [];

        foreach ($files as $file) {
            $migration = require $file;
            if (!is_array($migration) || empty($migration['version']) || empty($migration['name']) || empty($migration['statements'])) {
                throw new \RuntimeException('Invalid migration definition: ' . basename($file));
            }

            $migrations[] = [
                'version' => (string) $migration['version'],
                'name' => (string) $migration['name'],
                'statements' => $migration['statements'],
            ];
        }

        return $migrations;
    }

    public function getLatestVersion(): ?string
    {
        $migrations = $this->getAvailableMigrations();
        if ($migrations === []) {
            return null;
        }

        return end($migrations)['version'];
    }

    public function getPendingMigrations(PDO $pdo): array
    {
        $migrations = $this->getAvailableMigrations();

        if (!$this->hasTable($pdo, 'schema_migrations')) {
            return $migrations;
        }

        $applied = $this->getAppliedVersions($pdo);

        return array_values(array_filter(
            $migrations,
            static fn(array $migration): bool => !in_array($migration['version'], $applied, true)
        ));
    }

    public function applyPending(PDO $pdo): array
    {
        $applied = [];

        foreach ($this->getPendingMigrations($pdo) as $migration) {
            foreach ($migration['statements'] as $statement) {
                $pdo->exec($statement);
            }

            $this->recordMigration($pdo, $migration);
            $applied[] = $migration;
        }

        return $applied;
    }

    private function getAppliedVersions(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT version FROM schema_migrations ORDER BY version ASC');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

        return array_map('strval', $rows ?: []);
    }

    private function recordMigration(PDO $pdo, array $migration): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO schema_migrations (version, name) VALUES (:version, :name)
             ON DUPLICATE KEY UPDATE name = VALUES(name), applied_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            ':version' => $migration['version'],
            ':name' => $migration['name'],
        ]);

        if ($this->hasTable($pdo, 'app_meta')) {
            $metaStmt = $pdo->prepare(
                'INSERT INTO app_meta (meta_key, meta_value) VALUES (:meta_key, :meta_value)
                 ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = CURRENT_TIMESTAMP'
            );
            $metaStmt->execute([
                ':meta_key' => 'database_version',
                ':meta_value' => $migration['version'],
            ]);
        }
    }

    private function hasTable(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table'
        );
        $stmt->execute([':table' => $table]);

        return (bool) $stmt->fetchColumn();
    }
}
