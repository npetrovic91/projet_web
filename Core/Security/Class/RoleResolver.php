<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Security\Class;

final class RoleResolver
{
    public static function extractCodes(array $roles): array
    {
        $codes = [];
        foreach ($roles as $role) {
            $code = mb_strtolower(trim((string) ($role['rol_code'] ?? '')));
            if ($code !== '') {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    public static function extractNames(array $roles): array
    {
        return array_values(array_filter(array_map(
            static fn(array $role): string => trim((string) ($role['rol_nom'] ?? '')),
            $roles
        )));
    }

    public static function isSuperAdmin(array $roleCodes): bool
    {
        $superAdminCodes = ['super_administrateur', 'super_admin'];
        if (defined('ROLE_SUPERADMIN') && (string) ROLE_SUPERADMIN !== '') {
            $superAdminCodes[] = mb_strtolower((string) ROLE_SUPERADMIN);
        }

        foreach ($roleCodes as $code) {
            if (in_array(mb_strtolower((string) $code), $superAdminCodes, true)) {
                return true;
            }
        }

        return false;
    }

    public static function applicationLevel(array $roleCodes): int
    {
        if (self::isSuperAdmin($roleCodes)) {
            return 100;
        }

        $levels = [
            95 => ['pdg'],
            90 => ['administrateur_general_societe', 'administrateur_general', 'admin_general'],
            85 => ['responsable_groupe_concessions', 'administrateur_groupe_concessions', 'admin_groupe'],
            80 => ['administrateur_marque', 'admin_marque', 'chef_de_service', 'administrateur_departement', 'admin_departement'],
            70 => ['directeur_concession', 'responsable_apres_vente', 'responsable_garantie', 'chef_de_departement', 'administrateur_service', 'admin_service'],
            60 => ['chef_d_equipe', 'chef_equipe', 'administrateur_equipe', 'admin_equipe', 'manager'],
            50 => ['expert_technique', 'receptionnaire'],
            40 => ['technicien', 'technicien_sav', 'carrossier', 'peintre', 'conseiller_service', 'gestionnaire_pieces'],
        ];

        $best = 10;
        foreach ($roleCodes as $code) {
            $normalized = mb_strtolower((string) $code);
            foreach ($levels as $level => $codes) {
                if ($level > $best && in_array($normalized, $codes, true)) {
                    $best = $level;
                }
            }
        }

        return $best;
    }

    public static function buildSessionBlock(array $roles, array $permissions): array
    {
        $roleCodes = self::extractCodes($roles);
        if (self::isSuperAdmin($roleCodes)) {
            $permissions = array_values(array_unique(array_merge(['*'], $permissions)));
        }

        return [
            'role_codes' => $roleCodes,
            'role_names' => self::extractNames($roles),
            'permissions' => array_values(array_unique($permissions)),
            'level' => self::applicationLevel($roleCodes),
            'is_super_admin' => self::isSuperAdmin($roleCodes),
        ];
    }
}
