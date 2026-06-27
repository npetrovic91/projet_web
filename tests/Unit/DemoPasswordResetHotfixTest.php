<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$hotfix = file_get_contents($root . '/database/seeds/2026_06_03_lot36_reset_demo_passwords.sql');
$readme = file_get_contents($root . '/database/seeds/README_IMPORT_LOT36_RESET_DEMO_PASSWORDS_HOSTINGER.txt');
$accounts = file_get_contents($root . '/docs/COMPTES_DEMO_LOT35.md');

$hash = '$argon2id$v=19$m=65536,t=4,p=1$eG91d1ZxUGZ1LjV1MDFPVw$NfTUFJpcpH6gZegE8mJZ0BNRgK2O+KZUXTuJ/pI1784';

assert($hotfix !== false, 'Hotfix SQL lot36 introuvable.');
assert($readme !== false, 'README lot36 introuvable.');
assert($accounts !== false, 'Fiche comptes demo introuvable.');
assert(password_verify('DemoAutosav!2026', $hash), 'Le hash demo ne correspond pas au mot de passe annonce.');
assert(str_contains($hotfix, 'UPDATE sav_utilisateurs'));
assert(str_contains($hotfix, 'admin.general'));
assert(str_contains($hotfix, 'admin.general@autosav.demo'));
assert(str_contains($hotfix, 'uti_est_verrouille = 0'));
assert(str_contains($hotfix, 'uti_echecs_connexion = 0'));
assert(str_contains($hotfix, 'uti_doit_changer_mot_de_passe = 1'));
assert(str_contains($hotfix, '2026_06_03_lot36_reset_demo_passwords'));
assert(str_contains($readme, 'Identifiants invalides'));
assert(str_contains($readme, 'DemoAutosav!2026'));
assert(str_contains($accounts, '2026_06_03_lot36_reset_demo_passwords.sql'));

echo "DemoPasswordResetHotfixTest SUCCESS\n";
