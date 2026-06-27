<?php
declare(strict_types=1);

/**
 * AUTOSAV — Unification du mot de passe de tous les comptes démo
 *
 * Constat sur un export réel de la base (u166513890_base (5).sql,
 * 2026-06-27) : 64 comptes démo répartis sur deux mots de passe selon le
 * lot d'origine, et une anomalie (pdg.neovolt@autosav.demo avec un
 * troisième hash, jamais couvert par le correctif lot36 qui ne cible que
 * le réseau automobile). Ce script unifie tous les comptes
 * @autosav.demo sur Demo2026!, sans jamais toucher aux comptes réels
 * (@autosav.local).
 */

$root = dirname(__DIR__, 2);
$path = $root . '/database/seeds/2026_06_27_unifier_mot_de_passe_demo.sql';
$sql = file_get_contents($path) ?: '';

assert($sql !== '', '2026_06_27_unifier_mot_de_passe_demo.sql introuvable.');

$hash = '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c';
assert(password_verify('Demo2026!', $hash), 'Le hash utilisé doit correspondre au mot de passe Demo2026!.');
assert(str_contains($sql, $hash), 'Le script doit utiliser ce hash Demo2026! exact (déjà utilisé par les 54 autres comptes démo).');

assert(str_contains($sql, "WHERE uti_email_normalise LIKE '%@autosav.demo'"), 'Le filtre doit cibler tous les comptes @autosav.demo, quel que soit le lot.');
// La requête UPDATE elle-même (hors commentaires) ne doit comporter
// qu'un seul critère de filtre, portant sur @autosav.demo.
assert(substr_count($sql, "WHERE uti_email_normalise") === 1, 'Le script ne doit avoir qu\'un seul filtre WHERE, ciblant uniquement les comptes démo.');
assert(str_contains($sql, 'uti_est_verrouille = 0') && str_contains($sql, 'uti_echecs_connexion = 0'), 'Le script doit aussi déverrouiller les comptes et réinitialiser les échecs de connexion (cohérent avec lot36).');

// Ce script vit dans seeds/, jamais scanné par bin/migrate.php.
assert(
    !file_exists($root . '/database/migrations/2026_06_27_unifier_mot_de_passe_demo.sql'),
    'Ce script de données démo ne doit jamais se trouver dans database/migrations/.'
);

$docs = file_get_contents($root . '/docs/COMPTES_DEMO_LOT35.md') ?: '';
assert(str_contains($docs, '2026_06_27_unifier_mot_de_passe_demo.sql'), 'docs/COMPTES_DEMO_LOT35.md doit référencer ce nouveau script.');

echo "UnifyDemoPasswordFixTest SUCCESS\n";
