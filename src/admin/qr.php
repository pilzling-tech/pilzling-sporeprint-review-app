<?php
declare(strict_types=1);

// Sporeprint Admin — QR-Code-Generator + Bewertungslink-Konfigurator.
// Pro Shop:
//   - feedback_url_slug + feedback_landing_title + feedback_landing_text
//     pflegen (gesteuert in Phase J Public-Landing-Page)
//   - QR-Code für die fertige URL anzeigen + downloaden
//
// QR-Generation: Server-side via simple Inline-PHP (oder QuickChart-API
// als Fallback wenn keine PHP-QR-Lib verfügbar). Wir nutzen die
// QuickChart.io-API als pragmatischer Default — kein DB-Hit, kein
// Composer-Install, kein Maintainable-PHP-Package nötig.

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/nav.php';

requireLogin();

$user = currentUser();
$activeShop = $_SESSION['active_shop'] ?? 'pilzling';

$shop = dbQueryOne(
    "SELECT * FROM shops WHERE shop_id = ?",
    [$activeShop]
);

$saved = isset($_GET['saved']);
$errorMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $slug = trim($_POST['feedback_url_slug'] ?? '');
    $title = trim($_POST['feedback_landing_title'] ?? '');
    $text = trim($_POST['feedback_landing_text'] ?? '');

    if ($slug === '' || !preg_match('/^[a-z0-9\-]{2,64}$/', $slug)) {
        $errorMsg = 'Slug muss 2-64 Zeichen haben (a-z, 0-9, Bindestrich).';
    } else {
        // Doppel-Check ob Slug bereits von anderem Shop genutzt wird
        $clash = dbQueryOne(
            "SELECT shop_id FROM shops WHERE feedback_url_slug = ? AND shop_id != ?",
            [$slug, $activeShop]
        );
        if ($clash) {
            $errorMsg = 'Slug "' . htmlspecialchars($slug) . '" ist schon vergeben (Shop: ' . htmlspecialchars($clash['shop_id']) . ').';
        } else {
            dbExec(
                "UPDATE shops SET
                    feedback_url_slug = ?,
                    feedback_landing_title = ?,
                    feedback_landing_text = ?
                 WHERE shop_id = ?",
                [$slug, $title !== '' ? $title : null, $text !== '' ? $text : null, $activeShop]
            );
            header('Location: /qr.php?saved=1');
            exit;
        }
    }
}

// Frische Daten nach POST
$shop = dbQueryOne("SELECT * FROM shops WHERE shop_id = ?", [$activeShop]);
$feedbackSlug = $shop['feedback_url_slug'] ?? $activeShop;
$feedbackUrl = 'https://sporeprint.pilzling.eu/feedback?shop=' . urlencode($feedbackSlug);

// QR-Code-URL über QuickChart.io (kostenloser Service, keine Cookies, kein API-Key)
$qrSize = 320;
$qrUrl = 'https://quickchart.io/qr?text=' . urlencode($feedbackUrl) . '&size=' . $qrSize . '&margin=2';
$qrUrlDownload = 'https://quickchart.io/qr?text=' . urlencode($feedbackUrl) . '&size=600&margin=4&format=png';

$csrfToken = csrfToken();
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — QR-Code & Bewertungslink</title>
<link rel="stylesheet" href="/assets/admin.css">
<style>
.qr-grid {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: var(--space-5);
    align-items: flex-start;
}
@media (max-width: 900px) {
    .qr-grid { grid-template-columns: 1fr; }
}
.qr-card {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--space-5);
    box-shadow: var(--shadow-card);
    text-align: center;
}
.qr-card img {
    width: 320px;
    max-width: 100%;
    height: auto;
    border-radius: var(--radius-sm);
    margin: var(--space-3) auto;
    display: block;
    background: var(--color-white);
    padding: var(--space-2);
}
.feedback-url-box {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    background: var(--color-cream);
    padding: var(--space-3);
    border-radius: var(--radius-sm);
    margin: var(--space-3) 0;
    word-break: break-all;
    font-family: var(--font-mono);
    font-size: var(--font-size-sm);
}
.feedback-url-box code {
    flex: 1;
    background: transparent;
    padding: 0;
    text-align: left;
}
</style>
</head>
<body>

<?php renderAppHeader('qr'); ?>

<main class="app-main">
    <div class="page-header">
        <h1>QR-Code &amp; Bewertungslink</h1>
        <div class="page-header__actions">
            <a href="<?= htmlspecialchars($feedbackUrl) ?>" target="_blank" rel="noopener" class="btn-secondary btn--sm">Landing-Page öffnen ↗</a>
        </div>
    </div>

    <?php if ($saved): ?><div class="callout callout--success">Bewertungslink gespeichert.</div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="callout callout--error"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

    <div class="qr-grid">

        <!-- ===== Konfigurator-Form ===== -->
        <section class="card">
            <header class="card__header"><h2>Bewertungslink-Konfigurator</h2></header>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                <div class="form-row">
                    <label for="feedback_url_slug">URL-Slug</label>
                    <input type="text" id="feedback_url_slug" name="feedback_url_slug"
                           value="<?= htmlspecialchars($feedbackSlug) ?>" required pattern="[a-z0-9\-]{2,64}">
                    <p class="form-help">Erlaubte Zeichen: a-z, 0-9, Bindestrich. Resultierende URL siehe rechts.</p>
                </div>

                <div class="form-row">
                    <label for="feedback_landing_title">Page-Titel (Browser-Tab)</label>
                    <input type="text" id="feedback_landing_title" name="feedback_landing_title"
                           value="<?= htmlspecialchars($shop['feedback_landing_title'] ?? '') ?>"
                           placeholder="z.B. Pilzling bewerten">
                </div>

                <div class="form-row">
                    <label for="feedback_landing_text">Begrüßungstext</label>
                    <textarea id="feedback_landing_text" name="feedback_landing_text" rows="5"
                              placeholder="Hey! Wenn dir gefällt was wir machen — lass uns gerne eine Bewertung da."><?= htmlspecialchars($shop['feedback_landing_text'] ?? '') ?></textarea>
                    <p class="form-help">Wird auf der Landing-Page über den Plattform-Buttons angezeigt.</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Speichern</button>
                </div>
            </form>
        </section>

        <!-- ===== QR-Code + URL ===== -->
        <section class="qr-card">
            <h2>QR-Code</h2>
            <p class="text-muted text-sm">Drucken auf Verpackung, Marktstand-Aufsteller, Kassenbons. Führt direkt zur Bewertungs-Seite.</p>

            <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR-Code für <?= htmlspecialchars($feedbackUrl) ?>">

            <div class="feedback-url-box">
                <code><?= htmlspecialchars($feedbackUrl) ?></code>
                <button type="button" class="btn-tertiary btn--sm"
                        onclick="navigator.clipboard.writeText('<?= htmlspecialchars($feedbackUrl) ?>'); this.textContent='Kopiert!'; setTimeout(()=>this.textContent='Kopieren', 2000)">Kopieren</button>
            </div>

            <div class="form-actions" style="justify-content:center">
                <a href="<?= htmlspecialchars($qrUrlDownload) ?>" target="_blank" rel="noopener" download="sporeprint-qr-<?= htmlspecialchars($feedbackSlug) ?>.png" class="btn-primary btn--sm">Als PNG (600px) herunterladen</a>
            </div>
        </section>

    </div>
</main>
</body>
</html>
