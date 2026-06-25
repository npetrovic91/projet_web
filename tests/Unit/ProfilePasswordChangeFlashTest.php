<?php
declare(strict_types=1);

/**
 * AUTOSAV — Régression : notification du changement de mot de passe
 *
 * Bug corrigé le 2026-06-25 : sur /profile, un mot de passe et sa
 * confirmation différents étaient bien rejetés côté serveur
 * (ProfileService::changePassword), mais l'utilisateur n'en voyait jamais
 * rien — Core/Theme/Vue.php n'appelait jamais
 * SweetAlertGenerator::renderFromFlash(), malgré un commentaire affirmant
 * que c'était fait automatiquement. Ce test verrouille les deux moitiés du
 * correctif : la validation serveur ET son affichage réel.
 */

$root = dirname(__DIR__, 2);

$profileService = file_get_contents($root . '/Modules/Profile/Services/ProfileService.php') ?: '';
$vue = file_get_contents($root . '/Core/Theme/Vue.php') ?: '';
$show = file_get_contents($root . '/Modules/Profile/Views/show.php') ?: '';

assert($profileService !== '', 'ProfileService.php introuvable.');
assert($vue !== '', 'Vue.php introuvable.');
assert($show !== '', 'Profile/Views/show.php introuvable.');

// Validation serveur : changePassword() doit comparer newPassword et confirm.
assert(
    str_contains($profileService, '$newPassword !== $confirm'),
    'ProfileService::changePassword doit rejeter une confirmation differente du nouveau mot de passe.'
);

// Affichage réel : Vue.php doit consommer $_SESSION[\'flash\'] et le rendre
// via SweetAlertGenerator, pas seulement le stocker.
assert(
    str_contains($vue, "\$_SESSION['flash']"),
    'Vue.php doit lire les messages flash de la session.'
);
assert(
    str_contains($vue, 'SweetAlertGenerator::renderFromFlash'),
    'Vue.php doit rendre les messages flash via SweetAlertGenerator::renderFromFlash (sinon ils restent invisibles).'
);
assert(
    str_contains($vue, 'renderFlashAlerts'),
    'Vue::render() doit appeler une methode dediee a l\'affichage des flashs dans le corps de page.'
);

// Contrôle client en complément (formulaire de mot de passe) : ne doit pas
// dépendre uniquement de l'aller-retour serveur pour la confirmation.
assert(
    str_contains($show, 'id="password-confirm"') && str_contains($show, 'checkMatch'),
    'Le formulaire de mot de passe doit comparer les deux champs cote client avant soumission.'
);

echo "ProfilePasswordChangeFlashTest SUCCESS\n";
