<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Dashboard\Models;

/**
 * Catalogue de widgets Dashboard aligné sur la base SQL actuelle.
 *
 * Le dump de référence ne contient pas de tables persistantes dédiées aux widgets.
 * Le catalogue est donc applicatif et stable :
 * les données affichées par les widgets sont, elles, calculées depuis les
 * tables SQL réelles du noyau.
 */
class DashboardWidgetModel
{
    /**
     * @return array<int,array<string,mixed>>
     */
    public function getWidgetsForRoles(array $roles): array
    {
        $roles = array_values(array_unique(array_map('strval', $roles)));
        $widgets = array_values(array_filter($this->catalogue(), function (array $widget) use ($roles): bool {
            return $this->isWidgetAllowed($widget, $roles);
        }));

        usort($widgets, static function (array $a, array $b): int {
            return ((int) $a['dwi_default_order'] <=> (int) $b['dwi_default_order'])
                ?: strcmp((string) $a['dwi_label'], (string) $b['dwi_label']);
        });

        return $widgets;
    }

    public function getWidgetByCode(string $code): ?array
    {
        foreach ($this->catalogue() as $widget) {
            if (($widget['dwi_code'] ?? '') === $code && (int) ($widget['dwi_is_active'] ?? 0) === 1) {
                return $widget;
            }
        }
        return null;
    }

    /**
     * Les préférences utilisateur n’ont pas de table dans le dump SQL actuel.
     * La méthode reste présente pour compatibilité avec DashboardService.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getUserWidgetConfig(int $userId): array
    {
        return [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function catalogue(): array
    {
        return [
            [
                'dwi_id' => 1,
                'dwi_code' => 'profil',
                'dwi_label' => 'Mon profil',
                'dwi_view_file' => 'widget_profil.php',
                'dwi_default_order' => 10,
                'dwi_ajax_endpoint' => null,
                'dwi_roles_json' => json_encode(['*'], JSON_THROW_ON_ERROR),
                'dwi_is_active' => 1,
            ],
            [
                'dwi_id' => 2,
                'dwi_code' => 'notifications',
                'dwi_label' => 'Notifications',
                'dwi_view_file' => 'widget_notifications.php',
                'dwi_default_order' => 20,
                'dwi_ajax_endpoint' => null,
                'dwi_roles_json' => json_encode(['*'], JSON_THROW_ON_ERROR),
                'dwi_is_active' => 1,
            ],
            [
                'dwi_id' => 3,
                'dwi_code' => 'user_company_levels',
                'dwi_label' => 'Utilisateurs / sociétés / niveaux',
                'dwi_view_file' => 'widget_user_company_levels.php',
                'dwi_default_order' => 30,
                'dwi_ajax_endpoint' => null,
                'dwi_roles_json' => json_encode(['*'], JSON_THROW_ON_ERROR),
                'dwi_is_active' => 1,
            ],
            [
                'dwi_id' => 4,
                'dwi_code' => 'user',
                'dwi_label' => 'Accès rapides',
                'dwi_view_file' => 'widget_user.php',
                'dwi_default_order' => 40,
                'dwi_ajax_endpoint' => null,
                'dwi_roles_json' => json_encode(['*'], JSON_THROW_ON_ERROR),
                'dwi_is_active' => 1,
            ],
            [
                'dwi_id' => 5,
                'dwi_code' => 'manager',
                'dwi_label' => 'Vue manager',
                'dwi_view_file' => 'widget_manager.php',
                'dwi_default_order' => 50,
                'dwi_ajax_endpoint' => null,
                'dwi_roles_json' => json_encode(['manager', 'administrateur_equipe', 'administrateur_service', 'administrateur_departement', 'administrateur_general', 'super_administrateur', 'super_admin', 'SUPERADMIN'], JSON_THROW_ON_ERROR),
                'dwi_is_active' => 1,
            ],
            [
                'dwi_id' => 6,
                'dwi_code' => 'concessionnaire',
                'dwi_label' => 'Équipe société',
                'dwi_view_file' => 'widget_concessionnaire.php',
                'dwi_default_order' => 60,
                'dwi_ajax_endpoint' => null,
                'dwi_roles_json' => json_encode(['manager', 'administrateur_equipe', 'administrateur_service', 'administrateur_departement', 'administrateur_general', 'super_administrateur', 'super_admin', 'SUPERADMIN'], JSON_THROW_ON_ERROR),
                'dwi_is_active' => 1,
            ],
            [
                'dwi_id' => 7,
                'dwi_code' => 'supervision',
                'dwi_label' => 'Supervision globale',
                'dwi_view_file' => 'widget_superadmin.php',
                'dwi_default_order' => 70,
                'dwi_ajax_endpoint' => null,
                'dwi_roles_json' => json_encode(['super_administrateur', 'super_admin', 'SUPERADMIN'], JSON_THROW_ON_ERROR),
                'dwi_is_active' => 1,
            ],
            [
                'dwi_id' => 8,
                'dwi_code' => 'securite',
                'dwi_label' => 'Sécurité',
                'dwi_view_file' => 'widget_securite.php',
                'dwi_default_order' => 80,
                'dwi_ajax_endpoint' => '/ajax/dashboard/widget/securite',
                'dwi_roles_json' => json_encode(['super_administrateur', 'super_admin', 'SUPERADMIN'], JSON_THROW_ON_ERROR),
                'dwi_is_active' => 1,
            ],

            // ── Profils par type de société ────────────────────────────────────
            // Visibles uniquement quand la société active a le type correspondant.
            // Le DashboardController injecte 'type_constructeur', 'type_importateur',
            // 'type_marque' dans $effectiveRoles via getCompanyTypeCodes().

            [
                'dwi_id'            => 9,
                'dwi_code'          => 'profil_constructeur',
                'dwi_label'         => 'Profil Constructeur',
                'dwi_view_file'     => 'widget_profil_constructeur.php',
                'dwi_default_order' => 15,
                'dwi_ajax_endpoint' => null,
                'dwi_roles_json'    => json_encode(
                    ['type_constructeur', 'super_administrateur', 'super_admin', 'SUPERADMIN'],
                    JSON_THROW_ON_ERROR
                ),
                'dwi_is_active' => 1,
            ],
            [
                'dwi_id'            => 10,
                'dwi_code'          => 'profil_importateur',
                'dwi_label'         => 'Profil Importateur',
                'dwi_view_file'     => 'widget_profil_importateur.php',
                'dwi_default_order' => 16,
                'dwi_ajax_endpoint' => null,
                'dwi_roles_json'    => json_encode(
                    ['type_importateur', 'super_administrateur', 'super_admin', 'SUPERADMIN'],
                    JSON_THROW_ON_ERROR
                ),
                'dwi_is_active' => 1,
            ],
            [
                'dwi_id'            => 11,
                'dwi_code'          => 'profil_marque',
                'dwi_label'         => 'Profil Marque',
                'dwi_view_file'     => 'widget_profil_marque.php',
                'dwi_default_order' => 17,
                'dwi_ajax_endpoint' => null,
                'dwi_roles_json'    => json_encode(
                    ['type_marque', 'super_administrateur', 'super_admin', 'SUPERADMIN'],
                    JSON_THROW_ON_ERROR
                ),
                'dwi_is_active' => 1,
            ],
        ];
    }

    private function isWidgetAllowed(array $widget, array $roles): bool
    {
        if ((int) ($widget['dwi_is_active'] ?? 0) !== 1) {
            return false;
        }

        $allowed = json_decode((string) ($widget['dwi_roles_json'] ?? '[]'), true) ?: [];
        if (in_array('*', $allowed, true)) {
            return true;
        }

        if (in_array('super_administrateur', $roles, true)
            || in_array('super_admin', $roles, true)
            || in_array('SUPERADMIN', $roles, true)
        ) {
            return true;
        }

        foreach ($roles as $role) {
            if (in_array($role, $allowed, true)) {
                return true;
            }
        }

        return false;
    }
}
