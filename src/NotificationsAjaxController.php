<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Modules\Auth\Models\EmailTokenModel;
use Nenad\Autosav\Modules\Auth\Services\AuthService;

/**
 * ContrÃ´leur de validation d'email.
 * Traite le clic sur le lien de validation reÃ§u par email.
 */
class EmailController extends BaseController
{
    private EmailTokenModel $tokenModel;
    private LogManager      $logger;

    public function __construct()
    {
        parent::__construct();

        $this->tokenModel = new EmailTokenModel();
        $this->logger     = LogManager::getInstance();
    }

    /**
     * VÃ©rifie et valide le token de confirmation d'email.
     *
     * @param string $token Token brut extrait de l'URL
     * @return void
     */
    public function verify(string $token): void
    {
        $ip = AuthService::detectClientIp();

        // Nettoyer le token
        $rawToken  = trim($token);
        $tokenHash = hash('sha256', $rawToken);

        if (empty($rawToken)) {
            $this->flash()->error('Lien de validation invalide.');
            $this->render('Auth/Views/verify_email', [
                'pageTitle' => 'Validation Email â€” Autosav',
                'status'    => 'error',
                'message'   => 'Lien de validation invalide.',
            ], 'none');
            return;
        }

        // Chercher le token valide
        $tokenData = $this->tokenModel->findValidByHash($tokenHash);

        if ($tokenData === null) {
            $this->logger->channel('security')->warning('email_verify_invalid_token', [
                'ip'    => $ip,
                'token' => substr($rawToken, 0, 8) . '...', // Partial pour log
            ], 'none');

            $this->render('Auth/Views/verify_email', [
                'pageTitle' => 'Validation Email â€” Autosav',
                'status'    => 'error',
                'message'   => 'Ce lien de validation est invalide ou a expirÃ©. '
                    . 'Veuillez demander un nouveau lien de validation.',
            ], 'none');
            return;
        }

        // VÃ©rifier que l'email n'est pas dÃ©jÃ  validÃ©
        if ($tokenData['use_email_verified_at'] !== null) {
            $this->render('Auth/Views/verify_email', [
                'pageTitle' => 'Validation Email â€” Autosav',
                'status'    => 'already_verified',
                'message'   => 'Votre adresse email a dÃ©jÃ  Ã©tÃ© validÃ©e. Vous pouvez vous connecter.',
            ]);
            return;
        }

        $userId = (int) $tokenData['etk_user_id'];

        // Marquer l'email comme vÃ©rifiÃ©
        Database::getInstance()->execute(
            "UPDATE sav_users
             SET use_email_verified_at = NOW(),
                 use_updated_at        = NOW()
             WHERE use_id = :id",
            [':id' => $userId]
        );

        // Marquer le token comme utilisÃ© (usage unique â€” R30)
        $this->tokenModel->markUsed((int) $tokenData['etk_id'], $ip);

        $this->logger->channel('security')->info('email_verified', [
            'user_id' => $userId,
            'email'   => $tokenData['use_email'],
            'ip'      => $ip,
        ], 'none');

        $this->render('Auth/Views/verify_email', [
            'pageTitle' => 'Email validÃ© â€” Autosav',
            'status'    => 'success',
            'message'   => 'Votre adresse email a Ã©tÃ© validÃ©e avec succÃ¨s. Vous pouvez maintenant vous connecter.',
        ]);
    }
}
