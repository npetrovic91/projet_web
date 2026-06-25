<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Society\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

class ServiceAccesSocietes implements ServiceInterface
{
    /**
     * Rôles réellement globaux : accès hors périmètre société.
     * Les rôles métier restent limités aux sociétés présentes dans la session.
     */
    private array $rolesGlobaux = [
        'super_administrateur',
        'super_admin',
        'SUPERADMIN',
    ];

    public function peutLister(): bool
    {
        return $this->aUnRole($this->rolesGlobaux)
            || $this->aPermission('societe.consulter')
            || $this->aPermission('societe.modifier')
            || $this->aPermission('societe.bloquer')
            || $this->aPermission('company.read')
            || $this->aPermission('company.manage')
            || $this->aPermission('companies.read')
            || $this->aPermission('companies.manage')
            || $this->aPermission('admin.companies');
    }

    public function peutVoirSociete(int $idSociete): bool
    {
        if ($this->aUnRole($this->rolesGlobaux)) {
            return true;
        }

        return in_array($idSociete, $this->idsSocietesSession(), true);
    }

    public function peutCreer(): bool
    {
        // Règle métier : seul le super_admin peut créer société, marque, constructeur, importateur ou PDG.
        return $this->aUnRole($this->rolesGlobaux);
    }

    public function peutModifier(int $idSociete): bool
    {
        if (!$this->peutVoirSociete($idSociete)) {
            return false;
        }

        return $this->aUnRole($this->rolesGlobaux)
            || $this->aPermission('societe.modifier')
            || $this->aPermission('company.update')
            || $this->aPermission('company.manage')
            || $this->aPermission('companies.update')
            || $this->aPermission('companies.manage')
            || $this->aPermission('admin.companies');
    }

    public function peutSupprimer(int $idSociete): bool
    {
        if (!$this->peutVoirSociete($idSociete)) {
            return false;
        }

        return $this->aUnRole($this->rolesGlobaux)
            || $this->aPermission('societe.bloquer')
            || $this->aPermission('company.delete')
            || $this->aPermission('company.manage')
            || $this->aPermission('companies.delete')
            || $this->aPermission('companies.manage')
            || $this->aPermission('admin.companies');
    }

    public function filtresDePerimetre(): array
    {
        if ($this->aUnRole($this->rolesGlobaux)) {
            return [];
        }

        $ids = $this->idsSocietesSession();

        // Sécurité : un utilisateur non global sans société en session ne doit jamais voir toutes les sociétés.
        return ['ids_societes' => $ids !== [] ? $ids : [0]];
    }

    private function aUnRole(array $codes): bool
    {
        if (function_exists('has_role')) {
            return has_role($codes);
        }

        $roles = $_SESSION['user']['role_codes'] ?? $_SESSION['user']['roles'] ?? $_SESSION['user_roles'] ?? [];
        $roles = array_map(static fn($v) => strtolower((string) $v), (array) $roles);
        foreach ($codes as $code) {
            if (in_array(strtolower($code), $roles, true)) {
                return true;
            }
        }
        return false;
    }

    private function aPermission(string $permission): bool
    {
        if (function_exists('has_permission')) {
            return has_permission($permission);
        }

        $permissions = $_SESSION['user']['permissions'] ?? $_SESSION['user_permissions'] ?? [];
        return is_array($permissions) && in_array($permission, $permissions, true);
    }

    private function idsSocietesSession(): array
    {
        $ids = [];
        foreach (($_SESSION['user']['societes'] ?? []) as $societe) {
            foreach (['id', 'soc_id', 'com_id'] as $cle) {
                if (isset($societe[$cle])) {
                    $ids[] = (int) $societe[$cle];
                }
            }
        }
        foreach (['society', 'societe'] as $cleSession) {
            if (isset($_SESSION[$cleSession]['id'])) {
                $ids[] = (int) $_SESSION[$cleSession]['id'];
            }
            if (isset($_SESSION[$cleSession]['soc_id'])) {
                $ids[] = (int) $_SESSION[$cleSession]['soc_id'];
            }
        }
        foreach (['active_company_id', 'active_society_id'] as $cle) {
            if (isset($_SESSION[$cle])) {
                $ids[] = (int) $_SESSION[$cle];
            }
        }
        foreach (['active_company_id', 'active_society_id', 'uti_societe_active_id'] as $cle) {
            if (isset($_SESSION['user'][$cle])) {
                $ids[] = (int) $_SESSION['user'][$cle];
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
