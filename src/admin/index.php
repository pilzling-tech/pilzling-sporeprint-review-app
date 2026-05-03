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
    $loginError = 'Login nicht moeglich. Pruefe Benutzername und Passwort.';
}

$csrfToken = csrfToken();
?><!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint Admin — Login</title>
<style>
:root {
    --color-bg: #f5f3ee;
    --color-surface: #ffffff;
    --color-text: #2a2a2a;
    --color-muted: #6c6c6c;
    --color-accent: #7a4f1a;
    --color-error: #b53f2c;
    --color-border: #d8d3c7;
    --space-1: 0.5rem;
    --space-2: 1rem;
    --space-3: 1.5rem;
    --space-4: 2rem;
    --radius: 6px;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
    background: var(--color-bg);
    color: var(--color-text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-3);
    line-height: 1.5;
}
.login-card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    padding: var(--space-4);
    width: 100%;
    max-width: 360px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
h1 {
    margin: 0 0 var(--space-1) 0;
    font-size: 1.4rem;
    color: var(--color-accent);
}
.subtitle {
    margin: 0 0 var(--space-3) 0;
    font-size: 0.85rem;
    color: var(--color-muted);
}
label {
    display: block;
    font-size: 0.85rem;
    margin-bottom: 0.3rem;
    color: var(--color-muted);
}
input[type="text"],
input[type="password"] {
    width: 100%;
    padding: 0.6rem 0.8rem;
    border: 1px solid var(--color-border);
    border-radius: var(--radius);
    font-size: 1rem;
    background: var(--color-bg);
    margin-bottom: var(--space-2);
}
input[type="text"]:focus,
input[type="password"]:focus {
    outline: 2px solid var(--color-accent);
    outline-offset: -1px;
    border-color: var(--color-accent);
}
button {
    width: 100%;
    padding: 0.7rem;
    background: var(--color-accent);
    color: white;
    border: none;
    border-radius: var(--radius);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: var(--space-1);
}
button:hover { filter: brightness(1.05); }
.error {
    background: #fdecea;
    border: 1px solid #f5c6c0;
    color: var(--color-error);
    padding: 0.6rem 0.8rem;
    border-radius: var(--radius);
    margin-bottom: var(--space-2);
    font-size: 0.9rem;
}
.footer {
    margin-top: var(--space-3);
    text-align: center;
    font-size: 0.75rem;
    color: var(--color-muted);
}
</style>
</head>
<body>
<main class="login-card">
    <h1>Sporeprint</h1>
    <p class="subtitle">Admin-Bereich</p>
    <?php if ($loginError): ?>
        <div class="error"><?= htmlspecialchars($loginError) ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <label for="username">Benutzername</label>
        <input type="text" id="username" name="username" autofocus required autocomplete="username">

        <label for="password">Passwort</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <button type="submit">Einloggen</button>
    </form>
    <p class="footer">Sporeprint &middot; intern</p>
</main>
</body>
</html>
