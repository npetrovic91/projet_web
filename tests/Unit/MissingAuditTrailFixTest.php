<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : trous d'audit sur des mutations critiques (2.3)
 *
 * Audit du 2026-06-27 (convergence gestion d'erreurs) : plusieurs modèles
 * mutaient des données sensibles sans laisser AUCUNE trace dans
 * sav_journaux_audit — notamment le workflow de validation des standards
 * (qui a approuvé/rejeté quelle version), les documents juridiques, les
 * fichiers, les relations/marques/types de sociétés, et les coordonnées
 * bancaires (sociétés ET utilisateurs). Ce dernier point exige une
 * attention particulière : ne JAMAIS journaliser l'IBAN/RIB complet, donc
 * ce test vérifie aussi l'absence d'une telle fuite.
 */

$root = dirname(__DIR__, 2);

$checks = [
    'Modules/Standards/Models/StandardModel.php' => ['journaliser(', 'version_standard.valider', 'version_standard.rejeter'],
    'Modules/LegalDocuments/Models/LegalDocumentModel.php' => ['journaliser(', 'document_juridique.creer'],
    'Modules/Files/Models/FileModel.php' => ['journaliser(', 'fichier.upload', 'fichier.supprimer'],
    'Modules/Society/Models/ModeleRelationSociete.php' => ['journaliser(', 'relation_societe.creer'],
    'Modules/Society/Models/ModeleMarqueSociete.php' => ['journaliser(', 'marque_societe.attacher'],
    'Modules/Society/Models/ModeleTypeSociete.php' => ['journaliser(', 'type_societe.creer'],
    'Modules/Companies/Models/SocieteComplementModel.php' => ['journaliser(', 'compte_bancaire.creer', 'iban_suffixe'],
    'Modules/Users/Models/UserComplementModel.php' => ['journaliser(', 'compte_bancaire.creer', 'iban_suffixe'],
];

foreach ($checks as $path => $needles) {
    $source = file_get_contents($root . '/' . $path) ?: '';
    assert($source !== '', $path . ' introuvable.');
    foreach ($needles as $needle) {
        assert(str_contains($source, $needle), $path . ' doit contenir "' . $needle . '" (audit manquant non corrigé).');
    }
}

// Aucune donnée bancaire complète ne doit jamais être passée à journaliser().
foreach (['Modules/Companies/Models/SocieteComplementModel.php', 'Modules/Users/Models/UserComplementModel.php'] as $path) {
    $source = file_get_contents($root . '/' . $path) ?: '';
    assert(
        !preg_match("/journaliser\([^)]*'iban'\s*=>/", $source),
        $path . ' ne doit jamais journaliser l\'IBAN complet, seulement un suffixe.'
    );
}

echo "MissingAuditTrailFixTest SUCCESS\n";
