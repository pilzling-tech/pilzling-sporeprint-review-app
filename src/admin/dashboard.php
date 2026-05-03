<?php
declare(strict_types=1);

// Sporeprint Admin — Dashboard-Layout-Stub.
// Phase 1: nur Layout, keine Funktionen. Phase 3 fuellt die Karten mit
// Reviews-Liste, Reply-Funktion, Analytics, Widget-Konfigurator, QR-Generator.

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';

requireLogin();

$user = currentUser();

// Phase-1-Status fuer den Dashboard-Statusblock — informativ, was schon laeuft.
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
<header class="app-header">
    <div class="app-header__brand">Sporeprint</div>
    <div class="app-header__user">
        <span><?= htmlspecialchars($user ?? '') ?></span>
        <a href="/logout.php">Logout</a>
    </div>
</header>

<main class="app-main">
    <h1>Dashboard</h1>

    <div class="status-block">
        <h2>Phase 1 — Foundation</h2>
        <ul>
            <li class="status-ok">Backend-Helper (lib/) live</li>
            <li class="status-ok">Public-API-Endpoint /api/reviews mit Haertung aktiv</li>
            <li class="status-ok">Admin-Login + Session</li>
            <li class="status-ok">Schema v1 eingespielt — <?= $shopCount ?> Shops konfiguriert, <?= $reviewCount ?> Reviews in DB</li>
            <li class="status-pending">Google Reviews-API wartet auf Freigabe</li>
            <li class="status-pending">Trustpilot Public-API wartet auf Freigabe</li>
            <?php if ($lastSync): ?>
                <li class="status-ok">Letzter Sync ok: <?= htmlspecialchars($lastSync['shop_id']) ?> / <?= htmlspecialchars($lastSync['source']) ?> um <?= htmlspecialchars($lastSync['finished_at'] ?? '?') ?></li>
            <?php else: ?>
                <li class="status-pending">Noch kein erfolgreicher Sync — Cron-Skripte folgen sobald APIs freigegeben sind</li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="card-grid">
        <article class="card is-stub">
            <h2>Reviews-Uebersicht</h2>
            <p>Filter nach Plattform, Shop, Sternanzahl, Datum. Direkt aus der Liste antworten.</p>
        </article>
        <article class="card is-stub">
            <h2>Antworten verwalten</h2>
            <p>Reply auf Google / Trustpilot per API zurueckpushen. Status-Tracking pro Antwort.</p>
        </article>
        <article class="card is-stub">
            <h2>Analytics</h2>
            <p>Wachstumsgraph, Funnel, Durchschnitt pro Plattform und Shop.</p>
        </article>
        <article class="card is-stub">
            <h2>Widget-Konfigurator</h2>
            <p>Layout, Filter (min Sterne, max Items), Custom-CSS pro Shop.</p>
        </article>
        <article class="card is-stub">
            <h2>QR-Code-Generator</h2>
            <p>QR-Codes fuer Verpackung, Marktstand, etc. — fuehren direkt zur Bewertungsseite.</p>
        </article>
        <article class="card is-stub">
            <h2>Shop-Switcher</h2>
            <p>Pilzling / Pilzwald / Shroom Boom — alles in einer Oberflaeche, Multi-Tenant.</p>
        </article>
    </div>
</main>
</body>
</html>
