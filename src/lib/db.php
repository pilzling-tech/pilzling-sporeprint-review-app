<?php
declare(strict_types=1);

// Sporeprint — DB-Zugriff (SSOT).
// Wrapper um config/database.php damit alle Endpoints uniformiert über
// lib/ gehen (Konvention aus CLAUDE.md "Harte Regeln" → SSOT-Prinzip Code).
//
// Pattern übernommen aus production-app/src/config/database.php (Pre-Check A1)
// und auf englische Identifier angepasst.

require_once __DIR__ . '/../config/database.php';

/**
 * Convenience-Wrapper für prepared SELECT-Queries.
 * Gibt alle Zeilen zurück (FETCH_ASSOC).
 */
function dbQueryAll(string $sql, array $params = []): array
{
    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Convenience-Wrapper für prepared SELECT-Queries die genau eine Zeile
 * (oder keine) erwarten. Gibt das Row-Array oder null zurück.
 */
function dbQueryOne(string $sql, array $params = []): ?array
{
    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row !== false ? $row : null;
}

/**
 * Convenience-Wrapper für prepared INSERT/UPDATE/DELETE.
 * Gibt die affected-row-Anzahl zurück.
 */
function dbExec(string $sql, array $params = []): int
{
    $stmt = getDb()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}
