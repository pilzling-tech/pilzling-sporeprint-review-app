<?php
declare(strict_types=1);

// Sporeprint Admin — Widget-Test-Seite (intern).
// Erlaubt Live-Test des Widgets ohne JTL-Shop-Embedding.
// Hinter cPanel-Verzeichnisschutz + App-Login — kein externer Zugriff.
//
// Verschoben aus public/widget-test.html (Phase 1.9.4) damit das Widget-
// Asset-Hosting (sporeprint.pilzling.eu) sauber bleibt und die Test-Seite
// nicht oeffentlich indizierbar ist. Cross-Subdomain ok: Widget liefert
// via CORS aus sporeprint.pilzling.eu, Test-Seite laedt es ein.

require_once __DIR__ . '/../lib/auth.php';
requireLogin();

$user = currentUser();
$availableShops = ['pilzling', 'pilzwald', 'shroom-boom'];
$selectedShop = $_GET['shop'] ?? 'pilzling';
if (!in_array($selectedShop, $availableShops, true)) {
    $selectedShop = 'pilzling';
}
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — Widget-Test</title>
<link rel="stylesheet" href="/assets/admin.css">
</head>
<body>

<header class="app-header">
    <a href="/dashboard.php" class="app-header__brand">Sporeprint</a>
    <nav class="app-header__nav">
        <a href="/dashboard.php">Dashboard</a>
        <a href="/widget-test.php" class="is-active">Widget-Vorschau</a>
    </nav>
    <div class="app-header__user">
        <span><?= htmlspecialchars($user ?? '') ?></span>
        <a href="/logout.php">Logout</a>
    </div>
</header>

<main class="app-main">
    <div class="page-header">
        <h1>Widget-Vorschau</h1>
    </div>

    <div class="callout callout--info">
        <strong>Hinweis:</strong> Diese Seite ist nicht im JTL-Shop eingebettet — Referer-Check und CORS greifen nicht aktiv.
        Phase 1 nutzt hardcoded Mock-Reviews aus dem Widget. Nach Phase 2 (API-Anbindung) zieht das Widget echte Daten von <code>/api/reviews?shop=…</code>.
    </div>

    <section class="section">
        <div class="subsection-header">
            <h2>Shop wählen</h2>
        </div>

        <div class="form-actions">
            <?php foreach ($availableShops as $shopId): ?>
                <a href="?shop=<?= urlencode($shopId) ?>"
                   class="<?= $selectedShop === $shopId ? 'btn-primary' : 'btn-secondary' ?> btn--sm">
                    <?= htmlspecialchars(ucfirst(str_replace('-', ' ', $shopId))) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section">
        <div class="subsection-header">
            <h2>Live-Vorschau</h2>
        </div>

        <!-- Widget-Mount-Point + Loader -->
        <div id="sporeprint-widget"></div>
        <script src="https://sporeprint.pilzling.eu/widget.js"
                data-shop="<?= htmlspecialchars($selectedShop) ?>"></script>
    </section>
</main>

</body>
</html>
