<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Roles\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Roles\Models\RolePermissionModel;

class RolePermissionService implements ServiceInterface{
    public function __construct(private readonly RolePermissionModel $model = new RolePermissionModel())
    {
    }

    public function model(): RolePermissionModel
    {
        return $this->model;
    }

    /** @return array{success:bool,errors:array<int,string>,id?:int} */
    public function creerRole(array $data, ?int $utilisateurId): array
    {
        $errors = $this->validerRole($data);
        if ($errors === [] && $this->model->codeRoleExiste((string) $data['rol_code'])) {
            $errors[] = 'Ce code rôle existe déjà.';
        }
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }
        $id = $this->model->creerRole($data, $utilisateurId);
        return ['success' => true, 'errors' => [], 'id' => $id];
    }

    /** @return array{success:bool,errors:array<int,string>} */
    public function modifierRole(int $id, array $data, ?int $utilisateurId): array
    {
        $errors = $this->validerRole($data);
        if ($errors === [] && $this->model->codeRoleExiste((string) $data['rol_code'], $id)) {
            $errors[] = 'Ce code rôle est déjà utilisé par un autre rôle.';
        }
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }
        $this->model->modifierRole($id, $data, $utilisateurId);
        return ['success' => true, 'errors' => []];
    }

    /**
     * Valide le contexte de création/modification d'un rôle selon les droits métier :
     *  - Portée plateforme réservée au super_admin.
     *  - Type de société doit appartenir aux types autorisés pour l'utilisateur.
     *
     * @param  bool  $peutCreerGlobal   true si super_admin
     * @param  int[] $typesAutorises    tso_id autorisés pour l'utilisateur (vide = aucun filtre)
     * @return array<int,string>        Erreurs métier
     */
    public function validerContexteRole(array $data, bool $peutCreerGlobal, array $typesAutorises = []): array
    {
        $errors = [];

        // Règle de portée plateforme
        $scope = trim((string) ($data['rol_portee_code'] ?? 'interne'));
        if ($scope === 'plateforme' && !$peutCreerGlobal) {
            $errors[] = 'Seul un super-administrateur peut créer un rôle de portée plateforme.';
        }

        // Règle type de société
        $typeId = (int) ($data['rol_type_societe_id'] ?? 0);
        if ($typeId > 0 && $typesAutorises !== [] && !in_array($typeId, $typesAutorises, true)) {
            $errors[] = 'Le type de société sélectionné ne correspond pas aux types de votre société.';
        }

        return $errors;
    }

    /** @return array{success:bool,errors:array<int,string>,id?:int} */
    public function creerPermission(array $data, ?int $utilisateurId): array
    {
        $errors = $this->validerPermission($data);
        if ($errors === [] && $this->model->codePermissionExiste((string) $data['per_code'])) {
            $errors[] = 'Ce code permission existe déjà.';
        }
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }
        $id = $this->model->creerPermission($data, $utilisateurId);
        return ['success' => true, 'errors' => [], 'id' => $id];
    }

    /** @return array{success:bool,errors:array<int,string>} */
    public function modifierPermission(int $id, array $data, ?int $utilisateurId): array
    {
        $errors = $this->validerPermission($data);
        if ($errors === [] && $this->model->codePermissionExiste((string) $data['per_code'], $id)) {
            $errors[] = 'Ce code permission est déjà utilisé.';
        }
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }
        $this->model->modifierPermission($id, $data, $utilisateurId);
        return ['success' => true, 'errors' => []];
    }

    /** @return array<int,string> */
    public function extraireEffetsPermissions(array $data): array
    {
        $source = $data['permissions'] ?? [];
        if (!is_array($source)) {
            return [];
        }

        $effets = [];
        foreach ($source as $permissionId => $effet) {
            $permissionId = (int) $permissionId;
            $effet = (string) $effet;
            if ($permissionId <= 0 || !in_array($effet, ['autoriser', 'refuser'], true)) {
                continue;
            }
            $effets[$permissionId] = $effet;
        }
        return $effets;
    }

    /** @return array<int,string> */
    private function validerRole(array $data): array
    {
        $errors = [];
        if (trim((string) ($data['rol_code'] ?? '')) === '') {
            $errors[] = 'Le code du rôle est obligatoire.';
        }
        if (trim((string) ($data['rol_nom'] ?? '')) === '') {
            $errors[] = 'Le nom du rôle est obligatoire.';
        }
        return $errors;
    }

    /** @return array<int,string> */
    private function validerPermission(array $data): array
    {
        $errors = [];
        if (trim((string) ($data['per_code'] ?? '')) === '') {
            $errors[] = 'Le code de permission est obligatoire.';
        }
        return $errors;
    }
}
