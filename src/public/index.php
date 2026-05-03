<?php
declare(strict_types=1);

// Sporeprint Public — Platzhalter-Index für sporeprint.pilzling.eu.
// Diese Subdomain liefert primär das widget.js + /api/reviews aus —
// kein menschliches Frontend nötig. Diese Seite ist ein Stub damit
// direkter Subdomain-Aufruf nicht 403 wirft.

http_response_code(200);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Sporeprint</title>
<style>
/* === PUBLIC-STUB INLINE-CSS — bewusst kein Cross-Subdomain-Asset-Sharing ===
 *
 * Bei Änderungen in admin/assets/tokens.css die hier genutzten Werte
 * manuell nachziehen. Public-Stub wird selten geändert, daher keine
 * eigene CSS-Datei (Aufwand > Nutzen).
 *
 * Quelle der Werte: src/admin/assets/tokens.css :root
 */
body {
    font-family: "Rubik", system-ui, -apple-system, "Segoe UI", sans-serif;
    background: #F2F0ED;        /* --color-cream */
    color: #151824;             /* --color-dark */
    max-width: 640px;
    margin: 4rem auto;
    padding: 0 1rem;
    line-height: 1.5;
}
h1 {
    font-size: 1.6rem;
    color: #151824;
    margin: 0 0 1rem 0;
}
p {
    margin: 0 0 1rem 0;
}
a {
    color: #5A74B8;             /* --color-accent-blau-dark */
    text-decoration: underline;
    text-underline-offset: 2px;
}
a:hover {
    color: #F85B05;             /* --color-primary */
}
</style>
</head>
<body>
<h1>Sporeprint</h1>
<p>Diese Domain liefert das Sporeprint-Widget und die Public-API für Reviews
in den Pilzling-Shops. Sie ist keine eigenständige Website.</p>
<p>Mehr Infos: <a href="https://pilzling.shop">pilzling.shop</a></p>
</body>
</html>
