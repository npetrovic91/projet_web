<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Auth\Services\AuthService;

class LogoutController extends BaseController
{
    public function logout(): void
    {
        $auth = new AuthService();
        $auth->deconnecter('logout');
        header('Location: ' . url('/auth/login'), true, 302);
        exit;
    }
}
