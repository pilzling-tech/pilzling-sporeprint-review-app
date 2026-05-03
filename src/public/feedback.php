<?php
declare(strict_types=1);

// Sporeprint Public — Bewertungs-Landing-Page.
// URL: https://sporeprint.pilzling.eu/feedback?shop=<slug>
//
// Liest shops.feedback_landing_* + ci_primary für Branding.
// Zeigt Plattform-Wahl-Buttons (Google + Trustpilot, optional JTL).
// Click-Through-Tracking ist Phase 4 (Brevo-Funnel-Stufe-3).

require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

http_response_code(200);

// === Shop laden via Slug ===
$slug = trim($_GET['shop'] ?? '');
if ($slug === '' || !preg_match('/^[a-z0-9\-]{1,64}$/', $slug)) {
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>400 — Ungültiger Shop-Parameter</h1>';
    exit;
}

$shop = dbQueryOne(
    "SELECT * FROM shops WHERE feedback_url_slug = ? OR shop_id = ?",
    [$slug, $slug]
);

if (!$shop) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>404 — Shop nicht gefunden</h1>';
    echo '<p><a href="https://pilzling.shop">Zur Pilzling-Hauptseite</a></p>';
    exit;
}

$pageTitle = $shop['feedback_landing_title'] ?? ($shop['name'] . ' bewerten');
$pageText = $shop['feedback_landing_text'] ?? 'Hey! Wenn dir gefällt was wir machen — lass uns gerne eine Bewertung da. Wähl einfach aus, wo du uns bewerten möchtest.';
$ciPrimary = $shop['ci_primary'] ?? '#F85B05';

// === Bewertungs-Plattform-URLs bauen ===
$googleUrl = !empty($shop['google_place_id'])
    ? 'https://search.google.com/local/writereview?placeid=' . urlencode($shop['google_place_id'])
    : null;
$trustpilotUrl = !empty($shop['domain'])
    ? 'https://de.trustpilot.com/evaluate/' . urlencode($shop['domain'])
    : null;
$jtlReviewUrl = !empty($shop['domain'])
    ? 'https://' . $shop['domain']
    : null;

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= htmlspecialchars($pageTitle) ?></title>
<style>
/* === SPOREPRINT FEEDBACK-LANDING-PAGE — Inline-CSS ===
 * Bewusst self-contained (kein Cross-Subdomain-Asset-Sharing).
 * Werte aus admin/assets/tokens.css gespiegelt — bei Aenderung
 * dort hier nachpflegen.
 */
:root {
    --bg: #F2F0ED;             /* cream */
    --card-bg: #FFFFFF;
    --text: #151824;
    --muted: #666666;
    --border: #E0E0E0;
    --accent: <?= htmlspecialchars($ciPrimary) ?>;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: "Rubik", system-ui, -apple-system, "Segoe UI", "Helvetica Neue", sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    line-height: 1.5;
}
.feedback-card {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 48px 32px;
    max-width: 480px;
    width: 100%;
    box-shadow: 0 4px 24px rgba(21, 24, 36, 0.08);
    text-align: center;
}
.feedback-card__brand {
    font-size: 28px;
    font-weight: 700;
    color: var(--accent);
    margin: 0 0 8px 0;
}
.feedback-card__intro {
    color: var(--muted);
    font-size: 16px;
    margin-bottom: 32px;
}
.feedback-platform {
    display: flex;
    align-items: center;
    gap: 16px;
    width: 100%;
    padding: 16px 20px;
    background: var(--bg);
    border: 1.5px solid var(--border);
    border-radius: 12px;
    margin-bottom: 12px;
    color: var(--text);
    text-decoration: none;
    font-weight: 500;
    transition: border-color 0.15s, background 0.15s, transform 0.15s;
}
.feedback-platform:hover {
    border-color: var(--accent);
    background: var(--card-bg);
    transform: translateY(-1px);
}
.feedback-platform__icon {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
}
.feedback-platform__label {
    flex: 1;
    text-align: left;
    font-size: 15px;
}
.feedback-platform__arrow {
    color: var(--muted);
    font-size: 18px;
}
.feedback-card__footer {
    margin-top: 32px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
    font-size: 12px;
    color: var(--muted);
    letter-spacing: 0.04em;
}
.feedback-card__footer a {
    color: var(--muted);
    text-decoration: none;
    border-bottom: 1px dotted var(--border);
}
.feedback-card__footer a:hover { color: var(--accent); border-color: var(--accent); }
@media (max-width: 480px) {
    .feedback-card { padding: 32px 20px; }
    .feedback-card__brand { font-size: 24px; }
    .feedback-platform { padding: 14px 16px; gap: 12px; }
}
</style>
</head>
<body>

<main class="feedback-card">
    <h1 class="feedback-card__brand"><?= htmlspecialchars($shop['name']) ?></h1>
    <p class="feedback-card__intro"><?= nl2br(htmlspecialchars($pageText)) ?></p>

    <?php if ($googleUrl): ?>
        <a href="<?= htmlspecialchars($googleUrl) ?>" target="_blank" rel="noopener" class="feedback-platform">
            <svg class="feedback-platform__icon" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" aria-label="Google">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            <span class="feedback-platform__label">Bei Google bewerten</span>
            <span class="feedback-platform__arrow">→</span>
        </a>
    <?php endif; ?>

    <?php if ($trustpilotUrl): ?>
        <a href="<?= htmlspecialchars($trustpilotUrl) ?>" target="_blank" rel="noopener" class="feedback-platform">
            <svg class="feedback-platform__icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-label="Trustpilot">
                <path fill="#00b67a" d="M12 0L14.59 8.41H23.51L16.46 13.59L19.05 22L12 16.82L4.95 22L7.54 13.59L0.49 8.41H9.41L12 0Z"/>
            </svg>
            <span class="feedback-platform__label">Bei Trustpilot bewerten</span>
            <span class="feedback-platform__arrow">→</span>
        </a>
    <?php endif; ?>

    <?php if ($jtlReviewUrl): ?>
        <a href="<?= htmlspecialchars($jtlReviewUrl) ?>" target="_blank" rel="noopener" class="feedback-platform">
            <span class="feedback-platform__icon" style="font-size:24px;line-height:32px;text-align:center">🍄</span>
            <span class="feedback-platform__label">Im Shop bewerten</span>
            <span class="feedback-platform__arrow">→</span>
        </a>
    <?php endif; ?>

    <div class="feedback-card__footer">
        Bereitgestellt von <a href="https://sporeprint.pilzling.eu/" target="_blank" rel="noopener">Sporeprint</a>
    </div>
</main>

</body>
</html>
