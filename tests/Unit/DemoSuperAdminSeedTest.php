<?php
declare(strict_types=1);

/**
 * AUTOSAV — Compte démo super_administrateur (2026-06-28)
 *
 * Aucun lot démo existant ne créait de compte super_administrateur
 * (rôle plateforme, sans société associée — distinct des 11 rôles
 * "métier" scopés à une société utilisés par les autres lots démo).
 * Ce script en crée un, en miroir du schéma du compte réel
 * super.admin@autosav.local observé sur un export de production
 * (uti_est_systeme=1, uti_societe_active_id=1), pour que les comptes de
 * démonstration couvrent aussi ce rôle.
 */

$root = dirname(__DIR__, 2);
$sql = file_get_contents($root . '/database/seeds/2026_06_28_demo_super_admin.sql') ?: '';

assert($sql !== '', '2026_06_28_demo_super_admin.sql introuvable.');

$hash = '$argon2id$v=19$m=65536,t=4,p=1$dVG2Zs2AWwMT4YzsjVrAtg$TF0NZJGzJJ7ILp68GKG2ZqMyfSDyS7h+yd0B2mkAj/c';
assert(password_verify('Demo2026!', $hash), 'Le hash utilisé doit correspondre au mot de passe Demo2026! (cohérent avec les 54 autres comptes démo).');
assert(str_contains($sql, $hash), 'Le script doit réutiliser le même hash Demo2026! que les autres comptes démo.');

assert(str_contains($sql, "'super.admin.demo'"), 'Le compte doit utiliser un identifiant clairement démo (super.admin.demo), distinct du compte réel super.admin@autosav.local.');
assert(str_contains($sql, "@autosav.demo'"), "L'email doit rester sur le domaine @autosav.demo (jamais @autosav.local, réservé aux comptes réels).");

assert(
    preg_match('/super\.admin\.demo.*?,\s*1,\s*16,\s*1,\s*0,\s*1,/s', $sql) === 1,
    'uti_est_systeme doit être à 1 (cohérent avec le compte réel super.admin@autosav.local, dispense de l\'exigence d\'adhésion à une société — cf. AuthService.php).'
);

assert(
    str_contains($sql, 'rcu_role_id') && str_contains($sql, '@super_admin_demo_id, 1, NULL'),
    'Le rôle super_administrateur (rol_id=1) doit être assigné sans société (rcu_societe_id=NULL), comme le rôle plateforme réel.'
);

// Ce script vit dans seeds/, jamais scanné par bin/migrate.php.
assert(
    !file_exists($root . '/database/migrations/2026_06_28_demo_super_admin.sql'),
    'Ce script de données démo ne doit jamais se trouver dans database/migrations/.'
);

echo "DemoSuperAdminSeedTest SUCCESS\n";
