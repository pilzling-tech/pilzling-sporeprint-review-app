<?php
declare(strict_types=1);

// Sporeprint Public-API: Reviews-Liste für ein Shop.
//
// Aufruf: GET /api/reviews?shop=<shop_id>
// Response: pures JSON-Array (kein Envelope) — siehe ARCHITEKTUR.md
//          "API-Response-Konvention" und "API-Endpoint-Verzeichnis".
//
// Aktueller Stand (Phase 1): Endpoint läuft die volle Härtungs-Pipeline
// durch und liefert das gefilterte Reviews-Array aus DB. Da noch keine
// Cron-Skripte laufen, ist die Tabelle leer → Response ist "[]".
// Ab Phase API-Anbindung wird die Tabelle gefüllt — Endpoint bleibt
// unverändert.

require_once __DIR__ . '/../../lib/public_api_guard.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/helpers.php';

// === Layer 1-5 Härtung als allererste inhaltliche Zeile ===
// Bei Fail: enforcePublicApiHardening() schickt selbst die Response + exit.
$ctx = enforcePublicApiHardening($_GET['shop'] ?? '');
$shopId = $ctx['shop_id'];

// === Widget-Konfiguration laden ===
$widgetConfig = dbQueryOne(
    "SELECT layout, min_stars, max_items, show_product_reviews
     FROM widget_configs WHERE shop_id = ?",
    [$shopId]
);
if ($widgetConfig === null) {
    // Fallback-Defaults wenn (warum auch immer) keine Config existiert
    $widgetConfig = [
        'layout'               => 'carousel',
        'min_stars'            => 4,
        'max_items'            => 20,
        'show_product_reviews' => 1,
    ];
}

$minStars = (int) $widgetConfig['min_stars'];
$maxItems = (int) $widgetConfig['max_items'];
$includeProductReviews = (int) $widgetConfig['show_product_reviews'] === 1;

// === Reviews lesen — Datenminimierung (Layer 6) bereits in SELECT-Liste ===
// Whitelist der Felder gemäß ARCHITEKTUR.md "Was tatsächlich im
// Widget-Response steht":
//   stars, content, author, language, product_name, source, posted_at (Tag-genau)
// Niemals: IP/Geo/Email/exakte-Timestamps/interne IDs.

$sourceFilter = $includeProductReviews
    ? "('google', 'trustpilot', 'jtl')"
    : "('google', 'trustpilot')";

$rows = dbQueryAll(
    "SELECT
        stars,
        content,
        author,
        language,
        product_name,
        source,
        DATE_FORMAT(posted_at, '%Y-%m-%d') AS posted_on
     FROM reviews
     WHERE shop_id = ?
       AND visibility = 'visible'
       AND stars >= ?
       AND source IN {$sourceFilter}
     ORDER BY posted_at DESC
     LIMIT {$maxItems}",
    [$shopId, $minStars]
);

// Cast-Sauber: stars als int statt String aus PDO
foreach ($rows as &$row) {
    $row['stars'] = (int) $row['stars'];
}
unset($row);

// === Pure JSON-Array-Response (kein Envelope, Widget-optimiert) ===
jsonResponse($rows);
