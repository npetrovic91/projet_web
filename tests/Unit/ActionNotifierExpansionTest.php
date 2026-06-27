<?php
declare(strict_types=1);

/**
 * AUTOSAV — Extension du branchement ActionNotifier (2.4)
 *
 * Avant ce correctif, ActionNotifier::notifierUtilisateur() n'était
 * branché que sur 3 actions (changement mot de passe, changement de
 * rôle, validation/rejet de standard — cf. ActionNotifierWiringTest).
 * D'autres actions affectant directement un utilisateur identifiable ne
 * notifiaient personne : décision sur une demande de validation,
 * décision RGPD (qui doit légalement informer la personne concernée),
 * rattachement à une nouvelle société, assignation d'un nouveau
 * supérieur hiérarchique, acceptation d'une invitation.
 */

$root = dirname(__DIR__, 2);

$checks = [
    'Modules/Validation/Services/ValidationService.php' => ['ActionNotifier', 'validation.demande_decidee'],
    'Modules/GDPR/Services/GdprService.php' => ['ActionNotifier', 'gdpr.demande_acceptee', 'gdpr.demande_rejetee'],
    'Modules/Users/Services/UserCompanyService.php' => ['ActionNotifier', 'utilisateur.societe_rattachee'],
    'Modules/Users/Services/UserHierarchyService.php' => ['ActionNotifier', 'utilisateur.superieur_assigne'],
    'Modules/Invitations/Services/InvitationService.php' => ['ActionNotifier', 'invitation.acceptee'],
];

foreach ($checks as $path => $needles) {
    $source = file_get_contents($root . '/' . $path) ?: '';
    assert($source !== '', $path . ' introuvable.');
    foreach ($needles as $needle) {
        assert(str_contains($source, $needle), $path . ' doit contenir "' . $needle . '" (notification manquante non corrigée).');
    }
}

echo "ActionNotifierExpansionTest SUCCESS\n";
