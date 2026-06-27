<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : séparation migrations structurelles / données démo
 *
 * Audit DevOps du 2026-06-26 : database/migrations/ mélangeait des
 * migrations structurelles réelles (schéma, catalogue de rôles/permissions)
 * avec des données de démonstration (sociétés fictives, comptes avec mots
 * de passe connus publiquement type "Demo2026!"). bin/migrate.php applique
 * TOUT le contenu de database/migrations/*.sql sans distinction : si ce
 * dossier avait été déployé tel quel en production réelle, des comptes
 * démo avec mots de passe publics auraient été créés sur la base client.
 *
 * database/seeds/ est désormais le seul endroit pour les données de
 * démonstration — jamais scanné par bin/migrate.php (qui ne lit que
 * database/migrations/), donc jamais appliqué automatiquement en
 * production.
 */

$root = dirname(__DIR__, 2);
$migrationsDir = $root . '/database/migrations';
$seedsDir = $root . '/database/seeds';

assert(is_dir($migrationsDir), 'database/migrations introuvable.');
assert(is_dir($seedsDir), 'database/seeds introuvable.');

$migrationFiles = array_map('basename', glob($migrationsDir . '/*.sql') ?: []);

// Aucun fichier de donnees demo ne doit se trouver dans migrations/.
foreach (['lot35', 'lot36', 'lot38', 'seed_demo'] as $motifDemo) {
    foreach ($migrationFiles as $fichier) {
        assert(
            !str_contains($fichier, $motifDemo),
            "Fichier de donnees demo trouve dans database/migrations/ (devrait etre dans database/seeds/) : {$fichier}"
        );
    }
}

// Les fichiers structurels/catalogue reels doivent rester dans migrations/.
foreach ([
    '0001_baseline_schema.sql',
    '0002_importateur_distribue_concession.sql',
    '0003_acces_donnees_superadmin.sql',
    '0004_workflow_validation_standards.sql',
    '0005_bulk_actions_propagation_groupe.sql',
    '2026_06_03_lot37_admin_societe_permissions_referentiels.sql',
    '2026_06_05_v431_permissions_modules_sensibles.sql',
] as $attendu) {
    assert(in_array($attendu, $migrationFiles, true), "Migration structurelle manquante : {$attendu}");
}

// Les fichiers de donnees demo doivent exister dans seeds/, pas avoir disparu.
foreach ([
    '2026_06_03_lot35_demo_reseau_automobile.sql',
    '2026_06_03_lot36_reset_demo_passwords.sql',
    '2026_06_03_lot38_contextes_roles_admin_reseau.sql',
    'seed_demo_concession_groupe.sql',
] as $attendu) {
    assert(is_file($seedsDir . '/' . $attendu), "Seed demo manquant dans database/seeds/ : {$attendu}");
}

echo "MigrationsSeedsSeparationTest SUCCESS\n";
