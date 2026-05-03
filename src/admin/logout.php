<?php
declare(strict_types=1);

// Sporeprint Admin — Logout-Endpoint.
// Zerstoert Session, Redirect zu Login.

require_once __DIR__ . '/../lib/auth.php';

logout();
header('Location: /index.php');
exit;
