<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : ORDER BY injectable dans BaseModel::findAll() (HIGH-2)
 *
 * Audit sécurité du 2026-06-26 : $orderBy était concaténé directement
 * dans la requête SQL sans validation. Corrigé via quoteIdentifier()
 * (format strict) + whitelist optionnelle.
 */

$root = dirname(__DIR__, 2);
$baseModel = file_get_contents($root . '/Core/Model/BaseModel.php') ?: '';

assert($baseModel !== '', 'BaseModel.php introuvable.');
assert(
    !preg_match('/\$sql \.= " ORDER BY \{\$orderBy\}"/', $baseModel),
    'findAll() ne doit plus concatener $orderBy directement dans la requete.'
);
assert(
    str_contains($baseModel, 'quoteIdentifier($orderBy)'),
    'findAll() doit valider $orderBy via quoteIdentifier() avant de l\'inserer dans ORDER BY.'
);
assert(
    str_contains($baseModel, 'allowedColumns !== [] && !in_array($orderBy, $allowedColumns, true)'),
    'findAll() doit supporter une whitelist explicite de colonnes autorisees.'
);

echo "OrderByInjectionFixTest SUCCESS\n";
