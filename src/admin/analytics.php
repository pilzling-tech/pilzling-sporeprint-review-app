<?php
declare(strict_types=1);

// Sporeprint Admin — Analytics-Dashboard.
// Zeigt:
//   - Counter-Cards (neue Reviews, Durchschnitt, Total)
//   - Bar-Chart: Reviews pro Tag (Inline-SVG)
//   - Bewertungs-Funnel (5 farbige Bars)
//   - Line-Chart: Durchschnitts-Rating-Entwicklung
//
// Datenquelle: aktuell echte DB-Daten aus reviews + sync_runs (sofern
// vorhanden). Da noch keine API-Daten da sind, sieht man oft Empty-States.

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/nav.php';

requireLogin();

$user = currentUser();
$activeShop = $_SESSION['active_shop'] ?? 'pilzling';

// === Range-Filter ===
$rangeDays = (int) ($_GET['range'] ?? 7);
if (!in_array($rangeDays, [7, 30, 90, 365], true)) $rangeDays = 7;

$rangeStart = date('Y-m-d', strtotime("-$rangeDays days"));
$rangeEnd = date('Y-m-d');

// === Counter ===
$newReviews = (int) (dbQueryOne(
    "SELECT COUNT(*) AS n FROM reviews WHERE shop_id = ? AND fetched_at >= ?",
    [$activeShop, $rangeStart]
)['n'] ?? 0);

$totalReviews = (int) (dbQueryOne(
    "SELECT COUNT(*) AS n FROM reviews WHERE shop_id = ?",
    [$activeShop]
)['n'] ?? 0);

$avgStars = dbQueryOne(
    "SELECT AVG(stars) AS avg FROM reviews WHERE shop_id = ? AND visibility = 'visible'",
    [$activeShop]
)['avg'] ?? null;
$avgStars = $avgStars ? round((float)$avgStars, 2) : null;

$openReplies = (int) (dbQueryOne(
    "SELECT COUNT(*) AS n FROM reviews r
     LEFT JOIN review_replies rr ON rr.review_id = r.review_id
     WHERE r.shop_id = ? AND rr.reply_id IS NULL",
    [$activeShop]
)['n'] ?? 0);

// === Bar-Chart-Daten: Reviews pro Tag ===
$dailyData = dbQueryAll(
    "SELECT DATE(fetched_at) AS d, COUNT(*) AS n
     FROM reviews
     WHERE shop_id = ? AND fetched_at >= ?
     GROUP BY DATE(fetched_at)
     ORDER BY d ASC",
    [$activeShop, $rangeStart]
);
$dailyMap = [];
foreach ($dailyData as $row) $dailyMap[$row['d']] = (int) $row['n'];

// === Bewertungs-Funnel ===
$funnel = [
    ['label' => 'Einladungen gesendet',          'value' => 0,             'color' => 'orange',  'icon' => '📨'],
    ['label' => 'Besuche der Bewertungs-Seite',  'value' => 0,             'color' => 'blue',    'icon' => '👁'],
    ['label' => 'QR-Code-Aufrufe',               'value' => 0,             'color' => 'blue',    'icon' => '📱'],
    ['label' => 'Neue Bewertungen',              'value' => $newReviews,   'color' => 'green',   'icon' => '⭐'],
    ['label' => 'Negative Bewertungen (≤ 3 ★)',  'value' => 0,             'color' => 'red',     'icon' => '⚠'],
];

// Negative Reviews-Count nachladen
$negativeCount = (int) (dbQueryOne(
    "SELECT COUNT(*) AS n FROM reviews WHERE shop_id = ? AND stars <= 3 AND fetched_at >= ?",
    [$activeShop, $rangeStart]
)['n'] ?? 0);
$funnel[4]['value'] = $negativeCount;

// === Line-Chart: Durchschnitts-Rating-Entwicklung ===
$ratingTrendRaw = dbQueryAll(
    "SELECT DATE(posted_at) AS d, AVG(stars) AS avg, COUNT(*) AS n
     FROM reviews
     WHERE shop_id = ? AND posted_at >= ? AND visibility = 'visible'
     GROUP BY DATE(posted_at)
     ORDER BY d ASC",
    [$activeShop, $rangeStart]
);
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — Analytics</title>
<link rel="stylesheet" href="/assets/admin.css">
<style>
.metric-card {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--space-4);
    box-shadow: var(--shadow-card);
    text-align: center;
}
.metric-card__value {
    font-size: 2rem;
    font-weight: 600;
    color: var(--color-dark);
    line-height: 1;
    margin: var(--space-2) 0;
}
.metric-card__label {
    font-size: var(--font-size-sm);
    color: var(--color-text-muted);
}
.chart-wrap {
    background: var(--color-white);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--space-4);
    margin-top: var(--space-4);
    box-shadow: var(--shadow-card);
}
.chart-wrap h3 {
    margin: 0 0 var(--space-3) 0;
    font-size: var(--font-size-md);
}
.bar-chart {
    display: flex;
    align-items: flex-end;
    gap: 4px;
    height: 180px;
    border-bottom: 1px solid var(--color-border);
    padding-bottom: var(--space-1);
}
.bar-chart__bar {
    flex: 1;
    min-width: 8px;
    background: var(--color-accent-blau-dark);
    border-radius: 3px 3px 0 0;
    position: relative;
    cursor: help;
    min-height: 2px;
    transition: background 0.15s;
}
.bar-chart__bar:hover { background: var(--color-primary); }
.bar-chart__labels {
    display: flex;
    gap: 4px;
    margin-top: var(--space-1);
    font-size: var(--font-size-xs);
    color: var(--color-text-muted);
}
.bar-chart__label {
    flex: 1;
    text-align: center;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.funnel-row {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3);
    margin-bottom: var(--space-2);
    border-radius: var(--radius);
    color: var(--color-white);
    font-weight: 500;
}
.funnel-row--orange { background: var(--color-primary); }
.funnel-row--blue   { background: var(--color-accent-blau-dark); }
.funnel-row--green  { background: var(--color-accent-gruen-dark); }
.funnel-row--red    { background: var(--color-error); }
.funnel-row__icon { font-size: 1.2rem; }
.funnel-row__label { flex: 1; }
.funnel-row__value {
    font-size: 1.4rem;
    font-weight: 600;
    background: rgba(255, 255, 255, 0.18);
    padding: 0.1rem 0.7rem;
    border-radius: var(--radius-sm);
    min-width: 50px;
    text-align: center;
}
.line-chart-svg {
    display: block;
    width: 100%;
    height: 200px;
}
.range-tabs {
    display: flex;
    gap: var(--space-1);
}
.range-tabs a {
    padding: 0.3rem 0.8rem;
    border-radius: var(--radius-sm);
    background: var(--color-white);
    border: 1px solid var(--color-border);
    color: var(--color-text);
    text-decoration: none;
    font-size: var(--font-size-sm);
}
.range-tabs a.is-active {
    background: var(--color-accent-blau-dark);
    border-color: var(--color-accent-blau-dark);
    color: var(--color-white);
}
.range-tabs a:hover { color: var(--color-primary); border-color: var(--color-primary); }
.range-tabs a.is-active:hover { color: var(--color-white); }
</style>
</head>
<body>

<?php renderAppHeader('analytics'); ?>

<main class="app-main">
    <div class="page-header">
        <h1>Analytics</h1>
        <div class="range-tabs">
            <?php foreach ([['7','7 Tage'],['30','30 Tage'],['90','90 Tage'],['365','1 Jahr']] as [$d, $l]): ?>
                <a href="?range=<?= $d ?>" class="<?= $rangeDays === (int)$d ? 'is-active' : '' ?>"><?= $l ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($totalReviews === 0): ?>
    <div class="callout callout--info">
        <strong>Hinweis:</strong> Noch keine Reviews in der Datenbank — die meisten Werte sind 0. Sobald Cron-Skripte (Phase K) Reviews aus Google + Trustpilot ziehen, füllt sich diese Page mit echten Werten.
    </div>
    <?php endif; ?>

    <!-- ===== Counter-Cards ===== -->
    <section class="grid grid--4">
        <div class="metric-card">
            <div class="metric-card__label">Neue Reviews (<?= $rangeDays ?> Tage)</div>
            <div class="metric-card__value"><?= $newReviews ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-card__label">Reviews insgesamt</div>
            <div class="metric-card__value"><?= $totalReviews ?></div>
        </div>
        <div class="metric-card">
            <div class="metric-card__label">Durchschnitts-Rating</div>
            <div class="metric-card__value">
                <?= $avgStars !== null ? number_format($avgStars, 1, ',', '') . ' ★' : '–' ?>
            </div>
        </div>
        <div class="metric-card">
            <div class="metric-card__label">Offene Antworten</div>
            <div class="metric-card__value"><?= $openReplies ?></div>
        </div>
    </section>

    <!-- ===== Bar-Chart: Reviews pro Tag ===== -->
    <section class="chart-wrap">
        <h3>Neue Reviews pro Tag</h3>
        <?php
            // Datums-Liste für die letzten N Tage erzeugen
            $dates = [];
            for ($i = $rangeDays - 1; $i >= 0; $i--) {
                $dates[] = date('Y-m-d', strtotime("-$i days"));
            }
            $maxVal = max(array_values($dailyMap) ?: [1]);
        ?>
        <div class="bar-chart" aria-label="Reviews pro Tag">
            <?php foreach ($dates as $d):
                $val = $dailyMap[$d] ?? 0;
                $heightPct = $maxVal > 0 ? ($val / $maxVal * 100) : 0;
            ?>
                <div class="bar-chart__bar"
                     style="height: <?= $heightPct ?>%"
                     title="<?= htmlspecialchars(formatDate($d)) ?>: <?= $val ?> Reviews"></div>
            <?php endforeach; ?>
        </div>
        <?php if ($rangeDays <= 30): ?>
        <div class="bar-chart__labels">
            <?php foreach ($dates as $d): ?>
                <div class="bar-chart__label"><?= date('d.m.', strtotime($d)) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- ===== Bewertungs-Funnel ===== -->
    <section class="chart-wrap">
        <h3>Bewertungs-Funnel</h3>
        <p class="text-muted text-sm mb-4">Der Funnel zeigt von Einladungen bis zum tatsächlichen Review-Eintrag — Phase 4 (Brevo) füllt die ersten drei Stufen automatisch.</p>
        <?php foreach ($funnel as $step): ?>
            <div class="funnel-row funnel-row--<?= $step['color'] ?>">
                <span class="funnel-row__icon"><?= $step['icon'] ?></span>
                <span class="funnel-row__label"><?= htmlspecialchars($step['label']) ?></span>
                <span class="funnel-row__value"><?= $step['value'] ?></span>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- ===== Line-Chart: Durchschnitts-Rating-Verlauf ===== -->
    <section class="chart-wrap">
        <h3>Durchschnitts-Rating-Verlauf</h3>
        <?php if (empty($ratingTrendRaw)): ?>
            <div class="data-table__empty">Noch nicht genug Daten für einen Verlauf.</div>
        <?php else:
            // Punkte berechnen für SVG-Path
            $points = [];
            $minDate = strtotime($rangeStart);
            $maxDate = strtotime($rangeEnd);
            $dateRange = max(1, $maxDate - $minDate);
            foreach ($ratingTrendRaw as $row) {
                $ts = strtotime($row['d']);
                $x = (($ts - $minDate) / $dateRange) * 100;
                $y = 100 - (((float)$row['avg'] - 1) / 4 * 100); // 1-5 → 100-0%
                $points[] = sprintf('%.1f,%.1f', $x, $y);
            }
        ?>
            <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="line-chart-svg">
                <!-- Grid-Linien -->
                <line x1="0" y1="0" x2="100" y2="0" stroke="#E0E0E0" stroke-width="0.3"/>
                <line x1="0" y1="25" x2="100" y2="25" stroke="#E0E0E0" stroke-width="0.3"/>
                <line x1="0" y1="50" x2="100" y2="50" stroke="#E0E0E0" stroke-width="0.3"/>
                <line x1="0" y1="75" x2="100" y2="75" stroke="#E0E0E0" stroke-width="0.3"/>
                <line x1="0" y1="100" x2="100" y2="100" stroke="#E0E0E0" stroke-width="0.3"/>
                <!-- Pfad -->
                <polyline fill="none" stroke="#5A74B8" stroke-width="0.6" points="<?= implode(' ', $points) ?>"/>
                <?php foreach ($points as $p): list($px, $py) = explode(',', $p); ?>
                    <circle cx="<?= $px ?>" cy="<?= $py ?>" r="0.8" fill="#F85B05"/>
                <?php endforeach; ?>
            </svg>
            <p class="text-muted text-sm mt-2">Y-Achse: 1-5 Sterne · X-Achse: <?= htmlspecialchars(formatDate($rangeStart)) ?> bis heute</p>
        <?php endif; ?>
    </section>

</main>
</body>
</html>
