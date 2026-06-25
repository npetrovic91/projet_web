<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Auth\Services\AuthService;

class PasswordController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new AuthService();
    }

    public function showForgot(): void
    {
        $this->render('Auth/Views/forgot_password', [
            'pageTitle' => 'Mot de passe oublié',
            'csrf_token' => csrf_token(),
            'errors' => [],
            'sent' => false,
        ], 'minimal');
    }

    public function processForgot(): void
    {
        $tokenCsrf = (string) ($_POST[CSRF_TOKEN_NAME] ?? $_POST['_csrf_token'] ?? '');
        if ($tokenCsrf !== '' && !$this->verifyCsrf($tokenCsrf)) {
            $this->render('Auth/Views/forgot_password', [
                'pageTitle' => 'Mot de passe oublié',
                'csrf_token' => csrf_token(),
                'errors' => ['Session expirée. Veuillez réessayer.'],
                'sent' => false,
            ], 'minimal', 419);
            return;
        }

        $result = $this->auth->demanderReinitialisation((string) ($_POST['email'] ?? ''));
        $this->render('Auth/Views/forgot_password', [
            'pageTitle' => 'Mot de passe oublié',
            'csrf_token' => csrf_token(),
            'errors' => [],
            'sent' => true,
            'dev_token' => $result['token'] ?? null,
        ], 'minimal');
    }

    public function showReset(string $token): void
    {
        $this->render('Auth/Views/reset_password', [
            'pageTitle' => 'Réinitialiser le mot de passe',
            'csrf_token' => csrf_token(),
            'token' => $token,
            'errors' => [],
        ], 'minimal');
    }

    public function processReset(): void
    {
        $tokenCsrf = (string) ($_POST[CSRF_TOKEN_NAME] ?? $_POST['_csrf_token'] ?? '');
        if ($tokenCsrf !== '' && !$this->verifyCsrf($tokenCsrf)) {
            $this->render('Auth/Views/reset_password', [
                'pageTitle' => 'Réinitialiser le mot de passe',
                'csrf_token' => csrf_token(),
                'token' => (string) ($_POST['token'] ?? ''),
                'errors' => ['Session expirée. Veuillez réessayer.'],
            ], 'minimal', 419);
            return;
        }

        $result = $this->auth->reinitialiserMotDePasse(
            (string) ($_POST['token'] ?? ''),
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['password_confirmation'] ?? '')
        );

        if (($result['success'] ?? false) === true) {
            $this->render('Auth/Views/message', [
                'pageTitle' => 'Mot de passe modifié',
                'title' => 'Mot de passe modifié',
                'message' => 'Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.',
                'type' => 'success',
                'action_url' => '/auth/login',
                'action_label' => 'Se connecter',
            ], 'minimal');
            return;
        }

        $this->render('Auth/Views/reset_password', [
            'pageTitle' => 'Réinitialiser le mot de passe',
            'csrf_token' => csrf_token(),
            'token' => (string) ($_POST['token'] ?? ''),
            'errors' => (array) ($result['errors'] ?? [$result['message'] ?? 'Réinitialisation impossible.']),
        ], 'minimal', 422);
    }
}
