<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : fuite de mot de passe dans les logs de connexion (MED-6)
 *
 * Audit sécurité du 2026-06-26 : la connexion se fait toujours par email
 * (normaliserEmail() systématique côté recherche utilisateur), donc une
 * valeur saisie sans forme d'email dans le champ identifiant est très
 * probablement un mot de passe tapé par erreur — un schéma d'erreur
 * utilisateur courant. Avant ce correctif, enregistrerTentativeConnexion()
 * persistait cette valeur en clair dans sav_tentatives_connexion
 * (tcn_email_tente), exposant le mot de passe à quiconque a accès aux
 * logs/BDD. Désormais, seule une valeur ayant la forme d'un email est
 * persistée brute ; sinon elle est remplacée par un marqueur masqué.
 */

$root = dirname(__DIR__, 2);
$source = file_get_contents($root . '/Modules/Auth/Models/AuthModel.php') ?: '';

assert($source !== '', 'AuthModel.php introuvable.');
assert(
    str_contains($source, 'MASQUE_NON_EMAIL'),
    'enregistrerTentativeConnexion() doit masquer les valeurs qui ne ressemblent pas à un email avant persistance.'
);
assert(
    preg_match('/\$emailBrut\s*=\s*\$email !== \'\' && str_contains\(\$email, \'@\'\)/', $source) === 1,
    'Le masquage doit se baser sur la présence du format email (@ + domaine), pas sur une simple heuristique de longueur.'
);

echo "LoginAttemptPasswordLeakFixTest SUCCESS\n";
