<?php
// Sporeprint Admin — Platzhalter-Index
// Wird in Phase 3 zur eigentlichen Login-Seite. Aktuell nur Test-Stub
// damit der Verzeichnisschutz greifen kann (Apache wirft 403 wenn keine
// Index-Datei + Options -Indexes aktiv ist).

declare(strict_types=1);

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Sporeprint Admin — Setup</title>
<style>
body { font-family: system-ui, -apple-system, sans-serif; max-width: 640px; margin: 4rem auto; padding: 0 1rem; line-height: 1.6; color: #222; }
h1 { font-size: 1.5rem; }
code { background: #f4f4f4; padding: 0.2rem 0.4rem; border-radius: 3px; font-size: 0.9em; }
.ok { color: #0a7c2e; }
</style>
</head>
<body>
<h1>Sporeprint Admin</h1>
<p class="ok">✓ Server erreichbar, Verzeichnisschutz hat dich durchgelassen.</p>
<p>Stand: <?= date('Y-m-d H:i:s') ?> (PHP <?= PHP_VERSION ?>)</p>
<p>Diese Seite ist ein Platzhalter aus Phase 0 Foundation. Die echte
Admin-Login-Seite wird in Phase 3 entwickelt.</p>
</body>
</html>
