<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Services;

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Core\Security\SecurityManager;
use Nenad\Autosav\Modules\Auth\Models\PasswordResetTokenModel;

/**
 * Service de rÃ©initialisation de mot de passe.
 *
 * Flux :
 *   1. L'utilisateur saisit son email â†’ generateAndSendToken()
 *   2. Clic sur le lien email â†’ validateToken()
 *   3. Soumission du nouveau MDP â†’ resetPassword()
 */
class PasswordResetService
{
    private PasswordResetTokenModel  $tokenModel;
    private EmailVerificationService $emailService;
    private SecurityManager          $security;
    private LogManager               $logger;

    public function __construct(
        PasswordResetTokenModel  $tokenModel,
        EmailVerificationService $emailService,
        SecurityManager          $security,
        LogManager               $logger
    ) {
        $this->tokenModel   = $tokenModel;
        $this->emailService = $emailService;
        $this->security     = $security;
        $this->logger       = $logger;
    }

    /**
     * GÃ©nÃ¨re un token et envoie l'email de reset.
     * RÃ©ponse identique si l'email existe ou non (anti-Ã©numÃ©ration).
     *
     * @param string $email   Email saisi dans le formulaire
     * @param string $ip      IP du demandeur
     * @return bool           true si l'email a Ã©tÃ© envoyÃ©, false si email introuvable
     */
    public function generateAndSendToken(string $email, string $ip): bool
    {
        $emailNorm = mb_strtolower(trim($email));

        $user = Database::getInstance()->fetch(
            "SELECT use_id, use_email, use_firstname, use_email_verified_at
             FROM sav_users
             WHERE use_email = :email
               AND use_is_active = 1
               AND use_deleted_at IS NULL
             LIMIT 1",
            [':email' => $emailNorm]
        );

        // Si l'utilisateur n'existe pas â†’ on simule un succÃ¨s (anti-Ã©numÃ©ration)
        if ($user === null) {
            $this->logger->channel('security')->info('password_reset_unknown_email', [
                'email' => $emailNorm,
                'ip'    => $ip,
            ]);
            return false;
        }

        if ($user['use_email_verified_at'] === null) {
            $this->logger->channel('security')->info('password_reset_unverified_email', [
                'user_id' => $user['use_id'],
                'ip'      => $ip,
            ]);
            return false;
        }

        // Invalider les tokens prÃ©cÃ©dents
        $this->tokenModel->invalidatePrevious((int) $user['use_id']);

        // GÃ©nÃ©rer un nouveau token
        $token     = bin2hex(random_bytes(TOKEN_BYTE_LENGTH));
        $tokenHash = hash('sha256', $token);

        $this->tokenModel->create(
            (int) $user['use_id'],
            $tokenHash,
            RESET_TOKEN_EXPIRY_HOURS
        );

        // Envoyer l'email
        $sent = $this->emailService->sendPasswordResetEmail(
            $user['use_email'],
            $user['use_firstname'],
            $token
        );

        if ($sent) {
            $this->logger->channel('security')->info('password_reset_email_sent', [
                'user_id' => $user['use_id'],
                'ip'      => $ip,
            ]);
        }

        return $sent;
    }

    /**
     * Valide un token de reset (vÃ©rification avant affichage du formulaire).
     *
     * @param string $rawToken Token brut depuis l'URL
     * @return array|null      DonnÃ©es du token si valide, null sinon
     */
    public function validateToken(string $rawToken): ?array
    {
        $tokenHash = hash('sha256', $rawToken);
        return $this->tokenModel->findValidByHash($tokenHash);
    }

    /**
     * Applique le nouveau mot de passe et invalide le token.
     *
     * @param string $rawToken   Token brut depuis le formulaire
     * @param string $newPassword Nouveau mot de passe saisi
     * @param string $ip         IP du demandeur
     * @return array {
     *   'success' => bool,
     *   'message' => string
     * }
     */
    public function resetPassword(string $rawToken, string $newPassword, string $ip): array
    {
        $tokenHash = hash('sha256', $rawToken);
        $tokenData = $this->tokenModel->findValidByHash($tokenHash);

        if ($tokenData === null) {
            return ['success' => false, 'message' => 'Ce lien de rÃ©initialisation est invalide ou expirÃ©.'];
        }

        // Valider la complexitÃ© du nouveau mot de passe
        $validation = $this->security->validatePasswordStrength($newPassword);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => implode(' ', $validation['errors'])];
        }

        $userId   = (int) $tokenData['use_id'];
        $newHash  = $this->security->hashPassword($newPassword);
        $db       = Database::getInstance();

        // Enregistrer dans l'historique des mots de passe
        $db->execute(
            "INSERT INTO sav_password_history (phs_user_id, phs_hash, phs_created_at)
             VALUES (:user_id, :hash, NOW())",
            [':user_id' => $userId, ':hash' => $newHash]
        );

        // Purger l'historique si dÃ©passement de PASSWORD_HISTORY_COUNT
        $db->execute(
            "DELETE FROM sav_password_history
             WHERE phs_user_id = :user_id
               AND phs_id NOT IN (
                   SELECT phs_id FROM (
                       SELECT phs_id FROM sav_password_history
                       WHERE phs_user_id = :user_id2
                       ORDER BY phs_created_at DESC
                       LIMIT :limit
                   ) sub
               )",
            [':user_id' => $userId, ':user_id2' => $userId, ':limit' => PASSWORD_HISTORY_COUNT]
        );

        // Mettre Ã  jour le mot de passe
        $db->execute(
            "UPDATE sav_users
             SET use_password_hash        = :hash,
                 use_password_changed_at  = NOW(),
                 use_must_change_password = 0,
                 use_failed_login_attempts = 0,
                 use_updated_at           = NOW()
             WHERE use_id = :id",
            [':hash' => $newHash, ':id' => $userId]
        );

        // Invalider le token (usage unique â€” R30)
        $this->tokenModel->markUsed((int) $tokenData['prt_id'], $ip);

        $this->logger->channel('security')->info('password_reset_success', [
            'user_id' => $userId,
            'ip'      => $ip,
        ]);

        return ['success' => true, 'message' => 'Votre mot de passe a Ã©tÃ© rÃ©initialisÃ© avec succÃ¨s.'];
    }
}


