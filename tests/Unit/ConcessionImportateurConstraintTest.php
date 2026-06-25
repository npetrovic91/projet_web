<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : importateur_id obligatoire sur une concession
 *
 * Cahier des charges (organisation_metier.regles) : "Chaque concession
 * doit avoir un importateur_id obligatoire." Un audit du 2026-06-25 avait
 * trouvé que cette contrainte n'existait dans AUCUN formulaire ni
 * validation — même les concessions démo (Auto Avenue Paris, etc.)
 * n'avaient pas ce lien. Corrigé : champ formulaire + validation
 * backend + relation persistée (sav_relations_societes,
 * 'importateur_distribue_concession').
 */

$root = dirname(__DIR__, 2);

$validation = file_get_contents($root . '/Modules/Society/Services/ServiceValidationSocietes.php') ?: '';
$service = file_get_contents($root . '/Modules/Society/Services/ServiceSocietes.php') ?: '';
$modeleRelation = file_get_contents($root . '/Modules/Society/Models/ModeleRelationSociete.php') ?: '';
$formulaire = file_get_contents($root . '/Modules/Society/Views/partials/formulaire.php') ?: '';
$controller = file_get_contents($root . '/Modules/Society/Controllers/SocieteController.php') ?: '';

assert($validation !== '' && $service !== '' && $modeleRelation !== '' && $formulaire !== '' && $controller !== '', 'Fichiers du module Society introuvables.');

// Validation backend : le controle ne doit pas dependre du champ
// formulaire pour exister (defense meme si le champ est absent du POST).
assert(
    str_contains($validation, "doit obligatoirement être rattachée à un importateur"),
    'ServiceValidationSocietes doit rejeter une concession sans importateur_id.'
);
assert(
    str_contains($validation, 'array_intersect($typesIds, self::TYPES_CONCESSION)'),
    'La verification importateur doit etre conditionnee au type concession, pas a tous les types.'
);

// Persistance : la relation doit etre creee/mise a jour a l'enregistrement.
assert(
    str_contains($service, 'definirImportateur'),
    'ServiceSocietes::enregistrer() doit persister la relation importateur->concession.'
);
assert(
    str_contains($modeleRelation, 'importateur_distribue_concession'),
    'ModeleRelationSociete doit utiliser le type de relation importateur_distribue_concession.'
);

// Le formulaire doit exposer le champ, pas juste le backend.
assert(
    str_contains($formulaire, 'name="soc_importateur_id"'),
    'Le formulaire societe doit exposer un champ soc_importateur_id.'
);
assert(
    str_contains($controller, "societesActives('importateur')"),
    'SocieteController doit fournir la liste des importateurs disponibles au formulaire.'
);

echo "ConcessionImportateurConstraintTest SUCCESS\n";
