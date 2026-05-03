<?php
declare(strict_types=1);

// Sporeprint Admin — Antworten verwalten.
// Zwei Modi:
//   1. Mit ?review_id=X → Reply-Editor für eine Review (neu oder edit)
//   2. Ohne Parameter   → Liste aller Antworten mit Push-Status

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/nav.php';

requireLogin();

$user = currentUser();
$activeShop = $_SESSION['active_shop'] ?? 'pilzling';

$reviewIdParam = (int) ($_GET['review_id'] ?? 0);
$mode = $reviewIdParam > 0 ? 'editor' : 'list';

$reviewToReply = null;
$existingReply = null;
$saved = $_GET['saved'] ?? '';
$errorMsg = null;

if ($mode === 'editor') {
    $reviewToReply = dbQueryOne(
        "SELECT r.*, rr.reply_id, rr.content AS reply_content, rr.external_status
         FROM reviews r
         LEFT JOIN review_replies rr ON rr.review_id = r.review_id
         WHERE r.review_id = ? AND r.shop_id = ?",
        [$reviewIdParam, $activeShop]
    );

    if (!$reviewToReply) {
        $errorMsg = 'Review nicht gefunden oder gehört zu einem anderen Shop.';
    } else {
        $existingReply = $reviewToReply['reply_id'] ? [
            'reply_id' => $reviewToReply['reply_id'],
            'content' => $reviewToReply['reply_content'],
            'external_status' => $reviewToReply['external_status'],
        ] : null;
    }
}

// === POST-Handler ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mode === 'editor' && $reviewToReply) {
    requireCsrfToken();

    $content = trim($_POST['content'] ?? '');
    if ($content === '') {
        $errorMsg = 'Antwort-Text darf nicht leer sein.';
    } else {
        if ($existingReply) {
            // Update bestehende Antwort
            dbExec(
                "UPDATE review_replies
                 SET content = ?,
                     posted_by = ?,
                     external_status = 'pending'
                 WHERE reply_id = ?",
                [$content, $user, $existingReply['reply_id']]
            );
        } else {
            // Neue Antwort
            dbExec(
                "INSERT INTO review_replies (review_id, content, posted_by, external_status)
                 VALUES (?, ?, ?, 'pending')",
                [$reviewIdParam, $content, $user]
            );
        }
        header('Location: /replies.php?review_id=' . $reviewIdParam . '&saved=1');
        exit;
    }
}

$csrfToken = csrfToken();

// === Liste-Modus: alle Antworten laden ===
$allReplies = [];
if ($mode === 'list') {
    $allReplies = dbQueryAll(
        "SELECT rr.*, r.author, r.stars, r.source, r.content AS review_content,
                r.posted_at AS review_posted_at
         FROM review_replies rr
         JOIN reviews r ON r.review_id = rr.review_id
         WHERE r.shop_id = ?
         ORDER BY rr.created_at DESC
         LIMIT 100",
        [$activeShop]
    );
}

$statusChip = [
    'pending' => ['chip--orange', 'Wartend'],
    'sent'    => ['chip--green',  'Gepostet'],
    'failed'  => ['chip--red',    'Fehlgeschlagen'],
];
$sourceChip = [
    'google'     => ['chip--blue',  'Google'],
    'trustpilot' => ['chip--green', 'Trustpilot'],
    'jtl'        => ['chip--peach', 'Produkt (JTL)'],
];
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — Antworten</title>
<link rel="stylesheet" href="/assets/admin.css">
<style>
.reply-editor {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--space-5);
    box-shadow: var(--shadow-card);
}
.original-review {
    background: var(--color-cream);
    border-left: 3px solid var(--color-accent-blau-dark);
    padding: var(--space-3) var(--space-4);
    border-radius: var(--radius-sm);
    margin-bottom: var(--space-5);
}
.original-review__head {
    display: flex;
    justify-content: space-between;
    margin-bottom: var(--space-2);
    font-size: var(--font-size-sm);
    color: var(--color-text-muted);
}
.original-review__stars {
    color: var(--color-primary);
    font-size: 1rem;
    margin-bottom: var(--space-2);
}
</style>
</head>
<body>

<?php renderAppHeader('replies'); ?>

<main class="app-main">

<?php if ($mode === 'editor'): ?>

    <div class="page-header">
        <h1><?= $existingReply ? 'Antwort bearbeiten' : 'Antwort schreiben' ?></h1>
        <div class="page-header__actions">
            <a href="/reviews.php" class="btn-tertiary btn--sm">← Zurück zu Reviews</a>
        </div>
    </div>

    <?php if ($saved): ?>
        <div class="callout callout--success">
            Antwort gespeichert. Push an die Plattform erfolgt sobald die API freigegeben ist.
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="callout callout--error"><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <?php if ($reviewToReply): ?>
        <div class="reply-editor">
            <h2>Original-Bewertung</h2>
            <?php
                $sChip = $sourceChip[$reviewToReply['source']] ?? ['chip--gray', $reviewToReply['source']];
                $stars = str_repeat('★', (int)$reviewToReply['stars']) . str_repeat('☆', 5 - (int)$reviewToReply['stars']);
            ?>
            <div class="original-review">
                <div class="original-review__head">
                    <span><strong><?= htmlspecialchars($reviewToReply['author'] ?? 'Anonym') ?></strong> · <?= htmlspecialchars(formatDate($reviewToReply['posted_at'])) ?></span>
                    <span class="chip <?= $sChip[0] ?>"><?= htmlspecialchars($sChip[1]) ?></span>
                </div>
                <div class="original-review__stars"><?= $stars ?></div>
                <div><?= nl2br(htmlspecialchars($reviewToReply['content'] ?? '')) ?></div>
                <?php if ($reviewToReply['product_name']): ?>
                    <p class="text-sm text-muted mt-2">🍄 <?= htmlspecialchars($reviewToReply['product_name']) ?></p>
                <?php endif; ?>
            </div>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="form-row">
                    <label for="content">Eure Antwort</label>
                    <textarea id="content" name="content" rows="6" required><?= htmlspecialchars($existingReply['content'] ?? '') ?></textarea>
                    <p class="form-help">Wird zunächst lokal gespeichert. Push an Google/Trustpilot erfolgt automatisch sobald API-Anbindung aktiv ist.</p>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Antwort speichern</button>
                    <a href="/reviews.php" class="btn-secondary">Abbrechen</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

<?php else: /* === Liste-Modus === */ ?>

    <div class="page-header">
        <h1>Antworten</h1>
    </div>

    <div class="callout callout--info">
        <strong>Hinweis:</strong> Antworten werden zunächst lokal gespeichert. Sobald die API-Anbindung aktiv ist, werden sie automatisch an Google bzw. Trustpilot gepusht.
    </div>

    <?php if (empty($allReplies)): ?>
        <div class="data-table__empty">
            Noch keine Antworten verfasst.
            <br><a href="/reviews.php">Zu den Reviews</a>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-datum">Erstellt</th>
                    <th class="col-author">Bewerter:in</th>
                    <th class="col-source">Plattform</th>
                    <th class="col-content">Antwort</th>
                    <th class="col-status">Push-Status</th>
                    <th class="col-actions">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allReplies as $rr):
                    $sChip = $sourceChip[$rr['source']] ?? ['chip--gray', $rr['source']];
                    $stChip = $statusChip[$rr['external_status']] ?? ['chip--gray', $rr['external_status']];
                    $excerpt = mb_substr($rr['content'], 0, 80) . (mb_strlen($rr['content']) > 80 ? '…' : '');
                ?>
                <tr title="<?= htmlspecialchars($rr['content']) ?>">
                    <td class="col-datum"><?= htmlspecialchars(formatDate($rr['created_at'], true)) ?></td>
                    <td class="col-author"><?= htmlspecialchars($rr['author'] ?? '–') ?></td>
                    <td><span class="chip <?= $sChip[0] ?>"><?= htmlspecialchars($sChip[1]) ?></span></td>
                    <td><?= htmlspecialchars($excerpt) ?></td>
                    <td><span class="chip <?= $stChip[0] ?>"><?= htmlspecialchars($stChip[1]) ?></span></td>
                    <td class="col-actions">
                        <a href="/replies.php?review_id=<?= (int)$rr['review_id'] ?>" class="btn-tertiary btn--sm">Bearbeiten</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php endif; ?>

</main>
</body>
</html>
