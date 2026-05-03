<?php
declare(strict_types=1);

// Sporeprint Admin — Einstellungen.
// Tab-Struktur:
//   - notifications (default): Benachrichtigungs-E-Mails verwalten
//   - integrations:   API-Status (Google, Trustpilot)
//   - account:        Eingeloggter Admin, Hash-Generator-Hinweis

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/nav.php';

requireLogin();

$user = currentUser();
$activeShop = $_SESSION['active_shop'] ?? 'pilzling';

$tab = $_GET['tab'] ?? 'notifications';
if (!in_array($tab, ['notifications', 'integrations', 'account'], true)) $tab = 'notifications';

$saved = isset($_GET['saved']);
$errorMsg = null;

// === POST-Handler: Notifications ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'notifications') {
    requireCsrfToken();

    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'add') {
        $email = trim($_POST['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        } else {
            try {
                dbExec(
                    "INSERT INTO notification_emails (shop_id, email, notify_new_review, notify_reply_received, notify_negative_only, is_active)
                     VALUES (?, ?, 1, 1, 0, 1)",
                    [$activeShop, $email]
                );
                header('Location: /settings.php?tab=notifications&saved=1');
                exit;
            } catch (PDOException $e) {
                $errorMsg = 'E-Mail bereits hinterlegt: ' . htmlspecialchars($email);
            }
        }
    } elseif ($postAction === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            dbExec(
                "DELETE FROM notification_emails WHERE id = ? AND shop_id = ?",
                [$id, $activeShop]
            );
            header('Location: /settings.php?tab=notifications&saved=1');
            exit;
        }
    } elseif ($postAction === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $field = $_POST['field'] ?? '';
        $allowedFields = ['is_active', 'notify_new_review', 'notify_reply_received', 'notify_negative_only'];
        if ($id > 0 && in_array($field, $allowedFields, true)) {
            dbExec(
                "UPDATE notification_emails SET $field = 1 - $field WHERE id = ? AND shop_id = ?",
                [$id, $activeShop]
            );
            header('Location: /settings.php?tab=notifications&saved=1');
            exit;
        }
    }
}

// === Daten laden ===
$notifs = $tab === 'notifications'
    ? dbQueryAll(
        "SELECT * FROM notification_emails WHERE shop_id = ? ORDER BY created_at DESC",
        [$activeShop]
    )
    : [];

$shop = dbQueryOne("SELECT * FROM shops WHERE shop_id = ?", [$activeShop]);

$csrfToken = csrfToken();
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — Einstellungen</title>
<link rel="stylesheet" href="/assets/admin.css">
<style>
.settings-tabs {
    display: flex;
    gap: var(--space-1);
    border-bottom: 1px solid var(--color-border);
    margin-bottom: var(--space-5);
}
.settings-tabs a {
    padding: var(--space-2) var(--space-4);
    color: var(--color-text-muted);
    text-decoration: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    font-weight: 500;
}
.settings-tabs a.is-active {
    color: var(--color-accent-blau-dark);
    border-bottom-color: var(--color-accent-blau-dark);
}
.settings-tabs a:hover { color: var(--color-primary); }
.notif-row {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) 0;
    border-bottom: 1px solid var(--color-border-soft);
}
.notif-row:last-child { border-bottom: none; }
.notif-row__email {
    flex: 1;
    font-family: var(--font-mono);
    font-size: var(--font-size-sm);
}
.notif-toggles {
    display: flex;
    gap: var(--space-3);
    flex-wrap: wrap;
    font-size: var(--font-size-xs);
}
.notif-toggle-btn {
    background: none;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    padding: 0.25rem 0.5rem;
    cursor: pointer;
    color: var(--color-text-muted);
    font-size: var(--font-size-xs);
}
.notif-toggle-btn.is-on {
    background: var(--color-accent-blau-soft);
    border-color: var(--color-accent-blau-dark);
    color: var(--color-accent-blau-dark);
}
.notif-toggle-btn:hover { border-color: var(--color-primary); color: var(--color-primary); }
.api-status-row {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3);
    background: var(--color-cream);
    border-radius: var(--radius);
    margin-bottom: var(--space-3);
}
.api-status-row__logo {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
}
.api-status-row__info { flex: 1; }
</style>
</head>
<body>

<?php renderAppHeader('settings'); ?>

<main class="app-main">
    <div class="page-header">
        <h1>Einstellungen</h1>
    </div>

    <nav class="settings-tabs">
        <a href="?tab=notifications" class="<?= $tab === 'notifications' ? 'is-active' : '' ?>">Benachrichtigungen</a>
        <a href="?tab=integrations" class="<?= $tab === 'integrations' ? 'is-active' : '' ?>">Integrationen</a>
        <a href="?tab=account" class="<?= $tab === 'account' ? 'is-active' : '' ?>">Konto</a>
    </nav>

    <?php if ($saved): ?><div class="callout callout--success">Einstellungen gespeichert.</div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="callout callout--error"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

<?php if ($tab === 'notifications'): ?>

    <section class="card">
        <header class="card__header"><h2>Benachrichtigungs-E-Mails</h2></header>
        <p class="text-muted">Bei neuen Reviews oder eingehenden Antworten werden diese E-Mails benachrichtigt. Pro Empfänger einzeln togglebar.</p>

        <?php if (empty($notifs)): ?>
            <div class="data-table__empty">Noch keine E-Mails konfiguriert.</div>
        <?php else: ?>
            <?php foreach ($notifs as $n): ?>
                <div class="notif-row">
                    <div class="notif-row__email">
                        <?= htmlspecialchars($n['email']) ?>
                        <?php if (!$n['is_active']): ?><span class="chip chip--gray">Pausiert</span><?php endif; ?>
                    </div>
                    <div class="notif-toggles">
                        <?php
                        $toggles = [
                            'is_active' => 'Aktiv',
                            'notify_new_review' => 'Neue Reviews',
                            'notify_reply_received' => 'Eingehende Antworten',
                            'notify_negative_only' => 'Nur negative (≤ 3 ★)',
                        ];
                        foreach ($toggles as $field => $label): ?>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                                <input type="hidden" name="field" value="<?= $field ?>">
                                <button type="submit" class="notif-toggle-btn <?= (int)$n[$field] === 1 ? 'is-on' : '' ?>">
                                    <?= htmlspecialchars($label) ?>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                    <form method="post" onsubmit="return confirm('E-Mail wirklich entfernen?')" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                        <button type="submit" class="btn-icon btn-icon--danger" title="Entfernen">×</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <hr>

        <h3>Neue E-Mail hinzufügen</h3>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <label for="new_email">E-Mail-Adresse</label>
                <input type="email" id="new_email" name="email" required placeholder="kontakt@<?= htmlspecialchars($shop['domain'] ?? 'pilzling.shop') ?>">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Hinzufügen</button>
            </div>
        </form>
    </section>

<?php elseif ($tab === 'integrations'): ?>

    <section class="card">
        <header class="card__header"><h2>API-Integrationen</h2></header>
        <p class="text-muted">Status der externen Review-Quellen für Shop "<?= htmlspecialchars($activeShop) ?>".</p>

        <div class="api-status-row">
            <span class="api-status-row__logo" style="font-size:32px">G</span>
            <div class="api-status-row__info">
                <strong>Google Business Profile API</strong>
                <div class="text-sm text-muted">
                    <?php if (!empty($shop['google_place_id'])): ?>
                        Place-ID konfiguriert: <code><?= htmlspecialchars($shop['google_place_id']) ?></code>
                    <?php else: ?>
                        <span class="chip chip--orange">Place-ID fehlt</span> — in <a href="/shops.php?action=edit&shop=<?= htmlspecialchars($activeShop) ?>">Shops → Bearbeiten</a> eintragen
                    <?php endif; ?>
                </div>
                <div class="text-sm text-muted mt-2">
                    OAuth-Status: <span class="chip chip--orange">Antrag wartet auf Freigabe</span>
                </div>
            </div>
        </div>

        <div class="api-status-row">
            <span class="api-status-row__logo" style="color:#00b67a;font-size:24px">★</span>
            <div class="api-status-row__info">
                <strong>Trustpilot Public API</strong>
                <div class="text-sm text-muted">
                    <?php if (!empty($shop['trustpilot_unit_id'])): ?>
                        Business Unit ID konfiguriert: <code><?= htmlspecialchars($shop['trustpilot_unit_id']) ?></code>
                    <?php else: ?>
                        <span class="chip chip--orange">Unit-ID fehlt</span> — in <a href="/shops.php?action=edit&shop=<?= htmlspecialchars($activeShop) ?>">Shops → Bearbeiten</a> eintragen
                    <?php endif; ?>
                </div>
                <div class="text-sm text-muted mt-2">
                    API-Status: <span class="chip chip--orange">Antrag wartet auf Freigabe</span>
                </div>
            </div>
        </div>

        <div class="api-status-row">
            <span class="api-status-row__logo">🍄</span>
            <div class="api-status-row__info">
                <strong>JTL Produktbewertungen</strong>
                <div class="text-sm text-muted">
                    <span class="chip chip--gray">Zurückgestellt</span> — JTL-REST-API ist Beta + kostenpflichtig.
                    Geplanter Workaround: direkt-SQL aus JTL-MSSQL-DB (eigener Plan).
                </div>
            </div>
        </div>
    </section>

<?php elseif ($tab === 'account'): ?>

    <section class="card">
        <header class="card__header"><h2>Konto</h2></header>

        <div class="form-row">
            <label>Eingeloggt als</label>
            <input type="text" value="<?= htmlspecialchars($user ?? '') ?>" disabled>
        </div>

        <div class="form-row">
            <label>Session aktiv seit</label>
            <input type="text" value="<?= htmlspecialchars(formatDate(date('Y-m-d H:i:s', $_SESSION['logged_in_at'] ?? time()), true)) ?>" disabled>
        </div>

        <div class="form-row">
            <label>Aktiver Shop (in Session)</label>
            <input type="text" value="<?= htmlspecialchars($activeShop) ?>" disabled>
            <p class="form-help"><a href="/shops.php">In Shops-Verwaltung wechseln</a></p>
        </div>

        <hr>

        <h3>Passwort ändern</h3>
        <p class="text-muted">Single-Admin-Modus: Passwort wird via <code>config/.env</code> gepflegt (<code>ADMIN_PASSWORD_HASH</code>). Hash-Generierung am einfachsten via:</p>
        <pre>php -r 'echo password_hash("DEIN_NEUES_PASSWORT", PASSWORD_BCRYPT);'</pre>
        <p class="text-muted text-sm">Hash dann in <code>config/.env</code> bei <code>ADMIN_PASSWORD_HASH=</code> einsetzen + WinSCP-Sync.</p>

        <hr>

        <h3>Logout</h3>
        <p class="text-muted">Beendet die Session und führt zurück zur Login-Seite.</p>
        <a href="/logout.php" class="btn-danger btn--sm">Jetzt ausloggen</a>
    </section>

<?php endif; ?>

</main>
</body>
</html>
