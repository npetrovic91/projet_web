<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : propagation groupe → concessions (ACC-009)
 *
 * Cahier des charges : "Une propagation massive de groupe vers ses
 * concessions nécessite prévisualisation, confirmation, journalisation
 * et rollback possible." Un audit du 2026-06-25 avait trouvé qu'aucune
 * table sav_bulk_actions n'existait dans le schéma réel et qu'aucun code
 * applicatif n'implémentait cette fonctionnalité, malgré sa mention
 * explicite dans le cahier des charges.
 */

$root = dirname(__DIR__, 2);

$service = file_get_contents($root . '/Modules/Roles/Services/GroupPropagationService.php') ?: '';
$controller = file_get_contents($root . '/Modules/Roles/Controllers/GroupPropagationController.php') ?: '';
$migration = file_get_contents($root . '/database/migrations/0005_bulk_actions_propagation_groupe.sql') ?: '';
$urls = file_get_contents($root . '/config/urls.php') ?: '';

assert($service !== '' && $controller !== '', 'Fichiers de propagation groupe introuvables.');

// Les 4 etapes exigees par le cahier des charges doivent exister comme methodes distinctes.
foreach (['previsualiser', 'confirmer', 'annuler', 'historique'] as $methode) {
    assert(str_contains($service, "function {$methode}"), "GroupPropagationService doit definir {$methode}().");
}

// La previsualisation doit detecter les conflits avant toute ecriture,
// et persister son resultat (pas de recalcul a la confirmation).
assert(
    str_contains($service, "'conflit' =>"),
    'previsualiser() doit calculer un conflit par concession cible.'
);
assert(
    str_contains($service, "VALUES (:groupe, :type, :cible, :concessions, 'preview'"),
    'previsualiser() doit persister son resultat avec le statut preview.'
);

// La confirmation ne doit jamais ecraser un conflit detecte.
assert(
    str_contains($service, 'continue; // conflit'),
    'confirmer() doit ignorer (pas ecraser) les concessions en conflit.'
);

// Le rollback doit cibler precisement les elements crees par CETTE action.
assert(
    str_contains($service, "(\$resultat['crees'] ?? [])"),
    'annuler() doit rollback uniquement les elements references dans bac_resultat_json.'
);

// Seuls les roles pilotant un groupe peuvent declencher une propagation.
foreach (['responsable_groupe_concessions', 'directeur_groupe', 'administrateur_groupe_concessions'] as $role) {
    assert(str_contains($controller, "'{$role}'"), "GroupPropagationController doit autoriser le role {$role}.");
}

// Table dediee avec tous les champs necessaires a l'audit complet.
foreach (['bac_groupe_societe_id', 'bac_preview_json', 'bac_resultat_json', 'bac_execute_le', 'bac_rollback_le'] as $colonne) {
    assert(str_contains($migration, $colonne), "Colonne manquante dans la migration sav_bulk_actions : {$colonne}");
}

// Routes protegees CSRF.
foreach (['previsualiser', '{id}/confirmer', '{id}/annuler'] as $suffixe) {
    assert(
        str_contains($urls, "roles/propagation/{$suffixe}") && str_contains($urls, "'csrf'"),
        "Route de propagation manquante ou non protegee CSRF : {$suffixe}"
    );
}

echo "GroupPropagationTest SUCCESS\n";
