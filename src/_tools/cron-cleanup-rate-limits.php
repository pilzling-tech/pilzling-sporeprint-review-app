<?php
declare(strict_types=1);

// Sporeprint Cron-Skript: Alte rate_limits-Eintraege purgen.
//
// Aufruf: php /home/pilzling/app.reviews/_tools/cron-cleanup-rate-limits.php
// Frequenz: alle 15 Min (cPanel-Cronjob)
// Cron-Eintrag: */15 * * * * php /home/pilzling/app.reviews/_tools/cron-cleanup-rate-limits.php
//
// Loescht Buckets aelter als 60 Minuten (Sliding-Window-Retention).
// Datenminimierung (DSGVO Art. 6 Abs. 1 lit. f — IP nur fuer Rate-Limit-Window).
//
// Sicherheits-Check: Skript darf nur via CLI laufen, niemals ueber HTTP.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script can only be run from the command line.\n";
    exit(1);
}

require_once __DIR__ . '/../lib/db.php';

$cutoffBucket = (int) floor(time() / 60) - 60; // 60 Minuten ago

$deleted = dbExec(
    "DELETE FROM rate_limits WHERE bucket_minute < ?",
    [$cutoffBucket]
);

$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] cron-cleanup-rate-limits: deleted {$deleted} entries (cutoff bucket {$cutoffBucket})\n";
