<?php
declare(strict_types=1);

// Sporeprint — gemeinsame Helper-Funktionen (SSOT).
//
// API-Response-Pattern uebernommen aus production-app/src/includes/helpers.php
// (Pre-Check A3, A4) — angepasst fuer Sporeprints API-Konvention:
//   - Public API: pures Reviews-Array (kein Envelope) → jsonResponse()
//   - Admin API: {ok, data} / {ok, error} → apiSuccess() / apiError()
// Siehe docs/ARCHITEKTUR.md → "API-Response-Konvention".

/**
 * Sendet JSON-Response mit korrektem Header und beendet die Ausfuehrung.
 *
 * JSON_INVALID_UTF8_SUBSTITUTE: ersetzt ungueltige Bytes durch U+FFFD,
 * statt dass json_encode leer zurueckkommt (z.B. bei Binary-Feldern).
 */
function jsonResponse($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
        | JSON_INVALID_UTF8_SUBSTITUTE
        | JSON_PARTIAL_OUTPUT_ON_ERROR
    );
    exit;
}

/**
 * Admin-API-Erfolgsantwort. Wrappt $data in {ok: true, data: ...}.
 *
 * Nur fuer Admin-Endpoints. Public-API nutzt jsonResponse() direkt
 * mit purem Array — siehe docs/ARCHITEKTUR.md.
 */
function apiSuccess($data = null, int $status = 200): void
{
    jsonResponse(['ok' => true, 'data' => $data], $status);
}

/**
 * Admin-API-Fehlerantwort. Wrappt $message in {ok: false, error: "<msg>"}.
 *
 * HTTP-Status sollte semantisch passen: 400 Client-Fehler, 401 nicht
 * eingeloggt, 403 Forbidden, 404 nicht gefunden, 409 Konflikt, 500
 * Server-Fehler. Default 400.
 *
 * $message wird vom Frontend direkt als Toast-Text angezeigt — daher
 * kurz und nutzer-verstaendlich (auf Deutsch).
 */
function apiError(string $message, int $status = 400): void
{
    jsonResponse(['ok' => false, 'error' => $message], $status);
}

/**
 * Konvertiert IPv4- oder IPv6-String in 16-Byte-Binary fuer rate_limits.ip_address.
 *
 * IPv4 wird als IPv4-mapped-IPv6 gespeichert (16 Byte Layout
 * konsistent ueber alle Eintraege). Inverse Operation: inet_ntop().
 *
 * Bei ungueltigem Input → throws RuntimeException.
 */
function binaryIp(string $ipString): string
{
    $packed = @inet_pton($ipString);
    if ($packed === false) {
        throw new RuntimeException("Ungueltige IP-Adresse: {$ipString}");
    }
    // IPv4 (4 Byte) auf IPv4-mapped-IPv6 (16 Byte) padden
    if (strlen($packed) === 4) {
        $packed = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff" . $packed;
    }
    return $packed;
}

/**
 * Liest die echte Client-IP aus dem Request — beruecksichtigt typische
 * Reverse-Proxy-Header. Auf Server Profis cPanel sollte normalerweise
 * REMOTE_ADDR direkt korrekt sein (kein Cloudflare davor).
 *
 * Reihenfolge: HTTP_X_FORWARDED_FOR (erstes IP) → HTTP_X_REAL_IP → REMOTE_ADDR.
 */
function clientIp(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($list[0]);
    }
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return $_SERVER['HTTP_X_REAL_IP'];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// =====================================================================
// FORMAT-HELPER (SSOT — siehe docs/DESIGN-SYSTEM.md "Format-Standards")
//
// Pattern aus production-app/src/includes/helpers.php uebernommen
// (Pre-Check A3 erweitert um diese Helper). Konvention:
//   - Niemals date() / strftime() / toLocaleString() direkt im View-Code
//   - Niemals number_format() direkt im View-Code
//   - Immer formatDate() / humanTimeDiff() benutzen
//
// JS-Pendants in src/admin/assets/format.js — beide muessen synchron bleiben.
// =====================================================================

/**
 * Datum formatieren — TT.MM.JJJJ oder TT.MM.JJJJ, HH:MM.
 * Akzeptiert ISO-Strings ('2026-04-12', '2026-04-12 14:30:00', '2026-04-12T14:30:00').
 * Bei null/leer/ungueltig: "–" (Em-Dash).
 */
function formatDate(?string $iso, bool $mitUhrzeit = false): string
{
    if ($iso === null || $iso === '') return '–';
    $ts = strtotime($iso);
    if ($ts === false) return '–';
    return $mitUhrzeit
        ? date('d.m.Y, H:i', $ts)
        : date('d.m.Y', $ts);
}

/**
 * Menschenlesbare Zeitdifferenz: "gerade eben", "vor 5 Min", "vor 3h", "vor 2 Tagen".
 *
 * SYNC-PAIR: JS-Pendant in src/admin/assets/format.js → AppFormat.relative().
 * Beide muessen synchron bleiben (gleiche Schwellwerte, gleiche Labels).
 */
function humanTimeDiff(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) return '–';
    $diff = time() - $ts;
    if ($diff < 60) return 'gerade eben';
    if ($diff < 3600) return 'vor ' . (int) ($diff / 60) . ' Min';
    if ($diff < 86400) return 'vor ' . (int) ($diff / 3600) . 'h';
    $days = (int) ($diff / 86400);
    return 'vor ' . $days . ($days === 1 ? ' Tag' : ' Tagen');
}
