<?php
declare(strict_types=1);

// Sporeprint Admin — Widget-Konfigurator.
// Pro Shop einstellbar: Filter (min_stars, max_items, show_product_reviews),
// Layout (carousel/feed), Custom-CSS, Theme-Overrides (CSS-Variablen).
// Live-Preview lädt das Widget mit den aktuellen Settings (über
// einen Iframe-Ansatz — das Widget kennt theme_overrides via Mock-Override).

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/nav.php';

requireLogin();

$user = currentUser();
$activeShop = $_SESSION['active_shop'] ?? 'pilzling';

$config = dbQueryOne(
    "SELECT wc.*, s.name AS shop_name, s.ci_primary, s.ci_secondary
     FROM widget_configs wc
     JOIN shops s ON s.shop_id = wc.shop_id
     WHERE wc.shop_id = ?",
    [$activeShop]
);

$saved = isset($_GET['saved']);
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $layout = $_POST['layout'] ?? 'carousel';
    if (!in_array($layout, ['carousel', 'feed'], true)) $layout = 'carousel';
    $minStars = (int) ($_POST['min_stars'] ?? 4);
    if ($minStars < 1) $minStars = 1; if ($minStars > 5) $minStars = 5;
    $maxItems = (int) ($_POST['max_items'] ?? 20);
    if ($maxItems < 1) $maxItems = 1; if ($maxItems > 100) $maxItems = 100;
    $showProductReviews = isset($_POST['show_product_reviews']) ? 1 : 0;
    $customCss = trim($_POST['custom_css'] ?? '');

    // === Theme-Overrides bauen (nur nicht-leere Werte) ===
    $overrideKeys = ['accent', 'accent_soft', 'background', 'card_background', 'rating_filled'];
    $overrides = [];
    foreach ($overrideKeys as $key) {
        $val = trim($_POST['override_' . $key] ?? '');
        if ($val !== '' && preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) {
            $overrides[$key] = $val;
        }
    }
    $themeOverridesJson = empty($overrides) ? null : json_encode($overrides);

    dbExec(
        "UPDATE widget_configs
         SET layout = ?, min_stars = ?, max_items = ?, show_product_reviews = ?,
             custom_css = ?, theme_overrides = ?
         WHERE shop_id = ?",
        [$layout, $minStars, $maxItems, $showProductReviews,
         $customCss !== '' ? $customCss : null,
         $themeOverridesJson, $activeShop]
    );

    header('Location: /widget-config.php?saved=1');
    exit;
}

// Theme-Overrides parsen
$existingOverrides = [];
if ($config && !empty($config['theme_overrides'])) {
    $decoded = json_decode($config['theme_overrides'], true);
    if (is_array($decoded)) $existingOverrides = $decoded;
}

$csrfToken = csrfToken();
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — Widget-Konfigurator</title>
<link rel="stylesheet" href="/assets/admin.css">
<style>
.widget-config-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-5);
    align-items: flex-start;
}
@media (max-width: 1100px) {
    .widget-config-grid { grid-template-columns: 1fr; }
}
.widget-config-form {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--space-5);
    box-shadow: var(--shadow-card);
}
.widget-preview {
    position: sticky;
    top: var(--space-4);
}
.widget-preview iframe {
    width: 100%;
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow-card);
    min-height: 600px;
    background: #1a1f2e;
}
.color-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: var(--space-2);
    align-items: center;
}
.color-row input[type="color"] {
    width: 50px;
    height: 38px;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    cursor: pointer;
    padding: 2px;
    background: var(--color-white);
}
.color-row__reset {
    background: none;
    border: none;
    color: var(--color-text-muted);
    cursor: pointer;
    font-size: var(--font-size-xs);
    padding: 0.25rem 0.5rem;
}
.color-row__reset:hover { color: var(--color-primary); }
.checkbox-row {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) 0;
}
.checkbox-row input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}
.embed-code {
    background: var(--color-cream-dark);
    padding: var(--space-3);
    border-radius: var(--radius);
    font-family: var(--font-mono);
    font-size: var(--font-size-xs);
    word-break: break-all;
    margin-top: var(--space-3);
}
</style>
</head>
<body>

<?php renderAppHeader('widget-config'); ?>

<main class="app-main">
    <div class="page-header">
        <h1>Widget-Konfigurator</h1>
        <div class="page-header__actions">
            <a href="/widget-test.php" class="btn-tertiary btn--sm">Vollbild-Vorschau</a>
        </div>
    </div>

    <?php if ($saved): ?>
        <div class="callout callout--success">Widget-Konfiguration gespeichert.</div>
    <?php endif; ?>

    <?php if (!$config): ?>
        <div class="callout callout--error">Keine Widget-Config für Shop "<?= htmlspecialchars($activeShop) ?>" gefunden. Bitte Schema v1 einspielen.</div>
    <?php else: ?>

    <div class="widget-config-grid">

        <!-- ===== Konfig-Form links ===== -->
        <form method="post" class="widget-config-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <h2>Allgemein</h2>

            <div class="form-row">
                <label for="layout">Layout</label>
                <select id="layout" name="layout">
                    <option value="carousel" <?= $config['layout'] === 'carousel' ? 'selected' : '' ?>>Karussell (horizontal)</option>
                    <option value="feed" <?= $config['layout'] === 'feed' ? 'selected' : '' ?>>Feed (vertikal)</option>
                </select>
                <p class="form-help">Karussell ist das Standard-Layout für Pilzling-Shops. Feed eignet sich für längere Listen auf einer dedizierten Reviews-Page.</p>
            </div>

            <div class="form-row">
                <label for="min_stars">Mindest-Sterne</label>
                <select id="min_stars" name="min_stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= (int)$config['min_stars'] === $i ? 'selected' : '' ?>><?= $i ?> ★ und höher</option>
                    <?php endfor; ?>
                </select>
                <p class="form-help">Nur Reviews mit mindestens dieser Bewertung werden angezeigt. Default: 4 ★.</p>
            </div>

            <div class="form-row">
                <label for="max_items">Maximale Anzahl im Widget</label>
                <input type="number" id="max_items" name="max_items" min="1" max="100" value="<?= (int)$config['max_items'] ?>">
                <p class="form-help">Wie viele Reviews maximal im Widget angezeigt werden. Sortiert nach Datum (neueste zuerst).</p>
            </div>

            <div class="checkbox-row">
                <input type="checkbox" id="show_product_reviews" name="show_product_reviews" value="1" <?= (int)$config['show_product_reviews'] === 1 ? 'checked' : '' ?>>
                <label for="show_product_reviews" style="margin: 0;">Produktbewertungen (JTL) im Widget zeigen</label>
            </div>

            <hr>

            <h2>Theme-Overrides</h2>
            <?php $ciPrimaryDisplay = $config['ci_primary'] ?: '#F85B05'; ?>
            <p class="text-muted text-sm mb-4">
                Pro Shop eigene Akzent-Farbe — überschreibt das automatische Mapping aus
                <code>shops.ci_primary</code>.
                Default-CI: <span style="display:inline-block;vertical-align:middle;width:14px;height:14px;border-radius:3px;border:1px solid var(--color-border);background:<?= htmlspecialchars($ciPrimaryDisplay) ?>"></span>
                <code><?= htmlspecialchars($ciPrimaryDisplay) ?></code>.
                Leer lassen = automatisches Mapping aus CI.
            </p>

            <?php foreach ([
                ['accent', 'Akzent-Farbe (Sporen, CTAs, Links)'],
                ['accent_soft', 'Akzent-Soft (Hintergrund-Tints)'],
                ['background', 'Background des Widgets'],
                ['card_background', 'Card-Background'],
            ] as [$key, $label]): ?>
                <div class="form-row">
                    <label for="override_<?= $key ?>"><?= htmlspecialchars($label) ?></label>
                    <div class="color-row">
                        <input type="color" id="override_<?= $key ?>" name="override_<?= $key ?>"
                               value="<?= htmlspecialchars($existingOverrides[$key] ?? '#000000') ?>">
                        <button type="button" class="color-row__reset" onclick="document.getElementById('override_<?= $key ?>').value=''; this.previousElementSibling.value=''">Leer</button>
                    </div>
                </div>
            <?php endforeach; ?>

            <hr>

            <h2>Custom CSS</h2>
            <div class="form-row">
                <label for="custom_css">Eigenes CSS für dieses Widget</label>
                <textarea id="custom_css" name="custom_css" rows="6" placeholder=".sporeprint-widget { /* dein CSS hier */ }"><?= htmlspecialchars($config['custom_css'] ?? '') ?></textarea>
                <p class="form-help">Wird als &lt;style&gt;-Block ins Widget injected. Nur für Power-User — eigene Regeln können das Standard-Styling überschreiben.</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Speichern</button>
                <a href="/widget-config.php" class="btn-secondary">Verwerfen</a>
            </div>

            <hr>

            <h2>Embed-Code</h2>
            <p class="text-muted text-sm">Kopier diesen Code ins JTL-Shop-Template (z.B. unter dem Add-to-Cart-Bereich).</p>
            <code class="embed-code" id="embed-code">&lt;script src="https://sporeprint.pilzling.eu/widget.js" data-shop="<?= htmlspecialchars($activeShop) ?>"&gt;&lt;/script&gt;</code>
            <div class="form-actions">
                <button type="button" class="btn-secondary btn--sm" onclick="navigator.clipboard.writeText(document.getElementById('embed-code').textContent); this.textContent='Kopiert!'; setTimeout(()=>this.textContent='In Zwischenablage kopieren', 2000)">In Zwischenablage kopieren</button>
            </div>
        </form>

        <!-- ===== Live-Preview rechts ===== -->
        <div class="widget-preview">
            <h2>Live-Vorschau</h2>
            <p class="text-muted text-sm">Ohne Speichern reload-bare Vorschau via Widget-Test-Iframe.</p>
            <iframe src="/widget-test.php?embed=1&shop=<?= htmlspecialchars($activeShop) ?>"
                    title="Widget-Vorschau"></iframe>
            <p class="text-muted text-xs mt-2">Vorschau zeigt aktuell gespeicherten Stand. Änderungen werden nach "Speichern" sichtbar.</p>
        </div>

    </div>

    <?php endif; ?>
</main>

</body>
</html>
