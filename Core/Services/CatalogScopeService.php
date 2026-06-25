<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Services;

use Nenad\Autosav\Core\Database\Database;

/**
 * Portee metier des catalogues referentiels : fonctions, roles, competences,
 * certifications.
 *
 * Version V3 defensive : aucune erreur SQL de calcul de perimetre ne doit
 * provoquer une erreur 500 sur les ecrans /functions, /roles, /admin/skills,
 * /admin/qualifications. En cas de doute, on limite au perimetre interne.
 */
class CatalogScopeService
{
    public const SCOPE_INTERNAL = 'interne';
    public const SCOPE_NETWORK  = 'reseau';
    public const SCOPE_PLATFORM = 'plateforme';

    private Database $db;
    /** @var array<string,bool> */
    private array $tableExistsCache = [];
    /** @var array<string,array<int,string>> */
    private array $columnsCache = [];

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public static function canManageCurrentUser(): bool
    {
        if (function_exists('has_role') && has_role(['super_administrateur', 'super_admin', 'SUPERADMIN', 'pdg', 'chef_de_service'])) {
            return true;
        }

        // Fallback utile quand la session contient deja les permissions mais pas encore les nouveaux roles.
        return function_exists('has_permission') && has_permission([
            'functions.manage', 'fonction.gerer',
            'roles.manage', 'role.gerer',
            'skills.manage', 'competence.gerer',
            'qualifications.manage', 'certification.gerer',
        ]);
    }

    public static function currentUserIsSuperAdmin(): bool
    {
        return function_exists('has_role') && has_role(['super_administrateur', 'super_admin', 'SUPERADMIN']);
    }

    public function companyTypeCodes(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }

        if (!$this->tableExists('sav_types_societes') || !$this->tableExists('sav_affectations_types_societes')) {
            return [];
        }

        try {
            $rows = $this->db->fetchAll(
                "SELECT DISTINCT ts.tso_code
                   FROM sav_types_societes ts
                   JOIN sav_affectations_types_societes ats
                     ON ats.ats_type_societe_id = ts.tso_id
                  WHERE ats.ats_societe_id = :company_id
                    AND ats.ats_supprime_le IS NULL
                    AND ats.ats_archive_le IS NULL
                    AND (ats.ats_termine_le IS NULL OR ats.ats_termine_le >= CURDATE())
                    AND ts.tso_supprime_le IS NULL
                    AND ts.tso_archive_le IS NULL",
                ['company_id' => $companyId]
            );
        } catch (\Throwable $e) {
            $this->safeLog('catalog_scope.company_types_failed', $e);
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn(array $row): string => mb_strtolower((string) ($row['tso_code'] ?? '')),
            $rows
        ))));
    }

    public function isManufacturerOrBrand(int $companyId): bool
    {
        return (bool) array_intersect($this->companyTypeCodes($companyId), ['constructeur', 'marque']);
    }

    public function isImporter(int $companyId): bool
    {
        return in_array('importateur', $this->companyTypeCodes($companyId), true);
    }

    public function isConcession(int $companyId): bool
    {
        return in_array('concession', $this->companyTypeCodes($companyId), true);
    }

    /** @return array<string,string> */
    public function allowedPublicationScopes(int $companyId, bool $isSuperAdmin): array
    {
        if ($isSuperAdmin) {
            return [
                self::SCOPE_PLATFORM => 'Plateforme globale',
                self::SCOPE_INTERNAL => 'Interne societe active',
                self::SCOPE_NETWORK  => 'Reseau rattache',
            ];
        }

        if ($companyId <= 0) {
            return [self::SCOPE_INTERNAL => 'Interne societe'];
        }

        if ($this->isManufacturerOrBrand($companyId) || $this->isImporter($companyId)) {
            return [
                self::SCOPE_INTERNAL => 'Interne societe',
                self::SCOPE_NETWORK  => 'Reseau rattache',
            ];
        }

        return [self::SCOPE_INTERNAL => 'Interne societe'];
    }

    public function normalizeRequestedScope(?string $requested, int $companyId, bool $isSuperAdmin): string
    {
        $requested = mb_strtolower(trim((string) $requested));
        if ($requested === '' || !in_array($requested, [self::SCOPE_INTERNAL, self::SCOPE_NETWORK, self::SCOPE_PLATFORM], true)) {
            $requested = self::SCOPE_INTERNAL;
        }

        $allowed = array_keys($this->allowedPublicationScopes($companyId, $isSuperAdmin));
        if (!in_array($requested, $allowed, true)) {
            return self::SCOPE_INTERNAL;
        }

        return $requested;
    }

    public function ownerCompanyForScope(string $scope, ?int $companyId, bool $isSuperAdmin): ?int
    {
        if ($isSuperAdmin && $scope === self::SCOPE_PLATFORM) {
            return null;
        }

        return ($companyId ?? 0) > 0 ? (int) $companyId : null;
    }

    /** @return array<int,int> */
    public function networkOwnerCompanyIds(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }

        $ids = [$companyId];

        if ($this->tableExists('sav_representations_marques_societes')) {
            try {
                $representationRows = $this->db->fetchAll(
                    "SELECT DISTINCT x.owner_id
                       FROM (
                             SELECT rma_constructeur_societe_id AS owner_id
                               FROM sav_representations_marques_societes
                              WHERE rma_concession_societe_id = :company_id_a
                                 OR rma_importateur_societe_id = :company_id_b
                                 OR rma_marque_societe_id = :company_id_c
                             UNION ALL
                             SELECT rma_marque_societe_id AS owner_id
                               FROM sav_representations_marques_societes
                              WHERE rma_concession_societe_id = :company_id_d
                                 OR rma_importateur_societe_id = :company_id_e
                             UNION ALL
                             SELECT rma_importateur_societe_id AS owner_id
                               FROM sav_representations_marques_societes
                              WHERE rma_concession_societe_id = :company_id_f
                       ) x
                      WHERE x.owner_id IS NOT NULL",
                    [
                        'company_id_a' => $companyId,
                        'company_id_b' => $companyId,
                        'company_id_c' => $companyId,
                        'company_id_d' => $companyId,
                        'company_id_e' => $companyId,
                        'company_id_f' => $companyId,
                    ]
                );
                foreach ($representationRows as $row) {
                    $id = (int) ($row['owner_id'] ?? 0);
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
            } catch (\Throwable $e) {
                $this->safeLog('catalog_scope.representations_failed', $e);
            }
        }

        if ($this->tableExists('sav_relations_societes') && $this->tableExists('sav_types_relations_societes')) {
            try {
                $relationRows = $this->db->fetchAll(
                    "SELECT DISTINCT rso.rso_societe_source_id AS owner_id
                       FROM sav_relations_societes rso
                       JOIN sav_types_relations_societes tre ON tre.tre_id = rso.rso_type_relation_societe_id
                      WHERE rso.rso_societe_cible_id = :company_id
                        AND rso.rso_supprime_le IS NULL
                        AND rso.rso_archive_le IS NULL
                        AND (rso.rso_termine_le IS NULL OR rso.rso_termine_le >= CURDATE())
                        AND tre.tre_code IN ('groupe_pilote_concession', 'importateur_pilote_concession', 'importateur_rattache_concession')",
                    ['company_id' => $companyId]
                );
                foreach ($relationRows as $row) {
                    $id = (int) ($row['owner_id'] ?? 0);
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
            } catch (\Throwable $e) {
                $this->safeLog('catalog_scope.relations_failed', $e);
            }
        }

        return array_values(array_unique(array_filter($ids, static fn(int $id): bool => $id > 0)));
    }

    /** @return array<int,int> */
    public function editableOwnerCompanyIds(int $companyId, bool $isSuperAdmin): array
    {
        if ($isSuperAdmin) {
            return [];
        }
        return $companyId > 0 ? [$companyId] : [];
    }

    public static function isNetworkScope(?string $scope): bool
    {
        return mb_strtolower(trim((string) $scope)) === self::SCOPE_NETWORK;
    }

    public static function isPlatformScope(?string $scope): bool
    {
        return mb_strtolower(trim((string) $scope)) === self::SCOPE_PLATFORM;
    }

    private function tableExists(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        try {
            $exists = (int) $this->db->fetchColumn(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name',
                ['table_name' => $table]
            ) > 0;
        } catch (\Throwable $e) {
            $exists = false;
            $this->safeLog('catalog_scope.table_exists_failed', $e);
        }

        return $this->tableExistsCache[$table] = $exists;
    }

    private function safeLog(string $message, \Throwable $e): void
    {
        try {
            error_log('[AUTOSAV][CatalogScope] ' . $message . ' : ' . $e->getMessage());
        } catch (\Throwable) {
            // Ne jamais bloquer l'application pour un log.
        }
    }
}
