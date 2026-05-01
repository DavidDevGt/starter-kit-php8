<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$conn = new mysqli(
    $_ENV['DB_HOST'],
    $_ENV['DB_USER'],
    $_ENV['DB_PASS'],
    $_ENV['DB_NAME'],
);

if ($conn->connect_error) {
    echo "Connection failed: {$conn->connect_error}\n";
    exit(1);
}

$migrator  = new Database\Migrator($conn);
$command   = $argv[1] ?? 'run';
$path      = __DIR__ . '/migrations';

match ($command) {
    'run'      => $migrator->run($path),
    'rollback' => $migrator->rollback($path),
    default    => print("Unknown command. Use: run | rollback\n"),
};

$conn->close();
