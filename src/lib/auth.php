<?php
declare(strict_types=1);

// Sporeprint — Auth-Layer (Single-Admin v1).
//
// Pattern übernommen aus production-app/src/includes/auth.php (Pre-Check A6),
// vereinfacht: kein User-Stamm in DB, kein Role/Permission-System.
// Single-Admin-Credentials liegen in config/.env als ADMIN_USER + ADMIN_PASSWORD_HASH.
// User-Tabelle kommt erst in Phase 3, falls mehrere Admins gleichzeitig schreiben.

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// Session sicher konfigurieren BEVOR session_start()
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = ($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_name('sporeprint_admin');
    session_start();
}

/**
 * Versucht Login mit User+Passwort gegen .env-Credentials.
 * Bei Erfolg wird Session-Daten gesetzt + session_regenerate_id().
 * Generischer Returnwert — der Aufrufer zeigt bei false einen
 * generischen Fehler ("Login nicht möglich"), niemals "User existiert nicht"
 * oder "Passwort falsch" (Info-Disclosure-Schutz).
 */
function attemptLogin(string $username, string $password): bool
{
    loadEnv();
    $expectedUser = $_ENV['ADMIN_USER'] ?? '';
    $expectedHash = $_ENV['ADMIN_PASSWORD_HASH'] ?? '';

    if ($expectedUser === '' || $expectedHash === '') {
        // .env nicht konfiguriert — kein Login möglich. Bewusst silent (kein Hint nach außen).
        return false;
    }

    // Konstante Laufzeit gegen Timing-Side-Channel: hash_equals + password_verify auch
    // dann ausführen wenn der Username nicht passt.
    $userMatches = hash_equals($expectedUser, $username);
    $passwordMatches = password_verify($password, $expectedHash);

    if (!$userMatches || !$passwordMatches) {
        return false;
    }

    // Session-Fixation-Schutz: ID rotieren nach erfolgreichem Login
    session_regenerate_id(true);
    $_SESSION['admin_user'] = $username;
    $_SESSION['logged_in_at'] = time();
    return true;
}

/**
 * Logout: Session zerstören.
 */
function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'] ?? '',
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Bricht Request ab wenn nicht eingeloggt.
 * Bei API-Request (Pfad startet mit /api/): 401 + JSON-Fehler.
 * Sonst: Redirect auf Login-Seite.
 */
function requireLogin(): void
{
    if (empty($_SESSION['admin_user'])) {
        if (isApiRequest()) {
            apiError('Nicht eingeloggt', 401);
        }
        header('Location: /index.php');
        exit;
    }
}

/**
 * Liefert aktuell eingeloggten User (Username) oder null.
 */
function currentUser(): ?string
{
    return $_SESSION['admin_user'] ?? null;
}

/**
 * True wenn aktueller Request ein API-Aufruf ist (Pfad beginnt mit /api/).
 */
function isApiRequest(): bool
{
    return str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
}

// === CSRF-Token ===
// Einfaches Session-basiertes CSRF-Pattern. Token wird einmal pro Session
// generiert und in Forms eingefügt; bei POST-Verarbeitung verglichen via hash_equals.

/**
 * Gibt das aktuelle CSRF-Token zurück (generiert eines wenn keines existiert).
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Prüft das CSRF-Token aus dem Request gegen Session.
 * Bei Mismatch: 403 + sofort Exit.
 */
function requireCsrfToken(): void
{
    $sent = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if ($expected === '' || !hash_equals($expected, $sent)) {
        if (isApiRequest()) {
            apiError('Ungültiges CSRF-Token', 403);
        }
        http_response_code(403);
        echo '<h1>403 — Ungültiges CSRF-Token</h1>';
        echo '<p><a href="/index.php">Zurück zum Login</a></p>';
        exit;
    }
}
