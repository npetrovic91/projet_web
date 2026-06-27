<?php
declare(strict_types=1);

/**
 * AUTOSAV — Nouveaux déclencheurs email réels (2.5)
 *
 * Deux trous fonctionnels/légaux réels identifiés :
 *
 * 1. InvitationService::enregistrer()/regenererJeton() généraient un
 *    jeton d'invitation mais ne l'envoyaient JAMAIS par email — seulement
 *    affiché en flash message à l'administrateur, qui devait le
 *    transmettre lui-même par un canal externe. Une invitation est
 *    inutilisable sans ce lien : ce n'est pas une amélioration UX, c'est
 *    un parcours cassé.
 *
 * 2. GdprService::acceptRequest()/rejectRequest() ne notifiaient qu'en
 *    in-app (cf. 2.4) — insuffisant pour une obligation légale RGPD
 *    d'informer la personne concernée si elle ne se connecte pas à
 *    l'application. Passé à notifierUtilisateurAvecEmail() (email réel
 *    PHPMailer en plus du in-app).
 */

$root = dirname(__DIR__, 2);

$invitations = file_get_contents($root . '/Modules/Invitations/Services/InvitationService.php') ?: '';
assert($invitations !== '', 'InvitationService.php introuvable.');
assert(str_contains($invitations, 'EmailService'), 'InvitationService doit utiliser EmailService pour un envoi réel.');
assert(str_contains($invitations, 'function envoyerEmailInvitation'), 'InvitationService doit définir un envoi email du lien d\'invitation.');
assert(
    str_contains($invitations, "envoyerEmailInvitation(\$data['inv_email'], \$token)"),
    'enregistrer() doit envoyer l\'email d\'invitation lors de la création.'
);
assert(
    str_contains($invitations, "envoyerEmailInvitation((string) \$invitation['inv_email'], \$token)"),
    'regenererJeton() doit renvoyer l\'email d\'invitation lors de la régénération.'
);

$gdpr = file_get_contents($root . '/Modules/GDPR/Services/GdprService.php') ?: '';
assert($gdpr !== '', 'GdprService.php introuvable.');
assert(
    substr_count($gdpr, 'notifierUtilisateurAvecEmail') === 2,
    'acceptRequest() et rejectRequest() doivent tous les deux envoyer un email réel (pas seulement in-app) pour respecter l\'obligation RGPD d\'information.'
);

echo "NewEmailTriggersTest SUCCESS\n";
