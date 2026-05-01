<?php

declare(strict_types=1);

namespace Database;

use mysqli;

class Migrator
{
    public function __construct(private readonly mysqli $conn)
    {
        $this->ensureMigrationsTable();
    }

    public function run(string $migrationsPath): void
    {
        $files = glob($migrationsPath . '/*.php');
        sort($files);

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if ($this->hasRun($name)) {
                echo "[SKIP] {$name}\n";
                continue;
            }

            require_once $file;

            $migration = $this->instantiate($name);
            $migration->up($this->conn);

            $this->record($name);
            echo "[OK]   {$name}\n";
        }
    }

    public function rollback(string $migrationsPath): void
    {
        $last  = $this->lastBatch();
        $files = array_reverse(glob($migrationsPath . '/*.php') ?: []);

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if (!in_array($name, $last, true)) {
                continue;
            }

            require_once $file;

            $migration = $this->instantiate($name);
            $migration->down($this->conn);

            $this->remove($name);
            echo "[ROLLED BACK] {$name}\n";
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function instantiate(string $name): object
    {
        $parts     = explode('_', $name, 2);
        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $parts[1] ?? $name)));

        if (!class_exists($className)) {
            throw new \RuntimeException("Migration class [{$className}] not found.");
        }

        return new $className();
    }

    private function ensureMigrationsTable(): void
    {
        $this->conn->query(
            'CREATE TABLE IF NOT EXISTS migrations (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                migration  VARCHAR(255) NOT NULL UNIQUE,
                batch      INT NOT NULL DEFAULT 1,
                run_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )'
        );
    }

    private function hasRun(string $name): bool
    {
        $stmt = $this->conn->prepare('SELECT id FROM migrations WHERE migration = ?');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    private function record(string $name): void
    {
        $batch = $this->currentBatch();
        $stmt  = $this->conn->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)');
        $stmt->bind_param('si', $name, $batch);
        $stmt->execute();
    }

    private function remove(string $name): void
    {
        $stmt = $this->conn->prepare('DELETE FROM migrations WHERE migration = ?');
        $stmt->bind_param('s', $name);
        $stmt->execute();
    }

    private function currentBatch(): int
    {
        $result = $this->conn->query('SELECT COALESCE(MAX(batch), 0) AS b FROM migrations');
        return (int) $result->fetch_assoc()['b'] + 1;
    }

    private function lastBatch(): array
    {
        $result = $this->conn->query(
            'SELECT migration FROM migrations
             WHERE batch = (SELECT MAX(batch) FROM migrations)'
        );
        return array_column($result->fetch_all(MYSQLI_ASSOC), 'migration');
    }
}
