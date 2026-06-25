<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : notifications in-app + emails sur actions sensibles
 *
 * Modules/Notifications/Models/NotificationModel::queue() existait déjà
 * (table sav_notifications fonctionnelle) mais n'était appelé par AUCUNE
 * action métier — seulement par l'UI de lecture et l'admin EventTriggers.
 * Aucune action sensible ne notifiait jamais personne. ActionNotifier
 * centralise l'appel (in-app systématique, email réel via PHPMailer en
 * option) et est désormais branché sur les actions explicitement
 * désignées comme prioritaires : changement de mot de passe, changement
 * de rôle (avec email), validation/rejet de standard, accès justifié
 * super_admin (in-app).
 */

$root = dirname(__DIR__, 2);

$notifier = file_get_contents($root . '/Modules/Notifications/Services/ActionNotifier.php') ?: '';
assert($notifier !== '', 'ActionNotifier.php introuvable.');
assert(str_contains($notifier, 'function notifierUtilisateur'), 'ActionNotifier doit definir notifierUtilisateur().');
assert(str_contains($notifier, 'function notifierUtilisateurAvecEmail'), 'ActionNotifier doit definir notifierUtilisateurAvecEmail() pour les envois email reels.');
assert(str_contains($notifier, 'EmailService'), 'notifierUtilisateurAvecEmail() doit utiliser EmailService (envoi PHPMailer reel), pas seulement journaliser.');

$profileService = file_get_contents($root . '/Modules/Profile/Services/ProfileService.php') ?: '';
assert(
    str_contains($profileService, 'notifierUtilisateurAvecEmail') && str_contains($profileService, 'mot_de_passe_modifie'),
    'ProfileService::changePassword() doit notifier + emailer l\'utilisateur (in-app + PHPMailer).'
);

$userService = file_get_contents($root . '/Modules/Users/Services/UserService.php') ?: '';
assert(
    str_contains($userService, 'notifierChangementRole') && str_contains($userService, 'rolesAvant') && str_contains($userService, 'rolesApres'),
    'UserService::enregistrer() doit detecter un changement de role et notifier+emailer l\'utilisateur concerne.'
);

$standardService = file_get_contents($root . '/Modules/Standards/Services/StandardService.php') ?: '';
assert(
    str_contains($standardService, "'standard.version_validee'") && str_contains($standardService, "'standard.version_rejetee'"),
    'StandardService doit notifier l\'auteur d\'un standard valide ou rejete.'
);

$guard = file_get_contents($root . '/Core/Security/Class/SuperAdminAccessGuard.php') ?: '';
assert(
    str_contains($guard, 'notifierAdministrateursSociete') && str_contains($guard, "'administrateur_general_societe', 'pdg'"),
    'SuperAdminAccessGuard doit notifier les administrateurs de la societe consultee (transparence ACC-007).'
);

echo "ActionNotifierWiringTest SUCCESS\n";
