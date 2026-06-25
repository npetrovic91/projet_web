<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Core\Database\Database;

class RolesAjaxController extends AjaxController
{
    /**
     * GET /ajax/roles/creatable?company_id=X&module_id=Y
     * Retourne les rôles actifs assignables dans le contexte demandé.
     * Aligné sur sav_roles et sav_roles_contextuels_utilisateurs.
     */
    public function creatable(): void
    {
        $this->requireAuth();
        if (!has_permission('utilisateur.creer') && !has_permission('utilisateur.modifier')) {
            AjaxResponseService::forbidden('Permission insuffisante pour attribuer des roles.');
        }
        $db = Database::getInstance();

        $companyId = isset($_GET['company_id']) && (int) $_GET['company_id'] > 0 ? (int) $_GET['company_id'] : null;
        $moduleId = isset($_GET['module_id']) && (int) $_GET['module_id'] > 0 ? (int) $_GET['module_id'] : null;

        $where = [
            'r.rol_supprime_le IS NULL',
            'r.rol_archive_le IS NULL',
            '(st.sta_code IS NULL OR st.sta_code IN (\'actif\', \'active\'))',
        ];
        $params = [];

        if ($moduleId !== null) {
            $where[] = '(r.rol_module_id = :module_id OR r.rol_module_id IS NULL)';
            $params['module_id'] = $moduleId;
        }

        if ($companyId !== null && !has_role(ROLE_SUPERADMIN)) {
            $where[] = '(r.rol_societe_proprietaire_id IS NULL OR r.rol_societe_proprietaire_id = :company_id)';
            $params['company_id'] = $companyId;
        }
        if (!has_role(ROLE_SUPERADMIN)) {
            $where[] = "LOWER(r.rol_code) NOT IN ('super_administrateur', 'super_admin', 'superadmin')";
        }

        $roles = $db->fetchAll(
            "SELECT r.rol_id AS id,
                    r.rol_code AS code,
                    r.rol_nom AS label,
                    r.rol_module_id AS module_id,
                    m.mod_code AS module_code,
                    m.mod_nom AS module_label,
                    r.rol_societe_proprietaire_id AS company_id
               FROM sav_roles r
          LEFT JOIN sav_modules m ON m.mod_id = r.rol_module_id
          LEFT JOIN sav_statuts st ON st.sta_id = r.rol_statut_id
              WHERE " . implode(' AND ', $where) . "
           ORDER BY COALESCE(m.mod_nom, 'Noyau') ASC, r.rol_nom ASC",
            $params
        );

        AjaxResponseService::success('OK', ['roles' => $roles]);
    }
}
