<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Auth\Services\AuthService;

class TermsController extends BaseController
{
    public function accept(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $auth = new AuthService();
        $auth->accepterDernieresConditions((int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0));
        $this->flash('success', 'Conditions acceptées.');
        $this->redirect('/dashboard');
    }

    public function refuse(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $auth = new AuthService();
        $auth->deconnecter('terms_refused');
        header('Location: ' . url('/auth/login'), true, 302);
        exit;
    }
}
