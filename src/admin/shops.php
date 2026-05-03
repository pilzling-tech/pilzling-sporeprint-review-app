<?php
declare(strict_types=1);

// Sporeprint Admin — Shop-Switcher + Shop-Stammdaten-Edit.
// Aktion-Modi:
//   1. Liste aller Shops mit Kennzahlen
//   2. ?action=switch&shop=<id> → setzt Session-Active-Shop und redirected zu Dashboard
//   3. ?action=edit&shop=<id> → Edit-Form für Shop-Stammdaten
//   4. POST mit edit-Form → speichern + zurück

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

requireLogin();

$user = currentUser();
$activeShop = $_SESSION['active_shop'] ?? 'pilzling';

$action = $_GET['action'] ?? '';

// === Switch-Aktion ===
if ($action === 'switch') {
    $newShop = $_GET['shop'] ?? '';
    $exists = dbQueryOne("SELECT shop_id FROM shops WHERE shop_id = ?", [$newShop]);
    if ($exists) {
        $_SESSION['active_shop'] = $newShop;
    }
    header('Location: /dashboard.php');
    exit;
}

// === Edit-Modus ===
$editShopId = $_GET['shop'] ?? null;
$editShop = null;
$saved = isset($_GET['saved']);
$errorMsg = null;

if ($action === 'edit' && $editShopId) {
    $editShop = dbQueryOne("SELECT * FROM shops WHERE shop_id = ?", [$editShopId]);
    if (!$editShop) {
        $errorMsg = 'Shop nicht gefunden.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && $editShop) {
    requireCsrfToken();

    $name = trim($_POST['name'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $googlePlaceId = trim($_POST['google_place_id'] ?? '');
    $trustpilotUnitId = trim($_POST['trustpilot_unit_id'] ?? '');
    $ciPrimary = trim($_POST['ci_primary'] ?? '');
    $ciSecondary = trim($_POST['ci_secondary'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');

    // Hex-Validierung
    foreach (['ci_primary' => $ciPrimary, 'ci_secondary' => $ciSecondary] as $key => $val) {
        if ($val !== '' && !preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) {
            $errorMsg = ucfirst(str_replace('_', '-', $key)) . ': ungültiger Hex-Wert.';
            break;
        }
    }

    if (!$errorMsg) {
        if ($name === '' || $domain === '') {
            $errorMsg = 'Name und Domain sind Pflicht.';
        } else {
            dbExec(
                "UPDATE shops SET
                    name = ?, domain = ?,
                    google_place_id = ?, trustpilot_unit_id = ?,
                    ci_primary = ?, ci_secondary = ?,
                    contact_email = ?
                 WHERE shop_id = ?",
                [
                    $name, $domain,
                    $googlePlaceId !== '' ? $googlePlaceId : null,
                    $trustpilotUnitId !== '' ? $trustpilotUnitId : null,
                    $ciPrimary !== '' ? $ciPrimary : null,
                    $ciSecondary !== '' ? $ciSecondary : null,
                    $contactEmail !== '' ? $contactEmail : null,
                    $editShopId,
                ]
            );
            header('Location: /shops.php?action=edit&shop=' . urlencode($editShopId) . '&saved=1');
            exit;
        }
    }
}

// === Liste-Modus: alle Shops mit Kennzahlen ===
$allShops = dbQueryAll(
    "SELECT s.*,
        (SELECT COUNT(*) FROM reviews r WHERE r.shop_id = s.shop_id) AS review_count,
        (SELECT AVG(stars) FROM reviews r WHERE r.shop_id = s.shop_id AND r.visibility = 'visible') AS avg_stars
     FROM shops s
     ORDER BY s.shop_id"
);

$csrfToken = csrfToken();
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — Shops</title>
<link rel="stylesheet" href="/assets/admin.css">
<style>
.shop-card {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--space-4);
    box-shadow: var(--shadow-card);
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}
.shop-card.is-active {
    border-color: var(--color-accent-blau-dark);
    border-width: 2px;
}
.shop-card__head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.shop-card__name {
    font-size: var(--font-size-lg);
    font-weight: 600;
    color: var(--color-dark);
}
.shop-card__domain {
    font-size: var(--font-size-sm);
    color: var(--color-text-muted);
    font-family: var(--font-mono);
}
.shop-card__ci-swatches {
    display: flex;
    gap: var(--space-1);
}
.shop-card__ci-swatch {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 1px solid var(--color-border);
}
.shop-card__stats {
    display: flex;
    gap: var(--space-4);
    padding: var(--space-2) 0;
    border-top: 1px solid var(--color-border-soft);
    border-bottom: 1px solid var(--color-border-soft);
    font-size: var(--font-size-sm);
}
.shop-card__actions {
    display: flex;
    gap: var(--space-2);
    flex-wrap: wrap;
}
.shop-card__masked {
    font-family: var(--font-mono);
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
}
</style>
</head>
<body>

<header class="app-header">
    <a href="/dashboard.php" class="app-header__brand">Sporeprint</a>
    <nav class="app-header__nav">
        <a href="/dashboard.php">Dashboard</a>
        <a href="/reviews.php">Reviews</a>
        <a href="/replies.php">Antworten</a>
        <a href="/analytics.php">Analytics</a>
        <a href="/widget-config.php">Widget</a>
        <a href="/qr.php">QR &amp; Link</a>
        <a href="/shops.php" class="is-active">Shops</a>
    </nav>
    <div class="app-header__user">
        <span class="text-muted">Shop: <strong><?= htmlspecialchars($activeShop) ?></strong></span>
        <span><?= htmlspecialchars($user ?? '') ?></span>
        <a href="/logout.php">Logout</a>
    </div>
</header>

<main class="app-main">

<?php if ($action === 'edit' && $editShop): ?>

    <div class="page-header">
        <h1>Shop bearbeiten: <?= htmlspecialchars($editShop['name']) ?></h1>
        <div class="page-header__actions">
            <a href="/shops.php" class="btn-tertiary btn--sm">← Zurück zur Liste</a>
        </div>
    </div>

    <?php if ($saved): ?><div class="callout callout--success">Shop-Daten gespeichert.</div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="callout callout--error"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

    <section class="card">
        <header class="card__header"><h2>Stammdaten</h2></header>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="form-row">
                <label for="shop_id_view">Shop-ID (intern, nicht änderbar)</label>
                <input type="text" id="shop_id_view" value="<?= htmlspecialchars($editShop['shop_id']) ?>" disabled>
            </div>

            <div class="form-row">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($editShop['name']) ?>" required>
            </div>

            <div class="form-row">
                <label for="domain">Domain (Shop-URL ohne https://)</label>
                <input type="text" id="domain" name="domain" value="<?= htmlspecialchars($editShop['domain']) ?>" required>
                <p class="form-help">Wird für CORS und Referer-Check der Public-API genutzt. Beispiel: <code>pilzling.shop</code></p>
            </div>

            <hr>

            <h3>API-Identifier</h3>

            <div class="form-row">
                <label for="google_place_id">Google Place ID</label>
                <input type="text" id="google_place_id" name="google_place_id" value="<?= htmlspecialchars($editShop['google_place_id'] ?? '') ?>" placeholder="ChIJN1t_tDeu...">
                <p class="form-help">Google Business Profile Place-ID. Findbar via <a href="https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder" target="_blank" rel="noopener">Place-ID-Finder</a>.</p>
            </div>

            <div class="form-row">
                <label for="trustpilot_unit_id">Trustpilot Business Unit ID</label>
                <input type="text" id="trustpilot_unit_id" name="trustpilot_unit_id" value="<?= htmlspecialchars($editShop['trustpilot_unit_id'] ?? '') ?>" placeholder="46e7c9...">
                <p class="form-help">Abrufbar via <code>GET https://api.trustpilot.com/v1/business-units/find?name=<?= htmlspecialchars($editShop['domain']) ?></code> mit eurem API-Key.</p>
            </div>

            <hr>

            <h3>Corporate Identity</h3>

            <div class="form-row">
                <label for="ci_primary">CI Primary (Hex)</label>
                <input type="text" id="ci_primary" name="ci_primary" value="<?= htmlspecialchars($editShop['ci_primary'] ?? '') ?>" placeholder="#F85B05" pattern="^#[0-9a-fA-F]{3,8}$">
                <p class="form-help">Wird im Widget als Akzent-Farbe genutzt (Sporen-Rating, CTAs). Override via Widget-Konfigurator möglich.</p>
            </div>

            <div class="form-row">
                <label for="ci_secondary">CI Secondary (Hex)</label>
                <input type="text" id="ci_secondary" name="ci_secondary" value="<?= htmlspecialchars($editShop['ci_secondary'] ?? '') ?>" placeholder="#7a4f1a" pattern="^#[0-9a-fA-F]{3,8}$">
            </div>

            <hr>

            <h3>Kontakt</h3>

            <div class="form-row">
                <label for="contact_email">Default-Kontakt-E-Mail</label>
                <input type="email" id="contact_email" name="contact_email" value="<?= htmlspecialchars($editShop['contact_email'] ?? '') ?>" placeholder="kontakt@<?= htmlspecialchars($editShop['domain']) ?>">
                <p class="form-help">Wird als Default für Notifications + Reply-Forwarding genutzt. Weitere Empfänger via Settings → Benachrichtigungen.</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Speichern</button>
                <a href="/shops.php" class="btn-secondary">Abbrechen</a>
            </div>
        </form>
    </section>

<?php else: /* === Liste-Modus === */ ?>

    <div class="page-header">
        <h1>Shops</h1>
    </div>

    <p class="text-muted">Multi-Tenant-Übersicht. Aktiver Shop ist mit blauem Border markiert. Click auf "Auswählen" wechselt den aktiven Shop.</p>

    <div class="grid grid--2 mt-4">
        <?php foreach ($allShops as $s):
            $isActive = $s['shop_id'] === $activeShop;
            $reviewCount = (int) ($s['review_count'] ?? 0);
            $avgStars = $s['avg_stars'] !== null ? round((float)$s['avg_stars'], 1) : null;
        ?>
        <article class="shop-card <?= $isActive ? 'is-active' : '' ?>">
            <div class="shop-card__head">
                <div>
                    <div class="shop-card__name"><?= htmlspecialchars($s['name']) ?></div>
                    <div class="shop-card__domain"><?= htmlspecialchars($s['domain']) ?></div>
                </div>
                <div class="shop-card__ci-swatches">
                    <?php if (!empty($s['ci_primary'])): ?>
                        <div class="shop-card__ci-swatch" style="background: <?= htmlspecialchars($s['ci_primary']) ?>" title="Primary: <?= htmlspecialchars($s['ci_primary']) ?>"></div>
                    <?php endif; ?>
                    <?php if (!empty($s['ci_secondary'])): ?>
                        <div class="shop-card__ci-swatch" style="background: <?= htmlspecialchars($s['ci_secondary']) ?>" title="Secondary: <?= htmlspecialchars($s['ci_secondary']) ?>"></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="shop-card__stats">
                <div><strong><?= $reviewCount ?></strong> <span class="text-muted">Reviews</span></div>
                <div><strong><?= $avgStars !== null ? number_format($avgStars, 1, ',', '') . ' ★' : '–' ?></strong> <span class="text-muted">Ø</span></div>
            </div>

            <div>
                <div class="shop-card__masked">
                    Google: <?= !empty($s['google_place_id']) ? '✓ konfiguriert' : '✗ fehlt' ?> ·
                    Trustpilot: <?= !empty($s['trustpilot_unit_id']) ? '✓ konfiguriert' : '✗ fehlt' ?>
                </div>
            </div>

            <div class="shop-card__actions">
                <?php if (!$isActive): ?>
                    <a href="/shops.php?action=switch&shop=<?= urlencode($s['shop_id']) ?>" class="btn-primary btn--sm">Auswählen</a>
                <?php else: ?>
                    <span class="chip chip--green">Aktiv</span>
                <?php endif; ?>
                <a href="/shops.php?action=edit&shop=<?= urlencode($s['shop_id']) ?>" class="btn-secondary btn--sm">Bearbeiten</a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

</main>
</body>
</html>
