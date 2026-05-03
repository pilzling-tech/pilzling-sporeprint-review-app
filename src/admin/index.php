<?php
declare(strict_types=1);

// Sporeprint Admin — Login-Seite.
// Wenn schon eingeloggt: Redirect auf Dashboard.
// Bei POST: CSRF-Check, dann attemptLogin().

require_once __DIR__ . '/../lib/auth.php';

// Schon eingeloggt? → Dashboard
if (currentUser() !== null) {
    header('Location: /dashboard.php');
    exit;
}

$loginError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username !== '' && $password !== '') {
        if (attemptLogin($username, $password)) {
            header('Location: /dashboard.php');
            exit;
        }
    }

    // Generischer Error — niemals verraten ob User oder Passwort falsch war
    $loginError = 'Login nicht möglich. Prüfe Benutzername und Passwort.';
}

$csrfToken = csrfToken();
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — Login</title>
<link rel="stylesheet" href="/assets/admin.css">
</head>
<body class="login-page">

<main class="login-card">
    <h1 class="login-card__brand">Sporeprint</h1>
    <p class="login-card__subtitle">Admin-Bereich</p>

    <?php if ($loginError !== null): ?>
        <div class="callout callout--error" role="alert">
            <?= htmlspecialchars($loginError) ?>
        </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <div class="form-row">
            <label for="username">Benutzername</label>
            <input type="text" id="username" name="username" autofocus required autocomplete="username">
        </div>

        <div class="form-row">
            <label for="password">Passwort</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-primary btn--block">Einloggen</button>
        </div>
    </form>

    <p class="login-card__footer">Sporeprint &middot; intern</p>
</main>

</body>
</html>
