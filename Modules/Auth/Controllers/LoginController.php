<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Auth\Services\AuthService;

class LoginController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        parent::__construct();
        $this->auth = new AuthService();
    }

    public function showLogin(): void
    {
        if (is_authenticated()) {
            $this->redirect('/dashboard');
        }

        $this->render('Auth/Views/login', [
            'pageTitle' => 'Connexion',
            'page_title' => 'Connexion',
            'csrf_token' => csrf_token(),
            'redirect' => (string) ($_SESSION['redirect_after_login'] ?? '/dashboard'),
            'errors' => [],
            'email' => '',
        ], 'minimal');
    }

    public function processLogin(): void
    {
        $token = (string) ($_POST[CSRF_TOKEN_NAME] ?? $_POST['_csrf_token'] ?? '');
        if ($token !== '' && !$this->verifyCsrf($token)) {
            $this->render('Auth/Views/login', [
                'pageTitle' => 'Connexion',
                'page_title' => 'Connexion',
                'csrf_token' => csrf_token(),
                'errors' => ['Session expirée. Veuillez réessayer.'],
                'email' => (string) ($_POST['email'] ?? $_POST['identifiant'] ?? ''),
            ], 'minimal', 419);
            return;
        }

        $identifiant = (string) ($_POST['email'] ?? $_POST['identifiant'] ?? '');
        $password = (string) ($_POST['password'] ?? $_POST['mot_de_passe'] ?? '');
        try {
            $result = $this->auth->authentifier($identifiant, $password);
        } catch (\Throwable $e) {
            error_log(sprintf(
                '[AUTOSAV][AUTH] %s: %s in %s:%d',
                $e::class,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
            $this->render('Auth/Views/login', [
                'pageTitle' => 'Connexion',
                'page_title' => 'Connexion',
                'csrf_token' => csrf_token(),
                'errors' => ['Une erreur technique empêche la connexion. Contactez l’administrateur.'],
                'email' => $identifiant,
            ], 'minimal', 500);
            return;
        }

        if (($result['success'] ?? false) === true) {
            $redirect = (string) ($_POST['redirect'] ?? $result['redirect'] ?? '/dashboard');
            unset($_SESSION['redirect_after_login']);
            $this->redirect($this->redirectionInterne($redirect));
        }

        $this->render('Auth/Views/login', [
            'pageTitle' => 'Connexion',
            'page_title' => 'Connexion',
            'csrf_token' => csrf_token(),
            'errors' => (array) ($result['errors'] ?? [$result['message'] ?? 'Connexion impossible.']),
            'email' => $identifiant,
        ], 'minimal', 401);
    }

    private function redirectionInterne(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return '/dashboard';
        }
        if (str_starts_with($url, '/login') || str_starts_with($url, '/auth/login')) {
            return '/dashboard';
        }
        return $url;
    }
}
