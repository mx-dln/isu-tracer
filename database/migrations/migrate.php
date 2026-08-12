<?php

declare(strict_types=1);

/**
 * CLI migration runner.
 *
 * Usage:
 *   php database/migrations/migrate.php
 *
 * Creates the database (if missing) and executes every *.sql file in
 * database/migrations in filename order.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$basePath = dirname(__DIR__, 2);
require $basePath . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($basePath);
$dotenv->safeLoad();

$host = env('DB_HOST', 'localhost');
$port = (int) env('DB_PORT', 3306);
$db   = env('DB_DATABASE', 'tracer');
$user = env('DB_USERNAME', 'root');
$pass = env('DB_PASSWORD', '');

echo "=== ISU IAT Tracer - Migration Runner ===\n";
echo "Target: {$host}:{$port}/{$db}\n\n";

try {
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[OK] Database '{$db}' ensured.\n";
    $pdo->exec("USE `{$db}`");

    $migrationsDir = __DIR__;
    $files = glob($migrationsDir . '/*.sql');
    sort($files);

    // Track applied migrations.
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        file VARCHAR(255) NOT NULL UNIQUE,
        applied_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $applied = $pdo->query('SELECT file FROM migrations')->fetchAll(PDO::FETCH_COLUMN);

    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $applied, true)) {
            echo "[SKIP] {$name} (already applied)\n";
            continue;
        }

        $sql = file_get_contents($file);
        // Split on statement-terminating semicolons followed by a newline.
        $statements = preg_split('/;\s*\R/', trim($sql));

        try {
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if ($statement === '' || str_starts_with($statement, '--')) {
                    continue;
                }
                $pdo->exec($statement);
            }
            $pdo->exec("INSERT INTO migrations (`file`) VALUES ('{$name}')");
            echo "[OK] {$name}\n";
        } catch (Throwable $e) {
            echo "[FAIL] {$name}: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    echo "\nMigration complete.\n";
} catch (Throwable $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
