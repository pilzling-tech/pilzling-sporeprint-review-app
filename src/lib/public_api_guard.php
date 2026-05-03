<?php
declare(strict_types=1);

// Sporeprint — Public-API-Härtung (SSOT).
// Implementiert die Layer 1-4 der 6 Härtungs-Schichten aus Konzept Thema 4
// (Layer 5 SRI ist client-side, Layer 6 Datenminimierung ist Endpoint-
// Verantwortung).
//
// Pattern: Sporeprint-Erstwerk. Wandert nach Phase 1 als Pre-Check D7 nach
// references/php-patterns/public-api-hardening/ als Workspace-SSOT.
//
// Verwendung in jedem Public-Endpoint als allererste inhaltliche Zeile:
//
//   require_once __DIR__ . '/../../lib/public_api_guard.php';
//   $context = enforcePublicApiHardening($_GET['shop'] ?? '');
//   // $context = ['shop_id' => 'pilzling', 'shop_row' => [...]]

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/rate_limit.php';

/**
 * Konstanten für die Härtungs-Schichten.
 */
const PUBLIC_API_RATE_LIMIT_PER_MINUTE = 60;
const PUBLIC_API_RATE_LIMIT_WINDOW_MIN = 1;
const PUBLIC_API_CACHE_MAX_AGE_SEC = 21600; // 6h, gleicher Rhythmus wie Cron

/**
 * Erzwingt alle Public-API-Härtungs-Layer in der richtigen Reihenfolge:
 *   1) Shop-ID validieren (existiert in DB?)
 *   2) CORS-Header setzen (dynamisch nach Shop-Domain)
 *   3) Referer-Check (Aufruf muss von Shop-Domain kommen)
 *   4) Rate-Limit per IP
 *   5) Cache-Header setzen
 *
 * Bei Fail: schickt sofortige Response + exit. Aufrufer muss nichts mehr tun.
 *
 * @param string $shopId  Shop-ID aus Request (z.B. $_GET['shop'])
 * @return array{shop_id: string, shop_row: array}  Bei Erfolg: Shop-Metadaten für Endpoint-Logik
 */
function enforcePublicApiHardening(string $shopId): array
{
    // === Layer 1: Shop-ID validieren ===
    $shopId = trim($shopId);
    if ($shopId === '' || !preg_match('/^[a-z0-9\-]{1,32}$/', $shopId)) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Invalid shop parameter']);
        exit;
    }

    $shop = dbQueryOne(
        "SELECT shop_id, name, domain, ci_primary, ci_secondary
         FROM shops WHERE shop_id = ?",
        [$shopId]
    );

    if ($shop === null) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Shop not found']);
        exit;
    }

    // === Layer 2: CORS-Whitelist ===
    // Allow-Origin nur für die exakte Shop-Domain (https-Variante).
    $allowedOrigin = 'https://' . $shop['domain'];
    $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($requestOrigin !== '' && $requestOrigin === $allowedOrigin) {
        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
        header('Vary: Origin');
    }
    // Preflight-Methoden: nur GET nötig für Public-API
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    // OPTIONS-Preflight beantworten ohne Body
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    // === Layer 3: Referer-Check ===
    // Browser senden Referer mit der vollen Page-URL. Wir akzeptieren
    // wenn der Referer mit der Shop-Domain beginnt. Fehlender Referer
    // ist toleriert (Privacy-Browser, manche curl-Setups) — dann greifen
    // CORS + Rate-Limit als Fallback.
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referer !== '') {
        $expectedPrefix = 'https://' . $shop['domain'];
        if (!str_starts_with($referer, $expectedPrefix)
            && !str_starts_with($referer, 'http://' . $shop['domain'])) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Referer mismatch']);
            exit;
        }
    }

    // === Layer 4: Rate-Limit per IP ===
    try {
        $ipBinary = binaryIp(clientIp());
    } catch (RuntimeException $e) {
        // Ungültige IP — sicherheitshalber durchlassen aber nicht raten,
        // damit kein Bug im IP-Parser einen Endpoint-DoS verursacht.
        $ipBinary = null;
    }

    if ($ipBinary !== null) {
        $ok = checkRateLimit(
            $ipBinary,
            PUBLIC_API_RATE_LIMIT_PER_MINUTE,
            PUBLIC_API_RATE_LIMIT_WINDOW_MIN
        );
        if (!$ok) {
            http_response_code(429);
            header('Retry-After: 60');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Rate limit exceeded']);
            exit;
        }
    }

    // === Layer 5: Cache-Header ===
    header('Cache-Control: public, max-age=' . PUBLIC_API_CACHE_MAX_AGE_SEC);

    return [
        'shop_id'  => $shop['shop_id'],
        'shop_row' => $shop,
    ];
}
