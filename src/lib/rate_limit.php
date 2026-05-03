<?php
declare(strict_types=1);

// Sporeprint — Sliding-Window-Rate-Limiter (SSOT).
//
// Pattern: Sporeprint-Erstwerk. Wandert nach Phase 1 als Pre-Check D7 nach
// references/php-patterns/public-api-hardening/ als Workspace-SSOT.
//
// Implementierung: pro Minute eine Zeile pro IP in rate_limits-Tabelle.
// checkRateLimit() inkrementiert den Counter für aktuelle Minute und
// summiert die letzten N Minuten.
// Cleanup: _tools/cron-cleanup-rate-limits.php purged Buckets älter als 1h.

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Prüft Rate-Limit für eine IP. Gibt true zurück wenn die Anfrage
 * im erlaubten Limit liegt, false wenn überschritten.
 *
 * Inkrementiert immer (auch bei Überschreitung) — sonst könnte ein
 * Angreifer mit konstantem Polling den Counter "festhalten".
 *
 * @param string $ipBinary  IP als 16-Byte-Binary (siehe binaryIp())
 * @param int $limitPerMinute  Max. Requests pro Zeitfenster
 * @param int $windowMinutes  Fenstergröße in Minuten (1 = pure aktuelle-Minute)
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

    // Summe über das gesamte Fenster
    $row = dbQueryOne(
        "SELECT COALESCE(SUM(request_count), 0) AS total
         FROM rate_limits
         WHERE ip_address = ? AND bucket_minute >= ?",
        [$ipBinary, $windowStart]
    );

    $total = (int) ($row['total'] ?? 0);
    return $total <= $limitPerMinute;
}
