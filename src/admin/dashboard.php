<?php
declare(strict_types=1);

// Sporeprint Admin — Dashboard-Layout-Stub.
// Phase 1: nur Layout, keine Funktionen. Phase 3 füllt die Karten mit
// Reviews-Liste, Reply-Funktion, Analytics, Widget-Konfigurator, QR-Generator.

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/nav.php';

requireLogin();

$user = currentUser();

// Phase-1-Status für den Dashboard-Statusblock — informativ.
$shopCount = (int) (dbQueryOne("SELECT COUNT(*) AS n FROM shops")['n'] ?? 0);
$reviewCount = (int) (dbQueryOne("SELECT COUNT(*) AS n FROM reviews")['n'] ?? 0);
$lastSync = dbQueryOne(
    "SELECT shop_id, source, finished_at, status
     FROM sync_runs
     WHERE status = 'ok'
     ORDER BY started_at DESC
     LIMIT 1"
);
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — Dashboard</title>
<link rel="stylesheet" href="/assets/admin.css">
</head>
<body>

<?php renderAppHeader('dashboard'); ?>

<main class="app-main">
    <div class="page-header">
        <h1>Dashboard</h1>
    </div>

    <section class="status-block">
        <h2>System-Status</h2>
        <ul>
            <li class="status-ok">Backend-Foundation + Phase-3-Pages live</li>
            <li class="status-ok">Schema v1 + v2 eingespielt — <?= $shopCount ?> Shops, <?= $reviewCount ?> Reviews in DB</li>
            <li class="status-pending">Google Reviews-API wartet auf Freigabe</li>
            <li class="status-pending">Trustpilot Public-API wartet auf Freigabe</li>
            <?php if ($lastSync): ?>
                <li class="status-ok">
                    Letzter Sync: <?= htmlspecialchars($lastSync['shop_id']) ?> / <?= htmlspecialchars($lastSync['source']) ?>
                    <span class="text-muted">— <?= htmlspecialchars(formatDate($lastSync['finished_at'], true)) ?> (<?= htmlspecialchars(humanTimeDiff($lastSync['finished_at'])) ?>)</span>
                </li>
            <?php else: ?>
                <li class="status-pending">Noch kein erfolgreicher Sync — Cron-Skripte folgen sobald APIs freigegeben sind</li>
            <?php endif; ?>
        </ul>
    </section>

    <section class="section">
        <div class="subsection-header">
            <h2>Funktions-Bereiche</h2>
        </div>

        <div class="grid grid--2">
            <a href="/reviews.php" class="hub-tile">
                <h3>Reviews-Übersicht</h3>
                <p>Filter nach Plattform, Shop, Sternanzahl, Datum. Direkt aus der Liste antworten.</p>
            </a>
            <a href="/replies.php" class="hub-tile">
                <h3>Antworten verwalten</h3>
                <p>Reply auf Google / Trustpilot per API zurückpushen. Status-Tracking pro Antwort.</p>
            </a>
            <a href="/analytics.php" class="hub-tile">
                <h3>Analytics</h3>
                <p>Wachstumsgraph, Funnel, Durchschnitt pro Plattform und Shop.</p>
            </a>
            <a href="/widget-config.php" class="hub-tile">
                <h3>Widget-Konfigurator</h3>
                <p>Layout, Filter (min Sterne, max Items), Theme-Overrides, Embed-Code.</p>
            </a>
            <a href="/qr.php" class="hub-tile">
                <h3>QR-Code &amp; Bewertungslink</h3>
                <p>QR-Codes für Verpackung &amp; Marktstand. Bewertungs-Landing-Page-Konfigurator.</p>
            </a>
            <a href="/shops.php" class="hub-tile">
                <h3>Shop-Switcher</h3>
                <p>Pilzling / Pilzwald / Shroom Boom — alles in einer Oberfläche, Multi-Tenant.</p>
            </a>
            <a href="/settings.php" class="hub-tile">
                <h3>Einstellungen</h3>
                <p>Benachrichtigungs-E-Mails, API-Integrationen, Konto.</p>
            </a>
            <a href="/widget-test.php" class="hub-tile">
                <h3>Widget-Vorschau</h3>
                <p>Live-Test des Widgets mit Shop-Switcher — sieht aus wie im echten JTL-Shop.</p>
            </a>
        </div>
    </section>
</main>

</body>
</html>
