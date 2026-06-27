<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$migration = file_get_contents($root . '/database/seeds/2026_06_03_lot35_demo_reseau_automobile.sql');

$requiredConstructors = [
    'BMW_GROUP',
    'VOLKSWAGEN_AG',
    'MERCEDES_BENZ_GROUP',
    'STELLANTIS',
    'FIAT_GROUPE',
    'RENAULT_GROUP',
];

$requiredBrands = [
    'BMW',
    'VOLKSWAGEN',
    'AUDI',
    'SEAT',
    'SKODA',
    'RENAULT',
    'PEUGEOT',
    'CITROEN',
    'OPEL',
    'DS_AUTOMOBILES',
    'FIAT',
    'ALFA_ROMEO',
    'MERCEDES',
];

$requiredGroups = [
    'AUTO_AVENUE_GROUPE',
    'PREMIUM_MOTORS_EST',
    'GARAGE_MULTIMARQUE_PRO',
];

$requiredConcessions = [
    'AUTO_AVENUE_PARIS',
    'AUTO_AVENUE_LYON',
    'AUTO_AVENUE_MARSEILLE',
    'AUTO_AVENUE_LILLE',
    'PREMIUM_MOTORS_STRASBOURG',
    'PREMIUM_MOTORS_METZ',
    'GARAGE_MULTIMARQUE_NANTES',
    'GARAGE_MULTIMARQUE_TOULOUSE',
];

$requiredUsers = [
    'admin.general',
    'responsable.groupe',
    'directeur.paris',
    'responsable.sav',
    'conseiller.service',
    'technicien.diagnostic',
    'gestionnaire.pieces',
    'responsable.garantie',
    'magasinier.lille',
    'conseiller.toulouse',
];

$requiredRoles = [
    'administrateur_general_societe',
    'responsable_groupe_concessions',
    'directeur_concession',
    'responsable_apres_vente',
    'responsable_garantie',
    'conseiller_service',
    'technicien_sav',
    'gestionnaire_pieces',
];

$requiredFunctions = [
    'DIRECTION_GENERALE',
    'DIRECTION_CONCESSION',
    'RESPONSABLE_APRES_VENTE',
    'RESPONSABLE_GARANTIE',
    'CONSEILLER_SERVICE',
    'TECHNICIEN_DIAGNOSTIC',
    'MAGASINIER',
    'GESTIONNAIRE_PIECES',
];

foreach ([
    'constructeur' => $requiredConstructors,
    'marque' => $requiredBrands,
    'groupe' => $requiredGroups,
    'concession' => $requiredConcessions,
    'utilisateur' => $requiredUsers,
    'role' => $requiredRoles,
    'fonction' => $requiredFunctions,
] as $kind => $codes) {
    foreach ($codes as $code) {
        assert(str_contains($migration, "'{$code}'"), "{$kind} absent du lot35 : {$code}");
    }
}

assert(str_contains($migration, "constructeur.soc_code = 'VOLKSWAGEN_AG' AND marque.soc_code IN ('VOLKSWAGEN', 'AUDI', 'SEAT', 'SKODA')"));
assert(str_contains($migration, "constructeur.soc_code = 'STELLANTIS' AND marque.soc_code IN ('PEUGEOT', 'CITROEN', 'OPEL', 'DS_AUTOMOBILES')"));
assert(str_contains($migration, "constructeur.soc_code = 'FIAT_GROUPE' AND marque.soc_code IN ('FIAT', 'ALFA_ROMEO')"));
assert(!str_contains($migration, "constructeur.soc_code = 'FIAT_GROUPE' AND marque.soc_code IN ('PEUGEOT', 'CITROEN')"));
assert(str_contains($migration, 'sav_representations_marques_societes'));
assert(str_contains($migration, 'sav_roles_contextuels_utilisateurs'));
assert(str_contains($migration, 'sav_fonctions_utilisateurs'));
assert(str_contains($migration, 'DemoAutosav!2026'));
assert(str_contains($migration, '$argon2id$v=19$m=65536,t=4,p=1$eG91d1ZxUGZ1LjV1MDFPVw$NfTUFJpcpH6gZegE8mJZ0BNRgK2O+KZUXTuJ/pI1784'));
assert(password_verify('DemoAutosav!2026', '$argon2id$v=19$m=65536,t=4,p=1$eG91d1ZxUGZ1LjV1MDFPVw$NfTUFJpcpH6gZegE8mJZ0BNRgK2O+KZUXTuJ/pI1784'));
assert(str_contains($migration, 'uti_mot_de_passe_hash = VALUES(uti_mot_de_passe_hash)'));
assert(str_contains($migration, 'uti_doit_changer_mot_de_passe = VALUES(uti_doit_changer_mot_de_passe)'));

echo "DemoAutomotiveNetworkSeedTest SUCCESS\n";
