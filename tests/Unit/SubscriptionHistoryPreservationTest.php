<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : conservation d'historique à la souscription (ACC-001)
 *
 * Cahier des charges : "Une société non abonnée peut être créée, liée à
 * une facture/devis/commande, recevoir un email et devenir abonnée sans
 * perte d'historique." Un audit du 2026-06-25 avait trouvé que créer un
 * abonnement et créer l'espace applicatif correspondant étaient deux
 * actions manuelles distinctes (deux formulaires séparés), sans garantie
 * qu'elles soient faites ensemble, et sans aucune preuve visible que
 * l'historique préexistant (relations, contacts, emails reçus) restait
 * accessible.
 */

$root = dirname(__DIR__, 2);

$model = file_get_contents($root . '/Modules/Abonnements/Models/AbonnementModel.php') ?: '';
$service = file_get_contents($root . '/Modules/Abonnements/Services/AbonnementService.php') ?: '';
$controller = file_get_contents($root . '/Modules/Abonnements/Controllers/AbonnementController.php') ?: '';
$vue = file_get_contents($root . '/Modules/Abonnements/Views/souscrire.php') ?: '';
$urls = file_get_contents($root . '/config/urls.php') ?: '';

assert($model !== '' && $service !== '' && $controller !== '' && $vue !== '', 'Fichiers du module Abonnements introuvables.');

// La souscription doit etre atomique (abonnement + espace dans la meme transaction).
assert(
    (bool) preg_match('/function souscrireSociete\([^)]*\)[^{]*\{.*?beginTransaction\(\).*?enregistrerAbonnement\(.*?enregistrerEspace\(.*?commit\(\)/s', $model),
    'AbonnementModel::souscrireSociete() doit creer abonnement+espace dans une seule transaction.'
);
assert(
    str_contains($model, 'rollback()'),
    'souscrireSociete() doit annuler la transaction en cas d\'erreur partielle.'
);

// L'historique doit etre mesurable explicitement (preuve qu'il n'est pas perdu).
foreach (['relations', 'contacts', 'emails_recus'] as $champ) {
    assert(str_contains($model, "'{$champ}'"), "historiqueConserve() doit exposer le champ {$champ}.");
}
assert(
    str_contains($model, 'rso_societe_source_id = :id OR rso_societe_cible_id = :id2'),
    'historiqueConserve() doit compter les relations societe dans les deux sens (source et cible).'
);

// La souscription doit etre journalisee.
assert(
    str_contains($service, "'societe.souscrire'"),
    'AbonnementService::souscrireSociete() doit journaliser l\'action societe.souscrire.'
);

// Route et vue exposees, protegees CSRF.
assert(
    str_contains($urls, "'POST /abonnements/souscrire'") && str_contains($urls, "'csrf'"),
    'La route POST de souscription doit etre protegee CSRF.'
);
assert(
    str_contains($vue, 'Historique conservé'),
    'La vue de souscription doit afficher explicitement l\'historique conserve.'
);

echo "SubscriptionHistoryPreservationTest SUCCESS\n";
