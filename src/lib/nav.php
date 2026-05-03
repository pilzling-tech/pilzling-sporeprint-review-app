<?php
declare(strict_types=1);

// Sporeprint Admin — Nav-Helper (SSOT für App-Header).
//
// Eine zentrale Funktion rendert den App-Header. Alle Admin-Pages
// rufen renderAppHeader($currentPage) — keine Duplikation, ein
// Update-Punkt für Nav-Änderungen.
//
// Variante B aus Diskussion 2026-05-04: Top-Nav mit Dropdowns +
// Shop-Switcher rechts.
//
// Struktur:
//   - "Reviews ▾"   → reviews / replies / analytics
//   - "Widget ▾"    → widget-config / widget-test / qr
//   - "Einstellungen ▾" → shops / settings
//
// $currentPage muss matchen damit is-active korrekt gesetzt wird.
// Erlaubte Werte: 'dashboard', 'reviews', 'replies', 'analytics',
// 'widget-config', 'widget-test', 'qr', 'shops', 'settings'.

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Rendert den App-Header für Admin-Pages.
 * Aufruf: renderAppHeader('reviews');
 */
function renderAppHeader(string $currentPage): void
{
    $user = currentUser();
    $activeShop = $_SESSION['active_shop'] ?? 'pilzling';

    // Shop-Liste für Dropdown laden
    $shops = dbQueryAll("SELECT shop_id, name FROM shops ORDER BY shop_id");

    // Welche Top-Gruppe ist aktiv?
    $reviewsPages = ['reviews', 'replies', 'analytics'];
    $widgetPages = ['widget-config', 'widget-test', 'qr'];
    $settingsPages = ['shops', 'settings'];

    $reviewsActive = in_array($currentPage, $reviewsPages, true);
    $widgetActive = in_array($currentPage, $widgetPages, true);
    $settingsActive = in_array($currentPage, $settingsPages, true);
    $dashboardActive = $currentPage === 'dashboard';

    $userSafe = htmlspecialchars($user ?? '');
    $activeShopSafe = htmlspecialchars($activeShop);
    ?>
    <header class="app-header">
        <a href="/dashboard.php" class="app-header__brand">Sporeprint</a>

        <nav class="app-header__nav app-nav">
            <a href="/dashboard.php" class="app-nav__item <?= $dashboardActive ? 'is-active' : '' ?>">Dashboard</a>

            <!-- Reviews-Dropdown -->
            <div class="app-nav__group <?= $reviewsActive ? 'is-active' : '' ?>">
                <button type="button" class="app-nav__item app-nav__trigger" aria-haspopup="true" aria-expanded="false">
                    Reviews
                    <svg class="app-nav__chevron" width="10" height="10" viewBox="0 0 12 12" fill="none">
                        <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="app-nav__dropdown">
                    <a href="/reviews.php" class="<?= $currentPage === 'reviews' ? 'is-active' : '' ?>">Übersicht</a>
                    <a href="/replies.php" class="<?= $currentPage === 'replies' ? 'is-active' : '' ?>">Antworten</a>
                    <a href="/analytics.php" class="<?= $currentPage === 'analytics' ? 'is-active' : '' ?>">Analytics</a>
                </div>
            </div>

            <!-- Widget-Dropdown -->
            <div class="app-nav__group <?= $widgetActive ? 'is-active' : '' ?>">
                <button type="button" class="app-nav__item app-nav__trigger" aria-haspopup="true" aria-expanded="false">
                    Widget
                    <svg class="app-nav__chevron" width="10" height="10" viewBox="0 0 12 12" fill="none">
                        <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="app-nav__dropdown">
                    <a href="/widget-config.php" class="<?= $currentPage === 'widget-config' ? 'is-active' : '' ?>">Konfigurator</a>
                    <a href="/widget-test.php" class="<?= $currentPage === 'widget-test' ? 'is-active' : '' ?>">Vorschau</a>
                    <a href="/qr.php" class="<?= $currentPage === 'qr' ? 'is-active' : '' ?>">QR &amp; Bewertungslink</a>
                </div>
            </div>

            <!-- Einstellungen-Dropdown -->
            <div class="app-nav__group <?= $settingsActive ? 'is-active' : '' ?>">
                <button type="button" class="app-nav__item app-nav__trigger" aria-haspopup="true" aria-expanded="false">
                    Einstellungen
                    <svg class="app-nav__chevron" width="10" height="10" viewBox="0 0 12 12" fill="none">
                        <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="app-nav__dropdown">
                    <a href="/shops.php" class="<?= $currentPage === 'shops' ? 'is-active' : '' ?>">Shops</a>
                    <a href="/settings.php?tab=notifications" class="<?= $currentPage === 'settings' ? 'is-active' : '' ?>">Benachrichtigungen &amp; Konto</a>
                </div>
            </div>
        </nav>

        <div class="app-header__user">
            <!-- Shop-Switcher-Dropdown -->
            <div class="shop-switcher">
                <button type="button" class="shop-switcher__trigger" aria-haspopup="true" aria-expanded="false">
                    <span class="shop-switcher__indicator" aria-hidden="true"></span>
                    <span class="shop-switcher__label"><?= $activeShopSafe ?></span>
                    <svg class="shop-switcher__chevron" width="10" height="10" viewBox="0 0 12 12" fill="none">
                        <path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="shop-switcher__dropdown">
                    <div class="shop-switcher__dropdown-head">Shop wechseln</div>
                    <?php foreach ($shops as $s): ?>
                        <?php $isActive = $s['shop_id'] === $activeShop; ?>
                        <a href="/shops.php?action=switch&amp;shop=<?= urlencode($s['shop_id']) ?>"
                           class="<?= $isActive ? 'is-active' : '' ?>">
                            <span class="shop-switcher__indicator" aria-hidden="true"></span>
                            <span><?= htmlspecialchars($s['name']) ?></span>
                            <?php if ($isActive): ?><span class="shop-switcher__check">✓</span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                    <div class="shop-switcher__dropdown-foot">
                        <a href="/shops.php">Alle Shops verwalten →</a>
                    </div>
                </div>
            </div>

            <span class="app-header__user-name" title="Eingeloggt als <?= $userSafe ?>"><?= $userSafe ?></span>
            <a href="/logout.php" class="app-header__user-logout">Logout</a>
        </div>
    </header>

    <script>
    // Dropdown-Toggle für App-Nav + Shop-Switcher (Click-basiert, mobile-freundlich).
    (function() {
        const triggers = document.querySelectorAll('.app-nav__trigger, .shop-switcher__trigger');
        triggers.forEach(t => {
            t.addEventListener('click', e => {
                e.stopPropagation();
                const wasOpen = t.parentElement.classList.contains('is-open');
                // Alle anderen schließen
                document.querySelectorAll('.app-nav__group.is-open, .shop-switcher.is-open').forEach(g => g.classList.remove('is-open'));
                if (!wasOpen) {
                    t.parentElement.classList.add('is-open');
                    t.setAttribute('aria-expanded', 'true');
                } else {
                    t.setAttribute('aria-expanded', 'false');
                }
            });
        });
        // Click außerhalb → schließen
        document.addEventListener('click', () => {
            document.querySelectorAll('.app-nav__group.is-open, .shop-switcher.is-open').forEach(g => {
                g.classList.remove('is-open');
                const trigger = g.querySelector('[aria-expanded]');
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
            });
        });
        // ESC schließt
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.app-nav__group.is-open, .shop-switcher.is-open').forEach(g => g.classList.remove('is-open'));
            }
        });
    })();
    </script>
    <?php
}
