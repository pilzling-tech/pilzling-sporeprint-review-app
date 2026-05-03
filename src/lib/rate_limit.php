<?php
declare(strict_types=1);

// Sporeprint — Sliding-Window-Rate-Limiter (SSOT).
//
// Pattern: Sporeprint-Erstwerk. Wandert nach Phase 1 als Pre-Check D7 nach
// references/php-patterns/public-api-hardening/ als Workspace-SSOT.
//
// Implementierung: pro Minute eine Zeile pro IP in rate_limits-Tabelle.
// checkRateLimit() inkrementiert den Counter fuer aktuelle Minute und
// summiert die letzten N Minuten.
// Cleanup: _tools/cron-cleanup-rate-limits.php purged Buckets aelter als 1h.

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Prueft Rate-Limit fuer eine IP. Gibt true zurueck wenn die Anfrage
 * im erlaubten Limit liegt, false wenn ueberschritten.
 *
 * Inkrementiert immer (auch bei Ueberschreitung) — sonst koennte ein
 * Angreifer mit konstantem Polling den Counter "festhalten".
 *
 * @param string $ipBinary  IP als 16-Byte-Binary (siehe binaryIp())
 * @param int $limitPerMinute  Max. Requests pro Zeitfenster
 * @param int $windowMinutes  Fenstergroesse in Minuten (1 = pure Aktuelle-Minute)
 */
function checkRateLimit(string $ipBinary, int $limitPerMinute = 60, int $windowMinutes = 1): bool
{
    $currentBucket = (int) floor(time() / 60);
    $windowStart = $currentBucket - $windowMinutes + 1;

    // Inkrementiere aktuellen Bucket (oder lege ihn an)
    dbExec(
        "INSERT INTO rate_limits (ip_address, bucket_minute, request_count)
         VALUES (?, ?, 1)
         ON DUPLICATE KEY UPDATE request_count = request_count + 1",
        [$ipBinary, $currentBucket]
    );

    // Summe ueber das gesamte Fenster
    $row = dbQueryOne(
        "SELECT COALESCE(SUM(request_count), 0) AS total
         FROM rate_limits
         WHERE ip_address = ? AND bucket_minute >= ?",
        [$ipBinary, $windowStart]
    );

    $total = (int) ($row['total'] ?? 0);
    return $total <= $limitPerMinute;
}
