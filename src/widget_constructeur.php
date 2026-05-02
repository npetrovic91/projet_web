<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Security\Class\CsrfProtection;
use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;

abstract class AjaxController extends BaseController
{
    protected array $user = [];

    public function __construct()
    {
        parent::__construct();
        $this->enforceAjaxHeader();
        $this->enforceAuthentication();
        $this->enforceCsrfToken();
    }

    private function enforceAjaxHeader(): void
    {
        if (($_SERVER[AJAX_REQUEST_HEADER] ?? '') !== AJAX_REQUEST_VALUE) {
            AjaxResponseService::badRequest('Cet endpoint accepte uniquement les requetes AJAX authentifiees.');
        }
    }

    private function enforceAuthentication(): void
    {
        if (!is_authenticated()) {
            logger('security')->warning('ajax_unauthenticated', [
                'ip' => client_ip(),
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
            ]);
            AjaxResponseService::unauthorized();
        }

        $this->user = [
            'id' => (int) ($_SESSION['user_id'] ?? 0),
            'uuid' => (string) ($_SESSION['user_uuid'] ?? ''),
            'email' => (string) ($_SESSION['user_email'] ?? ''),
            'roles' => (array) ($_SESSION['user_roles'] ?? []),
            'permissions' => (array) ($_SESSION['user_permissions'] ?? []),
            'active_company_id' => $_SESSION['active_company_id'] ?? null,
            'active_brand_id' => $_SESSION['active_brand_id'] ?? null,
        ];
    }

    private function enforceCsrfToken(): void
    {
        $token = trim((string) ($_SERVER[AJAX_CSRF_HEADER] ?? ($_POST[AJAX_CSRF_FIELD] ?? '')));
        if (!CsrfProtection::validateTokenValue($token)) {
            logger('security')->warning('ajax_csrf_invalid', [
                'user_id' => $this->user['id'] ?? null,
                'ip' => client_ip(),
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
            ]);
            AjaxResponseService::forbidden('Token de securite invalide ou expire.');
        }
    }

    protected function requireAjaxRole(array $allowedRoles): void
    {
        if (in_array(ROLE_SUPERADMIN, $this->user['roles'], true)) {
            return;
        }
        foreach ($allowedRoles as $role) {
            if (in_array($role, $this->user['roles'], true)) {
                return;
            }
        }
        AjaxResponseService::forbidden('Role insuffisant pour cette action.');
    }

    protected function jsonBody(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }
}
