<?php
// MariaDB-Verbindung (pilzling_reviews_app auf Server Profis)
// Pattern uebernommen aus production-app/src/config/database.php (Pre-Check A1).

// Timezone setzen — Server läuft auf UTC, wir wollen aber deutsche lokale Zeit
date_default_timezone_set('Europe/Berlin');

function loadEnv(): void {
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        return;
    }
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Inline-Kommentar entfernen (wenn Value nicht in Quotes)
        if (!preg_match('/^["\']/', $value) && str_contains($value, '#')) {
            $value = trim(explode('#', $value, 2)[0]);
        }
        // Umschließende Quotes entfernen
        if (preg_match('/^(["\'])(.*)\1$/', $value, $m)) {
            $value = $m[2];
        }

        $_ENV[$key] = $value;
    }
}

function getDb(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    loadEnv();

    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $name = $_ENV['DB_NAME'] ?? 'pilzling_reviews_app';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASS'] ?? '';

    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
