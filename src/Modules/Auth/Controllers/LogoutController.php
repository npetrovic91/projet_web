<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Core\Security\SecurityManager;
use Nenad\Autosav\Modules\Auth\Services\AuthService;

class LogoutController extends BaseController
{
    public function logout(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/');
        }

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $sessionId = session_id();
        $ip = AuthService::detectClientIp();

        if ($userId > 0 && $sessionId !== '') {
            try {
                Database::getInstance()->execute(
                    "UPDATE sav_sessions
                     SET ses_ended_at = NOW(), ses_ended_reason = 'logout'
                     WHERE ses_session_id = :session_id
                       AND ses_user_id = :user_id
                       AND ses_ended_at IS NULL",
                    [':session_id' => $sessionId, ':user_id' => $userId]
                );
            } catch (\Throwable) {
            }

            LogManager::getInstance()->channel('security')->info('logout', [
                'user_id' => $userId,
                'ip' => $ip,
            ]);
        }

        SecurityManager::getInstance()->destroySession();
        session_start();
        $this->flash()->success('Vous avez ete deconnecte.');
        $this->redirect('/');
    }
}
