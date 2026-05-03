<?php
declare(strict_types=1);

/**
 * EINMALIGES Setup-Skript: Bcrypt-Hash fuer Admin-Passwort generieren.
 *
 * NACH ERFOLGREICHEM LOGIN-SETUP DIESE DATEI LOESCHEN!
 * Sicherheits-Schichten die hier greifen:
 *   - cPanel-Verzeichnisschutz (admin-sporeprint.pilzling.eu)
 *   - .htaccess-Hardening im DocRoot
 * Trotzdem: nach Nutzung weg damit, weil ein laufendes Hash-Tool ist
 * unnoetige Angriffsflaeche.
 */

$generatedHash = null;
$inputPassword = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputPassword = $_POST['password'] ?? '';
    if ($inputPassword !== '') {
        $generatedHash = password_hash($inputPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint — Hash-Generator (Setup)</title>
<style>
body { font-family: system-ui, sans-serif; max-width: 640px; margin: 3rem auto; padding: 0 1rem; line-height: 1.6; }
h1 { color: #7a4f1a; }
.warn { background: #fff8e1; border: 1px solid #f4d27a; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
.warn strong { color: #b53f2c; }
input[type="text"] { width: 100%; padding: 0.6rem; font-size: 1rem; border: 1px solid #d8d3c7; border-radius: 4px; margin-bottom: 1rem; box-sizing: border-box; }
button { padding: 0.7rem 1.2rem; background: #7a4f1a; color: white; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; }
.result { background: #f5f3ee; border: 1px solid #d8d3c7; padding: 1rem; border-radius: 6px; margin-top: 1.5rem; }
.result code { display: block; word-break: break-all; font-family: ui-monospace, Consolas, monospace; padding: 0.6rem; background: white; border-radius: 4px; margin-top: 0.5rem; }
.muted { color: #6c6c6c; font-size: 0.9rem; }
</style>
</head>
<body>

<h1>Sporeprint — Hash-Generator</h1>

<div class="warn">
<strong>NUR FUER SETUP!</strong> Diese Datei nach erfolgreichem Login wieder loeschen
(<code>src/admin/_hash-generator.php</code> aus dem Repo entfernen + WinSCP-Sync laufen lassen,
oder direkt im cPanel-Dateimanager loeschen).
</div>

<form method="post" autocomplete="off">
    <label for="password">Klartext-Passwort (das spaeter in Bitwarden gespeichert wird):</label>
    <input type="text" id="password" name="password" autofocus value="<?= htmlspecialchars($inputPassword ?? '') ?>">
    <button type="submit">Hash erzeugen</button>
</form>

<?php if ($generatedHash !== null): ?>
<div class="result">
    <strong>Bcrypt-Hash (Cost 12):</strong>
    <code><?= htmlspecialchars($generatedHash) ?></code>
    <p class="muted">Diesen Hash exakt so in <code>config/.env</code> bei <code>ADMIN_PASSWORD_HASH=</code> einsetzen — ohne Anfuehrungszeichen, ohne Leerzeichen davor/danach.</p>
    <p class="muted">Verifizierung: <?= password_verify($inputPassword, $generatedHash) ? '✅ password_verify ist konsistent' : '❌ FEHLER bei Verifizierung' ?></p>
</div>
<?php endif; ?>

<p class="muted" style="margin-top: 3rem;">
Sobald Hash in <code>.env</code> eingetragen ist und Login funktioniert: <strong>diese Datei loeschen!</strong>
</p>

</body>
</html>
