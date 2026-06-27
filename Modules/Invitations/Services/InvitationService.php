<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Invitations\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Emails\Services\EmailService;
use Nenad\Autosav\Modules\Invitations\Models\InvitationModel;
use Nenad\Autosav\Modules\Notifications\Services\ActionNotifier;

class InvitationService implements ServiceInterface{
    private InvitationModel $model;
    private ActionNotifier $notifier;
    private EmailService $emails;

    public function __construct(?ActionNotifier $notifier = null, ?EmailService $emails = null)
    {
        $this->model = new InvitationModel();
        $this->notifier = $notifier ?? new ActionNotifier();
        $this->emails = $emails ?? new EmailService();
    }

    public function statistiques(?int $societeId = null): array { return $this->model->statistiques($societeId); }
    public function lister(array $filters = []): array { return $this->model->lister($filters); }
    public function trouver(int $id): ?array { return $this->model->trouver($id); }
    public function references(): array { return $this->model->references(); }
    public function export(?int $societeId = null): array { return ['statistiques' => $this->statistiques($societeId), 'invitations' => $this->lister(['societe_id' => $societeId])]; }

    public function enregistrer(array $post, ?int $id, ?int $userId, ?string $ip): array
    {
        $data = $this->normaliserInvitation($post);
        if ($id) {
            $this->model->modifier($id, array_merge($data, ['inv_modifie_par_utilisateur_id' => $userId]));
            $this->model->audit($userId, $data['inv_societe_id'], 'invitation.modifier', 'sav_invitations_utilisateurs', $id, ['email' => $data['inv_email']], $ip);
            return ['id' => $id, 'token' => null];
        }
        $token = $this->genererToken();
        $data['inv_uuid'] = $this->uuid4();
        $data['inv_jeton_hash'] = hash('sha256', $token);
        $data['inv_statut_id'] = $this->model->references()['statut_invitation_envoyee'];
        $data['inv_cree_par_utilisateur_id'] = $userId;
        $newId = $this->model->creer($data);
        $this->model->audit($userId, $data['inv_societe_id'], 'invitation.creer', 'sav_invitations_utilisateurs', $newId, ['email' => $data['inv_email']], $ip);

        // CORRECTIF 2.5 (déclencheur email, 2026-06-27) : avant ce correctif,
        // le jeton d'invitation n'était JAMAIS envoyé par email — seulement
        // affiché en flash message à l'administrateur, qui devait le
        // transmettre lui-même par un canal externe. L'invitation est
        // pourtant inutilisable sans ce lien.
        $this->envoyerEmailInvitation($data['inv_email'], $token);

        return ['id' => $newId, 'token' => $token];
    }

    public function regenererJeton(int $id, ?int $userId, ?string $ip): string
    {
        $invitation = $this->model->trouver($id);
        if (!$invitation) {
            throw new \RuntimeException('Invitation introuvable.');
        }
        if (!empty($invitation['inv_acceptee_le'])) {
            throw new \RuntimeException('Une invitation acceptée ne peut pas être régénérée.');
        }
        $token = $this->genererToken();
        $this->model->modifier($id, [
            'inv_jeton_hash' => hash('sha256', $token),
            'inv_expire_le' => $this->expirationDepuisPost(['expire_jours' => 7]),
            'inv_annulee_le' => null,
            'inv_statut_id' => $this->model->references()['statut_invitation_envoyee'],
            'inv_modifie_par_utilisateur_id' => $userId,
        ]);
        $this->model->audit($userId, (int)$invitation['inv_societe_id'], 'invitation.regenerer', 'sav_invitations_utilisateurs', $id, [], $ip);
        $this->envoyerEmailInvitation((string) $invitation['inv_email'], $token);
        return $token;
    }

    public function annuler(int $id, ?int $userId, ?string $ip): void
    {
        $invitation = $this->model->trouver($id);
        if (!$invitation) {
            throw new \RuntimeException('Invitation introuvable.');
        }
        $this->model->modifier($id, [
            'inv_annulee_le' => date('Y-m-d H:i:s'),
            'inv_statut_id' => $this->model->references()['statut_invitation_annulee'],
            'inv_modifie_par_utilisateur_id' => $userId,
        ]);
        $this->model->audit($userId, (int)$invitation['inv_societe_id'], 'invitation.annuler', 'sav_invitations_utilisateurs', $id, [], $ip);
    }

    public function trouverParJetonPublic(string $token): ?array
    {
        return $this->model->trouverParHash(hash('sha256', $token));
    }

    public function accepterInvitation(string $token, array $post, ?string $ip, ?string $userAgent): int
    {
        $invitation = $this->trouverParJetonPublic($token);
        if (!$invitation) {
            throw new \RuntimeException('Invitation introuvable ou invalide.');
        }
        if (!empty($invitation['inv_acceptee_le'])) {
            throw new \RuntimeException('Cette invitation a déjà été acceptée.');
        }
        if (!empty($invitation['inv_annulee_le'])) {
            throw new \RuntimeException('Cette invitation a été annulée.');
        }
        if (strtotime((string)$invitation['inv_expire_le']) < time()) {
            throw new \RuntimeException('Cette invitation est expirée.');
        }
        $password = (string)($post['password'] ?? '');
        if (strlen($password) < 12 || $password !== (string)($post['password_confirmation'] ?? '')) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 12 caractères et être confirmé.');
        }
        $newUserId = $this->model->creerUtilisateurDepuisInvitation($invitation, [
            'password' => $password,
            'nom' => $post['nom'] ?? '',
            'prenom' => $post['prenom'] ?? '',
        ], $ip, $userAgent);

        // CORRECTIF 2.4 (notifications in-app) : la personne ayant envoyé
        // l'invitation n'était jamais informée de son acceptation.
        $inviterId = (int) ($invitation['inv_cree_par_utilisateur_id'] ?? 0);
        if ($inviterId > 0) {
            $this->notifier->notifierUtilisateur(
                $inviterId,
                'invitation.acceptee',
                'Invitation acceptée',
                'L\'invitation envoyée à ' . (string) $invitation['inv_email'] . ' a été acceptée.',
                ['invitation_id' => (int) $invitation['inv_id'], 'nouvel_utilisateur_id' => $newUserId],
                isset($invitation['inv_societe_id']) ? (int) $invitation['inv_societe_id'] : null,
                null
            );
        }

        return $newUserId;
    }

    public function creerPremierAdministrateur(array $post, ?int $userId, ?string $ip): array
    {
        $post['role_prevu_id'] = $post['role_prevu_id'] ?? $this->roleSuperAdministrateurId();
        return $this->enregistrer($post, null, $userId, $ip);
    }

    private function normaliserInvitation(array $post): array
    {
        $email = strtolower(trim((string)($post['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Adresse email invalide.');
        }
        $societeId = (int)($post['societe_id'] ?? 0);
        if ($societeId <= 0) {
            throw new \InvalidArgumentException('La société est obligatoire.');
        }
        return [
            'inv_email' => $email,
            'inv_email_normalise' => $email,
            'inv_societe_id' => $societeId,
            'inv_role_prevu_id' => (int)($post['role_prevu_id'] ?? 0) ?: null,
            'inv_fonction_prevue_id' => (int)($post['fonction_prevue_id'] ?? 0) ?: null,
            'inv_message' => trim((string)($post['message'] ?? '')) ?: null,
            'inv_expire_le' => $this->expirationDepuisPost($post),
        ];
    }

    private function expirationDepuisPost(array $post): string
    {
        $jours = max(1, min(60, (int)($post['expire_jours'] ?? 7)));
        return date('Y-m-d H:i:s', strtotime('+' . $jours . ' days'));
    }

    /**
     * CORRECTIF 2.5 : envoi réel (PHPMailer via EmailService) du lien
     * d'invitation. Best-effort : un échec d'envoi ne doit jamais
     * empêcher la création/régénération de l'invitation elle-même
     * (cohérent avec ActionNotifier::notifierUtilisateurAvecEmail()) — le
     * jeton reste disponible via le flash message en repli.
     */
    private function envoyerEmailInvitation(string $email, string $token): void
    {
        $appUrl = defined('APP_URL') ? rtrim((string) APP_URL, '/') : '';
        $lien = $appUrl . '/invitations/accept/' . rawurlencode($token);
        try {
            $this->emails->envoyer([
                'to' => $email,
                'subject' => 'Invitation à rejoindre AUTOSAV',
                'body' => '<p>Vous avez été invité à créer votre compte AUTOSAV.</p>'
                    . '<p><a href="' . htmlspecialchars($lien, ENT_QUOTES, 'UTF-8') . '">Accepter l\'invitation</a></p>'
                    . '<p>Ce lien expire dans quelques jours. Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email.</p>',
                'type_evenement' => 'invitation.envoyee',
            ]);
        } catch (\Throwable $e) {
            if (function_exists('logger')) {
                logger('email')->warning('invitation_email_echec', ['email' => $email, 'erreur' => $e->getMessage()]);
            }
        }
    }

    private function genererToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function roleSuperAdministrateurId(): ?int
    {
        foreach ($this->references()['roles'] as $role) {
            if (($role['rol_code'] ?? '') === 'super_administrateur') {
                return (int)$role['rol_id'];
            }
        }
        return null;
    }
}
