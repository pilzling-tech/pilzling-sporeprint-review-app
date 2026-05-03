<?php
declare(strict_types=1);

// Sporeprint — DB-Zugriff (SSOT).
// Wrapper um config/database.php damit alle Endpoints uniformiert ueber
// lib/ gehen (Konvention aus CLAUDE.md "Harte Regeln" → SSOT-Prinzip Code).
//
// Pattern uebernommen aus production-app/src/config/database.php (Pre-Check A1)
// und auf englische Identifier angepasst.

require_once __DIR__ . '/../config/database.php';

/**
 * Convenience-Wrapper fuer prepared SELECT-Queries.
 * Gibt alle Zeilen zurueck (FETCH_ASSOC).
 */
function dbQueryAll(string $sql, array $params = []): array
{
    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Convenience-Wrapper fuer prepared SELECT-Queries die genau eine Zeile
 * (oder keine) erwarten. Gibt das Row-Array oder null zurueck.
 */
function dbQueryOne(string $sql, array $params = []): ?array
{
    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row !== false ? $row : null;
}

/**
 * Convenience-Wrapper fuer prepared INSERT/UPDATE/DELETE.
 * Gibt die affected-row-Anzahl zurueck.
 */
function dbExec(string $sql, array $params = []): int
{
    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}
