<?php
declare(strict_types=1);

// Sporeprint Admin — Reviews-Liste mit Filter + Inline-Actions.
//
// Filter via GET-Parameter (search, source, stars, visibility, has_reply, date_from, date_to).
// Pagination kommt in Phase 3.5 wenn echte Daten dahinter sind — aktuell zeigen wir
// alle Treffer (Schema v1 hat noch wenig Daten).

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

requireLogin();

$user = currentUser();

// === Aktiver Shop aus Session oder Default ===
$activeShop = $_SESSION['active_shop'] ?? 'pilzling';

// === Filter aus GET ===
$filterSearch = trim($_GET['search'] ?? '');
$filterSource = $_GET['source'] ?? '';        // '', 'google', 'trustpilot', 'jtl'
$filterStars = (int) ($_GET['stars'] ?? 0);   // 0 = alle, 1-5 = nur diese
$filterVisibility = $_GET['visibility'] ?? '';
$filterHasReply = $_GET['has_reply'] ?? '';   // '', 'yes', 'no'
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo = $_GET['date_to'] ?? '';

// === Query bauen ===
$where = ['r.shop_id = ?'];
$params = [$activeShop];

if ($filterSearch !== '') {
    $where[] = '(r.content LIKE ? OR r.author LIKE ?)';
    $params[] = '%' . $filterSearch . '%';
    $params[] = '%' . $filterSearch . '%';
}
if ($filterSource !== '') {
    $where[] = 'r.source = ?';
    $params[] = $filterSource;
}
if ($filterStars > 0) {
    $where[] = 'r.stars = ?';
    $params[] = $filterStars;
}
if ($filterVisibility !== '') {
    $where[] = 'r.visibility = ?';
    $params[] = $filterVisibility;
}
if ($filterHasReply === 'yes') {
    $where[] = 'EXISTS (SELECT 1 FROM review_replies rr WHERE rr.review_id = r.review_id)';
} elseif ($filterHasReply === 'no') {
    $where[] = 'NOT EXISTS (SELECT 1 FROM review_replies rr WHERE rr.review_id = r.review_id)';
}
if ($filterDateFrom !== '') {
    $where[] = 'r.posted_at >= ?';
    $params[] = $filterDateFrom . ' 00:00:00';
}
if ($filterDateTo !== '') {
    $where[] = 'r.posted_at <= ?';
    $params[] = $filterDateTo . ' 23:59:59';
}

$whereSql = implode(' AND ', $where);

$reviews = dbQueryAll(
    "SELECT r.*, rr.reply_id, rr.content AS reply_content, rr.created_at AS reply_created_at,
            rr.external_status AS reply_external_status
     FROM reviews r
     LEFT JOIN review_replies rr ON rr.review_id = r.review_id
     WHERE $whereSql
     ORDER BY r.posted_at DESC
     LIMIT 200",
    $params
);

// === Counter (zur Info im Header) ===
$totalCount = (int) (dbQueryOne(
    "SELECT COUNT(*) AS n FROM reviews r WHERE $whereSql",
    $params
)['n'] ?? 0);

$shopList = dbQueryAll("SELECT shop_id, name FROM shops ORDER BY shop_id");

$csrfToken = csrfToken();

// === Helper-Mappings für Chips ===
$visibilityChip = [
    'visible' => ['chip--green', 'Sichtbar'],
    'hidden'  => ['chip--gray',  'Versteckt'],
    'flagged' => ['chip--red',   'Geflagged'],
];
$sourceChip = [
    'google'     => ['chip--blue',  'Google'],
    'trustpilot' => ['chip--green', 'Trustpilot'],
    'jtl'        => ['chip--peach', 'Produkt (JTL)'],
];
$replyStatusChip = [
    'pending' => ['chip--orange', 'Wartend'],
    'sent'    => ['chip--green',  'Gepostet'],
    'failed'  => ['chip--red',    'Fehlgeschlagen'],
];
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — Reviews</title>
<link rel="stylesheet" href="/assets/admin.css">
<style>
/* Page-spezifische Layout-Erweiterungen — Reviews-Liste mit Sidebar.
   Nur Layout-Anordnung, KEINE neuen Komponenten oder Farben (alles
   ueber Tokens aus tokens.css). Wenn Pattern wiederverwendet werden
   soll: nach components.css heben (.layout-with-sidebar). */
.layout-with-sidebar {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: var(--space-5);
    align-items: flex-start;
}
@media (max-width: 900px) {
    .layout-with-sidebar { grid-template-columns: 1fr; }
}
.review-card {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--space-4);
    margin-bottom: var(--space-3);
    box-shadow: var(--shadow-card);
}
.review-card__head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: var(--space-3);
    margin-bottom: var(--space-2);
}
.review-card__author {
    display: flex;
    gap: var(--space-2);
    align-items: center;
}
.review-card__avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--color-accent-blau-soft);
    color: var(--color-accent-blau-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 600;
    flex-shrink: 0;
}
.review-card__name {
    font-weight: 600;
    color: var(--color-dark);
}
.review-card__date {
    font-size: var(--font-size-sm);
    color: var(--color-text-muted);
}
.review-card__meta-chips {
    display: flex;
    gap: var(--space-1);
    flex-wrap: wrap;
}
.review-card__stars {
    font-size: 1rem;
    color: var(--color-primary);
    letter-spacing: 1px;
    margin-bottom: var(--space-2);
}
.review-card__content {
    color: var(--color-text);
    margin-bottom: var(--space-3);
    line-height: 1.55;
}
.review-card__product-tag {
    display: inline-block;
    background: var(--color-accent-peach-soft);
    color: var(--color-accent-peach-dark);
    padding: 0.15rem 0.5rem;
    border-radius: var(--radius-pill);
    font-size: var(--font-size-xs);
    font-weight: 600;
    margin-bottom: var(--space-3);
}
.review-card__actions {
    display: flex;
    gap: var(--space-2);
    flex-wrap: wrap;
    padding-top: var(--space-2);
    border-top: 1px solid var(--color-border-soft);
}
.review-card__reply {
    margin-top: var(--space-3);
    padding: var(--space-3);
    background: var(--color-cream);
    border-left: 3px solid var(--color-accent-blau-dark);
    border-radius: var(--radius-sm);
    font-size: var(--font-size-sm);
}
.review-card__reply-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: var(--space-2);
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
}
.filter-sidebar {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--space-4);
    box-shadow: var(--shadow-card);
    position: sticky;
    top: var(--space-4);
}
.filter-sidebar h3 {
    margin: 0 0 var(--space-3) 0;
    font-size: var(--font-size-md);
}
.filter-sidebar .form-row { margin-bottom: var(--space-3); }
.stars-toggle {
    display: flex;
    gap: var(--space-1);
    flex-wrap: wrap;
}
.stars-toggle button {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    padding: 0.25rem 0.6rem;
    cursor: pointer;
    font-size: var(--font-size-sm);
    color: var(--color-text);
}
.stars-toggle button.is-active {
    background: var(--color-accent-blau-dark);
    border-color: var(--color-accent-blau-dark);
    color: var(--color-white);
}
.stars-toggle button:hover { border-color: var(--color-primary); color: var(--color-primary); }
.stars-toggle button.is-active:hover { color: var(--color-white); }
</style>
</head>
<body>

<header class="app-header">
    <a href="/dashboard.php" class="app-header__brand">Sporeprint</a>
    <nav class="app-header__nav">
        <a href="/dashboard.php">Dashboard</a>
        <a href="/reviews.php" class="is-active">Reviews</a>
        <a href="/replies.php">Antworten</a>
        <a href="/analytics.php">Analytics</a>
        <a href="/widget-test.php">Widget-Vorschau</a>
    </nav>
    <div class="app-header__user">
        <span class="text-muted">Shop: <strong><?= htmlspecialchars($activeShop) ?></strong></span>
        <span><?= htmlspecialchars($user ?? '') ?></span>
        <a href="/logout.php">Logout</a>
    </div>
</header>

<main class="app-main">
    <div class="page-header">
        <h1>Reviews</h1>
        <div class="page-header__actions">
            <a href="/widget-config.php" class="btn-secondary btn--sm">Widget-Konfigurator</a>
        </div>
    </div>

    <div class="callout callout--info">
        <strong>Hinweis:</strong> Reviews-API wartet auf Freigabe (Google + Trustpilot). Diese Liste zeigt aktuell <?= $totalCount ?> Einträge aus der Datenbank. Sobald die Cron-Skripte laufen, füllt sich die Liste automatisch.
    </div>

    <div class="layout-with-sidebar">

        <!-- ===== Reviews-Liste ===== -->
        <section>
            <?php if (empty($reviews)): ?>
                <div class="data-table__empty">
                    Keine Reviews mit dieser Filter-Kombination.
                    <?php if ($filterSearch || $filterSource || $filterStars || $filterVisibility || $filterHasReply || $filterDateFrom || $filterDateTo): ?>
                        <br><a href="/reviews.php">Filter zurücksetzen</a>
                    <?php else: ?>
                        <br><span class="text-muted">Sobald APIs freigegeben sind, werden Reviews automatisch via Cron eingespielt.</span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $r):
                    $vChip = $visibilityChip[$r['visibility']] ?? ['chip--gray', $r['visibility']];
                    $sChip = $sourceChip[$r['source']] ?? ['chip--gray', $r['source']];
                    $stars = str_repeat('★', (int)$r['stars']) . str_repeat('☆', 5 - (int)$r['stars']);
                    $initials = strtoupper(mb_substr(trim($r['author'] ?? '?'), 0, 1));
                ?>
                <article class="review-card" data-review-id="<?= (int)$r['review_id'] ?>">
                    <div class="review-card__head">
                        <div class="review-card__author">
                            <div class="review-card__avatar"><?= htmlspecialchars($initials) ?></div>
                            <div>
                                <div class="review-card__name"><?= htmlspecialchars($r['author'] ?? 'Anonym') ?></div>
                                <div class="review-card__date"><?= htmlspecialchars(formatDate($r['posted_at'], false)) ?></div>
                            </div>
                        </div>
                        <div class="review-card__meta-chips">
                            <span class="chip <?= $sChip[0] ?>"><?= htmlspecialchars($sChip[1]) ?></span>
                            <span class="chip <?= $vChip[0] ?>"><?= htmlspecialchars($vChip[1]) ?></span>
                        </div>
                    </div>

                    <div class="review-card__stars" aria-label="<?= (int)$r['stars'] ?> von 5 Sternen">
                        <?= $stars ?>
                    </div>

                    <?php if ($r['product_name']): ?>
                        <span class="review-card__product-tag">🍄 <?= htmlspecialchars($r['product_name']) ?></span>
                    <?php endif; ?>

                    <div class="review-card__content"><?= nl2br(htmlspecialchars($r['content'] ?? '')) ?></div>

                    <?php if ($r['reply_id']):
                        $rChip = $replyStatusChip[$r['reply_external_status']] ?? ['chip--gray', $r['reply_external_status']];
                    ?>
                    <div class="review-card__reply">
                        <div class="review-card__reply-meta">
                            <span><strong>Eure Antwort</strong> · <?= htmlspecialchars(formatDate($r['reply_created_at'], true)) ?></span>
                            <span class="chip <?= $rChip[0] ?>"><?= htmlspecialchars($rChip[1]) ?></span>
                        </div>
                        <?= nl2br(htmlspecialchars($r['reply_content'] ?? '')) ?>
                    </div>
                    <?php endif; ?>

                    <div class="review-card__actions">
                        <?php if (!$r['reply_id']): ?>
                            <a href="/replies.php?review_id=<?= (int)$r['review_id'] ?>" class="btn-primary btn--sm">Antworten</a>
                        <?php else: ?>
                            <a href="/replies.php?review_id=<?= (int)$r['review_id'] ?>" class="btn-secondary btn--sm">Antwort bearbeiten</a>
                        <?php endif; ?>

                        <?php if ($r['visibility'] === 'visible'): ?>
                            <button class="btn-tertiary btn--sm" data-action="hide" data-review-id="<?= (int)$r['review_id'] ?>">Ausblenden</button>
                        <?php else: ?>
                            <button class="btn-tertiary btn--sm" data-action="show" data-review-id="<?= (int)$r['review_id'] ?>">Anzeigen</button>
                        <?php endif; ?>

                        <?php if ($r['visibility'] !== 'flagged'): ?>
                            <button class="btn-tertiary btn--sm" data-action="flag" data-review-id="<?= (int)$r['review_id'] ?>">Flaggen</button>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>

        <!-- ===== Filter-Sidebar ===== -->
        <aside class="filter-sidebar">
            <h3>Filter</h3>
            <form method="get" action="/reviews.php">

                <div class="form-row">
                    <label for="search">Suche</label>
                    <input type="search" id="search" name="search" placeholder="Text oder Name…" value="<?= htmlspecialchars($filterSearch) ?>">
                </div>

                <div class="form-row">
                    <label for="source">Plattform</label>
                    <select id="source" name="source">
                        <option value="">Alle</option>
                        <option value="google" <?= $filterSource === 'google' ? 'selected' : '' ?>>Google</option>
                        <option value="trustpilot" <?= $filterSource === 'trustpilot' ? 'selected' : '' ?>>Trustpilot</option>
                        <option value="jtl" <?= $filterSource === 'jtl' ? 'selected' : '' ?>>Produkt (JTL)</option>
                    </select>
                </div>

                <div class="form-row">
                    <label>Sterne</label>
                    <div class="stars-toggle">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <button type="button"
                                    class="<?= $filterStars === $i ? 'is-active' : '' ?>"
                                    data-stars="<?= $i ?>"><?= $i ?> ★</button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="stars" id="stars-input" value="<?= $filterStars ?: '' ?>">
                </div>

                <div class="form-row">
                    <label for="visibility">Sichtbarkeit</label>
                    <select id="visibility" name="visibility">
                        <option value="">Alle</option>
                        <option value="visible" <?= $filterVisibility === 'visible' ? 'selected' : '' ?>>Sichtbar</option>
                        <option value="hidden" <?= $filterVisibility === 'hidden' ? 'selected' : '' ?>>Versteckt</option>
                        <option value="flagged" <?= $filterVisibility === 'flagged' ? 'selected' : '' ?>>Geflagged</option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="has_reply">Antwort-Status</label>
                    <select id="has_reply" name="has_reply">
                        <option value="">Alle</option>
                        <option value="yes" <?= $filterHasReply === 'yes' ? 'selected' : '' ?>>Beantwortet</option>
                        <option value="no" <?= $filterHasReply === 'no' ? 'selected' : '' ?>>Offen</option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="date_from">Datum von</label>
                    <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($filterDateFrom) ?>">
                </div>

                <div class="form-row">
                    <label for="date_to">bis</label>
                    <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($filterDateTo) ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary btn--sm btn--block">Anwenden</button>
                </div>
                <div class="form-actions">
                    <a href="/reviews.php" class="btn-tertiary btn--sm btn--block">Zurücksetzen</a>
                </div>
            </form>
        </aside>

    </div>
</main>

<script>
// === Sterne-Toggle ===
document.querySelectorAll('.stars-toggle button').forEach(btn => {
    btn.addEventListener('click', () => {
        const val = btn.getAttribute('data-stars');
        const input = document.getElementById('stars-input');
        const wasActive = btn.classList.contains('is-active');
        document.querySelectorAll('.stars-toggle button').forEach(b => b.classList.remove('is-active'));
        if (!wasActive) {
            btn.classList.add('is-active');
            input.value = val;
        } else {
            input.value = '';
        }
    });
});

// === Inline-Actions (hide/show/flag) ===
document.querySelectorAll('[data-action]').forEach(btn => {
    btn.addEventListener('click', async () => {
        const action = btn.getAttribute('data-action');
        const reviewId = btn.getAttribute('data-review-id');
        if (!confirm('Wirklich "' + action + '"?')) return;
        try {
            const res = await fetch('/api/review-action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= htmlspecialchars($csrfToken) ?>' },
                body: JSON.stringify({ review_id: reviewId, action: action })
            });
            const data = await res.json();
            if (data.ok) {
                location.reload();
            } else {
                alert(data.error || 'Aktion fehlgeschlagen');
            }
        } catch (e) {
            alert('Netzwerk-Fehler: ' + e.message);
        }
    });
});
</script>

</body>
</html>
