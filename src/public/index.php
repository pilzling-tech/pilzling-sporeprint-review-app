<?php
// Sporeprint Public — Platzhalter-Index
// Wird in Phase 1+2 nicht zu einer richtigen Seite — die Public-Subdomain
// liefert das widget.js + /api/reviews aus, kein menschliches Frontend.
// Dieser Stub verhindert nur 403 bei direktem Subdomain-Aufruf.

declare(strict_types=1);

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Sporeprint</title>
<style>
body { font-family: system-ui, -apple-system, sans-serif; max-width: 640px; margin: 4rem auto; padding: 0 1rem; line-height: 1.6; color: #222; }
h1 { font-size: 1.5rem; }
</style>
</head>
<body>
<h1>Sporeprint</h1>
<p>Diese Domain liefert das Sporeprint-Widget und die Public-API für Reviews
in den Pilzling-Shops. Sie ist keine eigenständige Website.</p>
<p>Mehr Infos: <a href="https://pilzling.shop">pilzling.shop</a></p>
</body>
</html>
