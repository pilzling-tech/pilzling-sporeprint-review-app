<?php
declare(strict_types=1);

// Sporeprint Admin-API: Review-Visibility-Aktionen (hide/show/flag).
// POST mit JSON-Body { review_id, action }, CSRF via X-CSRF-Token-Header.

require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/helpers.php';

requireLogin();
requireCsrfToken();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    apiError('Methode nicht erlaubt', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) apiError('Ungültiger JSON-Body');

$reviewId = (int) ($input['review_id'] ?? 0);
$action = $input['action'] ?? '';

if ($reviewId <= 0) apiError('review_id fehlt');

$visibilityMap = [
    'hide' => 'hidden',
    'show' => 'visible',
    'flag' => 'flagged',
];
if (!isset($visibilityMap[$action])) apiError('Unbekannte Aktion');

$rowsAffected = dbExec(
    "UPDATE reviews SET visibility = ? WHERE review_id = ?",
    [$visibilityMap[$action], $reviewId]
);

if ($rowsAffected === 0) apiError('Review nicht gefunden', 404);

apiSuccess(['review_id' => $reviewId, 'visibility' => $visibilityMap[$action]]);
