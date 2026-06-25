<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Auth\Services\AuthService;

class EmailController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new AuthService();
    }

    public function verify(string $token): void
    {
        $success = $this->auth->verifierEmail($token);
        $this->render('Auth/Views/message', [
            'pageTitle' => $success ? 'Email vérifié' : 'Lien invalide',
            'title' => $success ? 'Email vérifié' : 'Lien invalide',
            'message' => $success
                ? 'Votre adresse email est maintenant vérifiée.'
                : 'Ce lien de vérification est invalide ou déjà utilisé.',
            'type' => $success ? 'success' : 'danger',
            'action_url' => '/auth/login',
            'action_label' => 'Retour à la connexion',
        ], 'minimal', $success ? 200 : 400);
    }

    public function resend(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $userId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            $this->redirect('/auth/login');
        }
        $token = $this->auth->genererTokenSecurite($userId);
        $this->flash('info', 'Token de vérification généré. Le module Emails enverra ce lien : /auth/verify-email/' . $token);
        $this->redirect('/profile');
    }
}
