<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Roles\Models;

use Nenad\Autosav\Core\Model\BaseModel;
use PDO;

/**
 * Modele RBAC / ABAC aligne sur le dump SQL courant.
 *
 * Tables utilisees :
 * - sav_roles
 * - sav_permissions
 * - sav_roles_permissions
 * - sav_roles_contextuels_utilisateurs
 * - sav_politiques_acces
 * - sav_conditions_politiques_acces
 */
class RolePermissionModel extends BaseModel
{
    protected string $table = 'sav_roles';
    protected string $colPrefix = 'rol_';

    /**
     * AUDIT 2026-06-21 — correctif point 4.5 : sav_journaux_audit n'était jamais
     * alimentée par le module Roles, alors que "changement de rôle" et la
     * définition des permissions associées sont parmi les actions les plus
     * sensibles listées par le cahier des charges. Même convention que les
     * autres modules (cf. Modules/Notes/Models/NotesModel.php::audit()).
     */
    private function audit(?int $userId, string $action, string $table, int $targetId, array $meta = []): void
    {
        try {
            $this->db->execute(
                "INSERT INTO sav_journaux_audit
                    (jau_utilisateur_id, jau_societe_id, jau_action, jau_table_cible, jau_id_cible,
                     jau_adresse_ip, jau_user_agent, jau_metadata_json, jau_cree_le)
                 VALUES
                    (:user_id, :societe_id, :action, :table_cible, :id_cible,
                     INET6_ATON(:ip), :user_agent, :metadata, NOW())",
                [
                    'user_id' => $userId ?: null,
                    'societe_id' => $_SESSION['active_company_id'] ?? ($_SESSION['user']['actual_society_id'] ?? null),
                    'action' => $action,
                    'table_cible' => $table,
                    'id_cible' => $targetId,
                    'ip' => function_exists('client_ip') ? client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
                    'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                    'metadata' => json_encode(['module' => 'Roles'] + $meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable $e) {
            if (function_exists('logger')) {
                logger('audit')->error('Échec écriture sav_journaux_audit (Roles)', [
                    'action' => $action, 'target_id' => $targetId, 'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Invalide immédiatement le cache de droits en session de tous les
     * utilisateurs porteurs actifs d'un rôle (cf. AuthMiddleware::refreshAclIfNeeded).
     * Indispensable ici : une modification des permissions d'un rôle impacte
     * potentiellement de nombreux utilisateurs en une seule opération.
     */
    private function invaliderAclPourRole(int $roleId): void
    {
        $this->db->execute(
            "UPDATE sav_utilisateurs u
                INNER JOIN sav_roles_contextuels_utilisateurs rcu ON rcu.rcu_utilisateur_id = u.uti_id
                SET u.uti_acl_version = u.uti_acl_version + 1
              WHERE rcu.rcu_role_id = :role_id
                AND rcu.rcu_archive_le IS NULL
                AND rcu.rcu_supprime_le IS NULL",
            ['role_id' => $roleId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function listerRoles(array $filtres = []): array
    {
        $where = ['r.rol_supprime_le IS NULL', 'r.rol_archive_le IS NULL'];
        $params = [];

        if (!empty($filtres['q'])) {
            $where[] = '(r.rol_code LIKE :q OR r.rol_nom LIKE :q OR r.rol_description LIKE :q)';
            $params['q'] = '%' . trim((string) $filtres['q']) . '%';
        }
        if (!empty($filtres['module_id'])) {
            $where[] = 'r.rol_module_id = :module_id';
            $params['module_id'] = (int) $filtres['module_id'];
        }
        if (!empty($filtres['societe_id'])) {
            $where[] = 'r.rol_societe_proprietaire_id = :societe_id';
            $params['societe_id'] = (int) $filtres['societe_id'];
        }
        if (array_key_exists('societe_ids_autorisees', $filtres) && is_array($filtres['societe_ids_autorisees'])) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $filtres['societe_ids_autorisees']))));
            $networkIds = array_values(array_unique(array_filter(array_map('intval', $filtres['network_societe_ids_autorisees'] ?? []))));
            if ($ids === [] && $networkIds === []) {
                $where[] = '1 = 0';
            } else {
                $scopeParts = [];
                if ($ids !== []) {
                    $placeholders = [];
                    foreach ($ids as $index => $id) {
                        $key = 'scope_societe_' . $index;
                        $placeholders[] = ':' . $key;
                        $params[$key] = $id;
                    }
                    $scopeParts[] = 'r.rol_societe_proprietaire_id IN (' . implode(', ', $placeholders) . ')';
                }
                if ($networkIds !== [] && in_array('rol_portee_code', $this->tableColumns(), true)) {
                    $placeholders = [];
                    foreach ($networkIds as $index => $id) {
                        $key = 'network_scope_societe_' . $index;
                        $placeholders[] = ':' . $key;
                        $params[$key] = $id;
                    }
                    $scopeParts[] = "(r.rol_societe_proprietaire_id IN (" . implode(', ', $placeholders) . ") AND r.rol_portee_code = 'reseau')";
                }
                $where[] = '(' . implode(' OR ', $scopeParts) . ')';
            }
        }

        $scopeExpr = $this->roleScopeSelect('r');

        return $this->db->fetchAll(
            "SELECT r.*,
                    st.sta_code AS statut_code,
                    st.sta_libelle AS statut_libelle,
                    m.mod_code,
                    m.mod_nom,
                    s.soc_nom AS societe_proprietaire_nom,
                    ts.tso_nom AS type_societe_nom,
                    " . $scopeExpr . " AS rol_portee_code,
                    CASE WHEN r.rol_societe_proprietaire_id IS NULL THEN 'plateforme' ELSE " . $scopeExpr . " END AS rol_scope,
                    (SELECT COUNT(*)
                       FROM sav_roles_contextuels_utilisateurs rcu
                      WHERE rcu.rcu_role_id = r.rol_id
                        AND rcu.rcu_supprime_le IS NULL
                        AND rcu.rcu_archive_le IS NULL
                        AND (rcu.rcu_termine_le IS NULL OR rcu.rcu_termine_le >= CURDATE())) AS utilisateurs_count,
                    (SELECT COUNT(*)
                       FROM sav_roles_permissions rpe
                      WHERE rpe.rpe_role_id = r.rol_id
                        AND rpe.rpe_effet = 'autoriser'
                        AND rpe.rpe_supprime_le IS NULL) AS permissions_autorisees_count,
                    (SELECT COUNT(*)
                       FROM sav_roles_permissions rpe
                      WHERE rpe.rpe_role_id = r.rol_id
                        AND rpe.rpe_effet = 'refuser'
                        AND rpe.rpe_supprime_le IS NULL) AS permissions_refusees_count
               FROM sav_roles r
          LEFT JOIN sav_statuts st ON st.sta_id = r.rol_statut_id
          LEFT JOIN sav_modules m ON m.mod_id = r.rol_module_id
          LEFT JOIN sav_societes s ON s.soc_id = r.rol_societe_proprietaire_id
          LEFT JOIN sav_types_societes ts ON ts.tso_id = r.rol_type_societe_id
              WHERE " . implode(' AND ', $where) . "
           ORDER BY COALESCE(m.mod_nom, 'Noyau') ASC, r.rol_nom ASC",
            $params
        );
    }

    public function trouverRole(int $id): ?array
    {
        $scopeExpr = $this->roleScopeSelect('r');

        return $this->db->fetch(
            "SELECT r.*,
                    st.sta_code AS statut_code,
                    st.sta_libelle AS statut_libelle,
                    m.mod_code,
                    m.mod_nom,
                    s.soc_nom AS societe_proprietaire_nom,
                    ts.tso_nom AS type_societe_nom,
                    " . $scopeExpr . " AS rol_portee_code,
                    CASE WHEN r.rol_societe_proprietaire_id IS NULL THEN 'plateforme' ELSE " . $scopeExpr . " END AS rol_scope
               FROM sav_roles r
          LEFT JOIN sav_statuts st ON st.sta_id = r.rol_statut_id
          LEFT JOIN sav_modules m ON m.mod_id = r.rol_module_id
          LEFT JOIN sav_societes s ON s.soc_id = r.rol_societe_proprietaire_id
          LEFT JOIN sav_types_societes ts ON ts.tso_id = r.rol_type_societe_id
              WHERE r.rol_id = :id
                AND r.rol_supprime_le IS NULL
                AND r.rol_archive_le IS NULL
              LIMIT 1",
            ['id' => $id]
        );
    }

    public function roleDansSocietesAutorisees(int $id, array $societeIds): bool
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $societeIds))));
        if ($ids === []) {
            return false;
        }
        $placeholders = [];
        $params = ['id' => $id];
        foreach ($ids as $index => $societeId) {
            $key = 'societe_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $societeId;
        }
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*)
               FROM sav_roles
              WHERE rol_id = :id
                AND rol_societe_proprietaire_id IN (" . implode(', ', $placeholders) . ")
                AND rol_supprime_le IS NULL
                AND rol_archive_le IS NULL",
            $params
        ) > 0;
    }

    /** @return array<int,array<string,mixed>> */
    public function permissionsDuRole(int $roleId): array
    {
        return $this->db->fetchAll(
            "SELECT p.*,
                    m.mod_code,
                    m.mod_nom,
                    rpe.rpe_id,
                    rpe.rpe_effet
               FROM sav_permissions p
          LEFT JOIN sav_modules m ON m.mod_id = p.per_module_id
          LEFT JOIN sav_roles_permissions rpe
                 ON rpe.rpe_permission_id = p.per_id
                AND rpe.rpe_role_id = :role_id
                AND rpe.rpe_supprime_le IS NULL
              WHERE p.per_supprime_le IS NULL
                AND p.per_archive_le IS NULL
           ORDER BY COALESCE(m.mod_nom, 'Noyau') ASC, p.per_code ASC",
            ['role_id' => $roleId]
        );
    }

    public function creerRole(array $data, ?int $utilisateurId): int
    {
        $payload = $this->filterColumns([
            'rol_code' => $this->normaliserCode((string) $data['rol_code']),
            'rol_nom' => trim((string) $data['rol_nom']),
            'rol_description' => $this->nullableString($data['rol_description'] ?? null),
            'rol_societe_proprietaire_id' => $this->nullableInt($data['rol_societe_proprietaire_id'] ?? null),
            'rol_type_societe_id' => $this->nullableInt($data['rol_type_societe_id'] ?? null),
            'rol_module_id' => $this->nullableInt($data['rol_module_id'] ?? null),
            'rol_portee_code' => $data['rol_portee_code'] ?? 'interne',
            'rol_statut_id' => $this->nullableInt($data['rol_statut_id'] ?? null) ?? $this->statutId('general', 'actif'),
            'rol_cree_par_utilisateur_id' => $utilisateurId,
        ]);

        $this->insertDynamic('sav_roles', $payload);
        $id = (int) $this->db->lastInsertId();

        $this->audit($utilisateurId, 'role.creation', 'sav_roles', $id, ['code' => $payload['rol_code'] ?? null]);

        return $id;
    }

    public function modifierRole(int $id, array $data, ?int $utilisateurId): bool
    {
        $payload = $this->filterColumns([
            'rol_code' => $this->normaliserCode((string) $data['rol_code']),
            'rol_nom' => trim((string) $data['rol_nom']),
            'rol_description' => $this->nullableString($data['rol_description'] ?? null),
            'rol_societe_proprietaire_id' => $this->nullableInt($data['rol_societe_proprietaire_id'] ?? null),
            'rol_type_societe_id' => $this->nullableInt($data['rol_type_societe_id'] ?? null),
            'rol_module_id' => $this->nullableInt($data['rol_module_id'] ?? null),
            'rol_portee_code' => $data['rol_portee_code'] ?? 'interne',
            'rol_statut_id' => $this->nullableInt($data['rol_statut_id'] ?? null) ?? $this->statutId('general', 'actif'),
            'rol_modifie_par_utilisateur_id' => $utilisateurId,
            'rol_modifie_le' => date('Y-m-d H:i:s'),
        ]);

        $result = $this->updateDynamic('sav_roles', 'rol_id', $id, $payload);

        if ($result) {
            // La portée, le module ou le statut d'un rôle peuvent changer ce que ses
            // porteurs sont autorisés à faire : invalidation immédiate, comme pour
            // une resynchronisation de permissions.
            $this->invaliderAclPourRole($id);
            $this->audit($utilisateurId, 'role.modification', 'sav_roles', $id, ['champs' => array_keys($payload)]);
        }

        return $result;
    }

    public function supprimerRole(int $id, ?int $utilisateurId): bool
    {
        $result = $this->db->execute(
            "UPDATE sav_roles
                SET rol_supprime_le = NOW(),
                    rol_supprime_par_utilisateur_id = :uid,
                    rol_statut_id = :statut
              WHERE rol_id = :id
                AND rol_supprime_le IS NULL",
            ['id' => $id, 'uid' => $utilisateurId, 'statut' => $this->statutId('general', 'supprime')]
        );

        if ($result) {
            $this->invaliderAclPourRole($id);
            $this->audit($utilisateurId, 'role.suppression', 'sav_roles', $id);
        }

        return $result;
    }

    public function codeRoleExiste(string $code, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM sav_roles
                 WHERE rol_code = :code
                   AND rol_supprime_le IS NULL
                   AND rol_archive_le IS NULL";
        $params = ['code' => $this->normaliserCode($code)];
        if ($excludeId !== null) {
            $sql .= ' AND rol_id <> :id';
            $params['id'] = $excludeId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    /**
     * Synchronise les permissions explicites d'un role.
     * @param array<int,string> $effets Par permission_id : autoriser|refuser
     */
    public function synchroniserPermissionsRole(int $roleId, array $effets, ?int $utilisateurId): void
    {
        $avant = $this->db->fetchAll(
            "SELECT rpe_permission_id, rpe_effet FROM sav_roles_permissions
              WHERE rpe_role_id = :role_id AND rpe_supprime_le IS NULL",
            ['role_id' => $roleId]
        );

        $this->db->transaction(function () use ($roleId, $effets, $utilisateurId): void {
            $this->db->execute(
                "UPDATE sav_roles_permissions
                    SET rpe_supprime_le = NOW(),
                        rpe_supprime_par_utilisateur_id = :uid
                  WHERE rpe_role_id = :role_id
                    AND rpe_supprime_le IS NULL",
                ['role_id' => $roleId, 'uid' => $utilisateurId]
            );

            $insert = $this->pdo->prepare(
                "INSERT INTO sav_roles_permissions
                    (rpe_role_id, rpe_permission_id, rpe_effet, rpe_cree_par_utilisateur_id)
                 VALUES
                    (:role_id, :permission_id, :effet, :uid)"
            );

            foreach ($effets as $permissionId => $effet) {
                $permissionId = (int) $permissionId;
                $effet = $effet === 'refuser' ? 'refuser' : ($effet === 'autoriser' ? 'autoriser' : '');
                if ($permissionId <= 0 || $effet === '') {
                    continue;
                }
                $insert->bindValue(':role_id', $roleId, PDO::PARAM_INT);
                $insert->bindValue(':permission_id', $permissionId, PDO::PARAM_INT);
                $insert->bindValue(':effet', $effet, PDO::PARAM_STR);
                $insert->bindValue(':uid', $utilisateurId, $utilisateurId ? PDO::PARAM_INT : PDO::PARAM_NULL);
                $insert->execute();
            }
        });

        // AUDIT 2026-06-21 — correctifs points 4.5 et 4.6 :
        // C'est l'opération la plus sensible de tout le moteur RBAC (deny > allow
        // par permission, cf. cahier des charges) et elle n'était jusqu'ici jamais
        // journalisée. Elle peut aussi affecter de nombreux utilisateurs en une
        // seule fois : invalidation immédiate de leur cache de droits en session.
        $avantMap = [];
        foreach ($avant as $row) {
            $avantMap[(int) $row['rpe_permission_id']] = (string) $row['rpe_effet'];
        }
        $this->invaliderAclPourRole($roleId);
        $this->audit($utilisateurId, 'role.permissions.synchronisation', 'sav_roles_permissions', $roleId, [
            'avant' => $avantMap,
            'apres' => $effets,
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    public function listerPermissions(array $filtres = []): array
    {
        $where = ['p.per_supprime_le IS NULL', 'p.per_archive_le IS NULL'];
        $params = [];
        if (!empty($filtres['q'])) {
            $where[] = '(p.per_code LIKE :q OR p.per_description LIKE :q)';
            $params['q'] = '%' . trim((string) $filtres['q']) . '%';
        }
        if (!empty($filtres['module_id'])) {
            $where[] = 'p.per_module_id = :module_id';
            $params['module_id'] = (int) $filtres['module_id'];
        }

        return $this->db->fetchAll(
            "SELECT p.*,
                    st.sta_code AS statut_code,
                    st.sta_libelle AS statut_libelle,
                    m.mod_code,
                    m.mod_nom,
                    (SELECT COUNT(*)
                       FROM sav_roles_permissions rpe
                      WHERE rpe.rpe_permission_id = p.per_id
                        AND rpe.rpe_effet = 'autoriser'
                        AND rpe.rpe_supprime_le IS NULL) AS roles_autorisant_count,
                    (SELECT COUNT(*)
                       FROM sav_roles_permissions rpe
                      WHERE rpe.rpe_permission_id = p.per_id
                        AND rpe.rpe_effet = 'refuser'
                        AND rpe.rpe_supprime_le IS NULL) AS roles_refusant_count
               FROM sav_permissions p
          LEFT JOIN sav_statuts st ON st.sta_id = p.per_statut_id
          LEFT JOIN sav_modules m ON m.mod_id = p.per_module_id
              WHERE " . implode(' AND ', $where) . "
           ORDER BY COALESCE(m.mod_nom, 'Noyau') ASC, p.per_code ASC",
            $params
        );
    }

    public function trouverPermission(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT p.*, st.sta_code AS statut_code, st.sta_libelle AS statut_libelle, m.mod_code, m.mod_nom
               FROM sav_permissions p
          LEFT JOIN sav_statuts st ON st.sta_id = p.per_statut_id
          LEFT JOIN sav_modules m ON m.mod_id = p.per_module_id
              WHERE p.per_id = :id
                AND p.per_supprime_le IS NULL
                AND p.per_archive_le IS NULL
              LIMIT 1",
            ['id' => $id]
        );
    }

    public function creerPermission(array $data, ?int $utilisateurId): int
    {
        $code = $this->normaliserPermission((string) $data['per_code']);
        $this->db->execute(
            "INSERT INTO sav_permissions
                (per_code, per_description, per_module_id, per_statut_id, per_cree_par_utilisateur_id)
             VALUES
                (:code, :description, :module_id, :statut_id, :cree_par)",
            [
                'code' => $code,
                'description' => $this->nullableString($data['per_description'] ?? null),
                'module_id' => $this->nullableInt($data['per_module_id'] ?? null),
                'statut_id' => $this->nullableInt($data['per_statut_id'] ?? null) ?? $this->statutId('general', 'actif'),
                'cree_par' => $utilisateurId,
            ]
        );

        $id = (int) $this->db->lastInsertId();
        $this->audit($utilisateurId, 'permission.creation', 'sav_permissions', $id, ['code' => $code]);

        return $id;
    }

    public function modifierPermission(int $id, array $data, ?int $utilisateurId): bool
    {
        $result = $this->db->execute(
            "UPDATE sav_permissions
                SET per_code = :code,
                    per_description = :description,
                    per_module_id = :module_id,
                    per_statut_id = :statut_id,
                    per_modifie_par_utilisateur_id = :modifie_par,
                    per_modifie_le = NOW()
              WHERE per_id = :id
                AND per_supprime_le IS NULL",
            [
                'id' => $id,
                'code' => $this->normaliserPermission((string) $data['per_code']),
                'description' => $this->nullableString($data['per_description'] ?? null),
                'module_id' => $this->nullableInt($data['per_module_id'] ?? null),
                'statut_id' => $this->nullableInt($data['per_statut_id'] ?? null) ?? $this->statutId('general', 'actif'),
                'modifie_par' => $utilisateurId,
            ]
        );

        if ($result) {
            $this->audit($utilisateurId, 'permission.modification', 'sav_permissions', $id);
        }

        return $result;
    }

    public function supprimerPermission(int $id, ?int $utilisateurId): bool
    {
        $result = $this->db->execute(
            "UPDATE sav_permissions
                SET per_supprime_le = NOW(),
                    per_supprime_par_utilisateur_id = :uid,
                    per_statut_id = :statut
              WHERE per_id = :id
                AND per_supprime_le IS NULL",
            ['id' => $id, 'uid' => $utilisateurId, 'statut' => $this->statutId('general', 'supprime')]
        );

        if ($result) {
            $this->audit($utilisateurId, 'permission.suppression', 'sav_permissions', $id);
        }

        return $result;
    }

    public function codePermissionExiste(string $code, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM sav_permissions
                 WHERE per_code = :code
                   AND per_supprime_le IS NULL
                   AND per_archive_le IS NULL";
        $params = ['code' => $this->normaliserPermission($code)];
        if ($excludeId !== null) {
            $sql .= ' AND per_id <> :id';
            $params['id'] = $excludeId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    /** @return array<int,array<string,mixed>> */
    public function listerPolitiquesAcces(array $filtres = []): array
    {
        $where = ['pac.pac_supprime_le IS NULL', 'pac.pac_archive_le IS NULL'];
        $params = [];
        if (!empty($filtres['q'])) {
            $where[] = '(pac.pac_code LIKE :q OR pac.pac_nom LIKE :q)';
            $params['q'] = '%' . trim((string) $filtres['q']) . '%';
        }
        if (!empty($filtres['permission_id'])) {
            $where[] = 'pac.pac_permission_id = :permission_id';
            $params['permission_id'] = (int) $filtres['permission_id'];
        }

        return $this->db->fetchAll(
            "SELECT pac.*,
                    p.per_code,
                    m.mod_code,
                    m.mod_nom,
                    st.sta_code AS statut_code,
                    (SELECT COUNT(*) FROM sav_conditions_politiques_acces cpa
                      WHERE cpa.cpa_politique_acces_id = pac.pac_id
                        AND cpa.cpa_supprime_le IS NULL) AS conditions_count
               FROM sav_politiques_acces pac
          LEFT JOIN sav_permissions p ON p.per_id = pac.pac_permission_id
          LEFT JOIN sav_modules m ON m.mod_id = pac.pac_module_id
          LEFT JOIN sav_statuts st ON st.sta_id = pac.pac_statut_id
              WHERE " . implode(' AND ', $where) . "
           ORDER BY pac.pac_priorite ASC, pac.pac_code ASC",
            $params
        );
    }

    public function trouverPolitiqueAcces(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT pac.*, p.per_code, m.mod_code, m.mod_nom, st.sta_code AS statut_code
               FROM sav_politiques_acces pac
          LEFT JOIN sav_permissions p ON p.per_id = pac.pac_permission_id
          LEFT JOIN sav_modules m ON m.mod_id = pac.pac_module_id
          LEFT JOIN sav_statuts st ON st.sta_id = pac.pac_statut_id
              WHERE pac.pac_id = :id
                AND pac.pac_supprime_le IS NULL
                AND pac.pac_archive_le IS NULL
              LIMIT 1",
            ['id' => $id]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function conditionsPolitique(int $policyId): array
    {
        return $this->db->fetchAll(
            "SELECT *
               FROM sav_conditions_politiques_acces
              WHERE cpa_politique_acces_id = :id
                AND cpa_supprime_le IS NULL
           ORDER BY cpa_id ASC",
            ['id' => $policyId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function listerModules(): array
    {
        return $this->db->fetchAll(
            "SELECT mod_id, mod_code, mod_nom
               FROM sav_modules
              WHERE mod_supprime_le IS NULL
                AND mod_archive_le IS NULL
           ORDER BY mod_nom ASC"
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function listerSocietes(?array $societeIds = null): array
    {
        $where = [
            'soc_supprime_le IS NULL',
            'soc_archive_le IS NULL',
        ];
        $params = [];
        if ($societeIds !== null) {
            $ids = array_values(array_unique(array_filter(array_map('intval', $societeIds))));
            if ($ids === []) {
                $where[] = '1 = 0';
            } else {
                $placeholders = [];
                foreach ($ids as $index => $id) {
                    $key = 'societe_' . $index;
                    $placeholders[] = ':' . $key;
                    $params[$key] = $id;
                }
                $where[] = 'soc_id IN (' . implode(', ', $placeholders) . ')';
            }
        }
        return $this->db->fetchAll(
            "SELECT soc_id, soc_code, soc_nom
               FROM sav_societes
              WHERE " . implode(' AND ', $where) . "
           ORDER BY soc_nom ASC",
            $params
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function listerTypesSocietes(): array
    {
        return $this->db->fetchAll(
            "SELECT tso_id, tso_code, tso_nom
               FROM sav_types_societes
              WHERE tso_supprime_le IS NULL
                AND tso_archive_le IS NULL
           ORDER BY tso_nom ASC"
        );
    }

    /**
     * Retourne uniquement les types de société affectés à une société donnée.
     * Utilisé dans le formulaire rôle pour restreindre le select "Type de société"
     * à la réalité métier de la société active de l'utilisateur connecté.
     *
     * @return array<int,array<string,mixed>>
     */
    public function listerTypesSocietesParSociete(int $societeId): array
    {
        return $this->db->fetchAll(
            "SELECT ts.tso_id, ts.tso_code, ts.tso_nom
               FROM sav_types_societes ts
         INNER JOIN sav_affectations_types_societes ats
                 ON ats.ats_type_societe_id = ts.tso_id
              WHERE ats.ats_societe_id = :societe_id
                AND ats.ats_supprime_le IS NULL
                AND ats.ats_archive_le IS NULL
                AND ts.tso_supprime_le IS NULL
                AND ts.tso_archive_le IS NULL
           ORDER BY ts.tso_nom ASC",
            ['societe_id' => $societeId]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function listerStatutsGeneraux(): array
    {
        return $this->db->fetchAll(
            "SELECT sta_id, sta_code, sta_libelle
               FROM sav_statuts
              WHERE sta_domaine = 'general'
                AND sta_est_actif = 1
                AND sta_supprime_le IS NULL
           ORDER BY sta_ordre ASC, sta_libelle ASC"
        );
    }

    private function statutId(string $domaine, string $code): ?int
    {
        $value = $this->db->fetchColumn(
            "SELECT sta_id
               FROM sav_statuts
              WHERE sta_domaine = :domaine
                AND sta_code = :code
                AND sta_supprime_le IS NULL
              LIMIT 1",
            ['domaine' => $domaine, 'code' => $code]
        );
        return $value === false || $value === null ? null : (int) $value;
    }

    private function normaliserCode(string $code): string
    {
        $code = trim(mb_strtolower($code));
        $code = preg_replace('/[^a-z0-9_.-]+/u', '_', $code) ?: $code;
        return trim($code, '_.-');
    }

    private function normaliserPermission(string $code): string
    {
        $code = trim(mb_strtolower($code));
        $code = preg_replace('/[^a-z0-9_.-]+/u', '.', $code) ?: $code;
        return trim($code, '.-_');
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || (int) $value <= 0) {
            return null;
        }
        return (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private function roleScopeSelect(string $alias): string
    {
        return in_array('rol_portee_code', $this->tableColumns(), true)
            ? $alias . '.rol_portee_code'
            : "'interne'";
    }

}