<?php
declare(strict_types=1);

// Sporeprint Admin — Dashboard-Layout-Stub.
// Phase 1: nur Layout, keine Funktionen. Phase 3 füllt die Karten mit
// Reviews-Liste, Reply-Funktion, Analytics, Widget-Konfigurator, QR-Generator.

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

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

<header class="app-header">
    <a href="/dashboard.php" class="app-header__brand">Sporeprint</a>
    <nav class="app-header__nav">
        <a href="/dashboard.php" class="is-active">Dashboard</a>
    </nav>
    <div class="app-header__user">
        <span><?= htmlspecialchars($user ?? '') ?></span>
        <a href="/logout.php">Logout</a>
    </div>
</header>

<main class="app-main">
    <div class="page-header">
        <h1>Dashboard</h1>
    </div>

    <section class="status-block">
        <h2>Phase 1 — Foundation</h2>
        <ul>
            <li class="status-ok">Backend-Helper (lib/) live</li>
            <li class="status-ok">Public-API-Endpoint <code>/api/reviews</code> mit Härtung aktiv</li>
            <li class="status-ok">Admin-Login + Session</li>
            <li class="status-ok">Schema v1 eingespielt — <?= $shopCount ?> Shops konfiguriert, <?= $reviewCount ?> Reviews in DB</li>
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
            <article class="card card--stub">
                <h3>Reviews-Übersicht</h3>
                <p class="text-muted">Filter nach Plattform, Shop, Sternanzahl, Datum. Direkt aus der Liste antworten.</p>
            </article>
            <article class="card card--stub">
                <h3>Antworten verwalten</h3>
                <p class="text-muted">Reply auf Google / Trustpilot per API zurückpushen. Status-Tracking pro Antwort.</p>
            </article>
            <article class="card card--stub">
                <h3>Analytics</h3>
                <p class="text-muted">Wachstumsgraph, Funnel, Durchschnitt pro Plattform und Shop.</p>
            </article>
            <article class="card card--stub">
                <h3>Widget-Konfigurator</h3>
                <p class="text-muted">Layout, Filter (min Sterne, max Items), Custom-CSS pro Shop.</p>
            </article>
            <article class="card card--stub">
                <h3>QR-Code-Generator</h3>
                <p class="text-muted">QR-Codes für Verpackung, Marktstand, etc. — führen direkt zur Bewertungsseite.</p>
            </article>
            <article class="card card--stub">
                <h3>Shop-Switcher</h3>
                <p class="text-muted">Pilzling / Pilzwald / Shroom Boom — alles in einer Oberfläche, Multi-Tenant.</p>
            </article>
        </div>
    </section>
</main>

</body>
</html>
