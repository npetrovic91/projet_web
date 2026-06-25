<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Core\Middleware\TenantEntitlementMiddleware;
use Nenad\Autosav\Core\Security\Class\RoleResolver;
use Nenad\Autosav\Modules\Auth\Models\AuthModel;
use Nenad\Autosav\Modules\Ajax\Models\ContextModel;
use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;

class ContextController extends AjaxController
{
    private ContextModel $contextModel;
    private AuthModel $authModel;

    public function __construct()
    {
        parent::__construct();
        $this->contextModel = new ContextModel();
        $this->authModel = new AuthModel();
    }

    public function setCompany(): void
    {
        $companyId = (int) ($_POST['company_id'] ?? 0);
        if ($companyId <= 0 || !$this->contextModel->userOwnsCompany($this->user['id'], $companyId)) {
            AjaxResponseService::forbidden('Entreprise non autorisee.');
        }

        $this->contextModel->updateActiveCompany($this->user['id'], $companyId);
        $company = $this->contextModel->getCompany($companyId) ?? [];
        $brands = $this->contextModel->getBrandsForCompany($companyId);
        $_SESSION['active_company_id'] = $companyId;
        $_SESSION['active_concession_id'] = $companyId;
        $_SESSION['active_brand_id'] = null;
        $_SESSION['active_society_id'] = $companyId;
        $_SESSION['user']['active_company_id'] = $companyId;
        $_SESSION['user']['actual_society_id'] = $companyId;
        $_SESSION['user']['actual_concession_id'] = $companyId;
        $_SESSION['user']['actual_brand'] = null;
        $_SESSION['user']['active_brand_id'] = null;
        $_SESSION['society'] = $company;
        $_SESSION['concession'] = $company;
        $_SESSION['marque'] = [];
        $_SESSION['available_brands'] = $brands;
        $this->refreshAuthorizationContext($this->user['id'], $companyId, null);
        TenantEntitlementMiddleware::clearSessionCache($this->user['id'], $companyId);
        $this->updatePersistentSession($companyId, $companyId, null);

        AjaxResponseService::success('Contexte entreprise mis a jour.', [
            'company_id' => $companyId,
            'brands' => $brands,
        ]);
    }

    public function setBrand(): void
    {
        $brandId = (int) ($_POST['brand_id'] ?? 0);
        $companyId = (int) ($_SESSION['active_company_id'] ?? 0);
        if ($companyId <= 0) {
            AjaxResponseService::forbidden('Entreprise active manquante.');
        }
        if ($brandId <= 0) {
            $this->contextModel->updateActiveBrand($this->user['id'], null);
            $_SESSION['active_brand_id'] = null;
            $_SESSION['user']['actual_brand'] = null;
            $_SESSION['user']['active_brand_id'] = null;
            $_SESSION['marque'] = [];
            $this->refreshAuthorizationContext($this->user['id'], $companyId, null);
            TenantEntitlementMiddleware::clearSessionCache($this->user['id'], $companyId);
            $this->updatePersistentSession((int) ($_SESSION['active_company_id'] ?? 0), (int) ($_SESSION['active_concession_id'] ?? $_SESSION['active_company_id'] ?? 0), null);
            AjaxResponseService::success('Toutes les marques sont maintenant actives.', ['brand_id' => null]);
        }
        if (!$this->contextModel->companyHasBrand($companyId, $brandId)) {
            AjaxResponseService::forbidden('Marque non autorisee pour cette entreprise.');
        }

        $this->contextModel->updateActiveBrand($this->user['id'], $brandId);
        $_SESSION['active_brand_id'] = $brandId;
        $_SESSION['user']['actual_brand'] = $brandId;
        $_SESSION['user']['active_brand_id'] = $brandId;
        $_SESSION['marque'] = $this->contextModel->getCompany($brandId) ?? [];
        $this->refreshAuthorizationContext($this->user['id'], $companyId, $brandId);
        TenantEntitlementMiddleware::clearSessionCache($this->user['id'], $companyId);
        $this->updatePersistentSession((int) ($_SESSION['active_company_id'] ?? 0), (int) ($_SESSION['active_concession_id'] ?? $_SESSION['active_company_id'] ?? 0), $brandId);
        AjaxResponseService::success('Contexte marque mis a jour.', ['brand_id' => $brandId]);
    }

    public function brandsForCompany(): void
    {
        $companyId = (int) ($_GET['company_id'] ?? ($_SESSION['active_company_id'] ?? 0));
        if ($companyId <= 0 || !$this->contextModel->userOwnsCompany($this->user['id'], $companyId)) {
            AjaxResponseService::forbidden('Entreprise non autorisee.');
        }

        AjaxResponseService::success('Marques chargees.', [
            'brands' => $this->contextModel->getBrandsForCompany($companyId),
        ]);
    }

    private function updatePersistentSession(?int $companyId, ?int $concessionId, ?int $brandId): void
    {
        $sessionId = (int) ($_SESSION['auth_session_id'] ?? $_SESSION['session_id'] ?? $_SESSION['security']['session_db_id'] ?? $_SESSION['user']['session_id'] ?? 0);
        if ($sessionId <= 0) {
            return;
        }

        try {
            $this->contextModel->updatePersistentContext($sessionId, $companyId ?: null, $concessionId ?: null, $brandId);
        } catch (\Throwable) {
            // La session PHP reste la source active meme si la persistance DB est indisponible.
        }
    }

    private function refreshAuthorizationContext(int $userId, int $companyId, ?int $brandId = null): void
    {
        $roles = $this->authModel->listerRoles($userId, $companyId, null, $brandId);
        $permissions = $this->authModel->listerPermissions($userId, $companyId, null, $brandId);
        $sessionBlock = RoleResolver::buildSessionBlock($roles, $permissions);
        $roleCodes = $sessionBlock['role_codes'];
        $permissions = $sessionBlock['permissions'];

        $_SESSION['user']['roles'] = $roleCodes;
        $_SESSION['user']['role_codes'] = $roleCodes;
        $_SESSION['user']['role_names'] = $sessionBlock['role_names'];
        $_SESSION['user']['permissions'] = $permissions;
        $_SESSION['user']['level'] = $sessionBlock['level'];
        $_SESSION['permissions'] = $permissions;
        $_SESSION['user_roles'] = $roleCodes;
        $_SESSION['user_permissions'] = $permissions;
        $_SESSION['user_level'] = $sessionBlock['level'];
        $_SESSION['security']['roles_loaded_at'] = date('c');
        $_SESSION['security']['permissions_loaded_at'] = date('c');
    }

    private function roleCodes(array $roles): array
    {
        $codes = [];
        foreach ($roles as $role) {
            $code = trim((string) ($role['rol_code'] ?? ''));
            if ($code !== '') {
                $codes[] = $code;
            }
        }
        return array_values(array_unique($codes));
    }

    private function isSuperAdmin(array $roleCodes): bool
    {
        return in_array('super_administrateur', $roleCodes, true)
            || in_array('super_admin', $roleCodes, true)
            || (defined('ROLE_SUPERADMIN') && in_array((string) ROLE_SUPERADMIN, $roleCodes, true));
    }

    private function applicationLevel(array $roleCodes): int
    {
        if ($this->isSuperAdmin($roleCodes)) {
            return 100;
        }
        foreach ($roleCodes as $roleCode) {
            if (in_array($roleCode, ['administrateur_general_societe', 'administrateur_general', 'admin_general'], true)) {
                return 90;
            }
            if (in_array($roleCode, ['responsable_groupe_concessions', 'administrateur_departement', 'admin_departement'], true)) {
                return 80;
            }
            if (in_array($roleCode, ['directeur_concession', 'responsable_apres_vente', 'responsable_garantie', 'administrateur_service', 'admin_service'], true)) {
                return 70;
            }
            if (in_array($roleCode, ['administrateur_equipe', 'admin_equipe', 'manager'], true)) {
                return 60;
            }
        }
        return 10;
    }
}
