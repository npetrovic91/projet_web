<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Organisation\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Organisation interne alignée sur le schéma SQL français.
 *
 * Tables couvertes :
 * - sav_departements, sav_secteurs, sav_services, sav_equipes ;
 * - sav_departements_secteurs, sav_departements_services ;
 * - sav_secteurs_services, sav_services_equipes, sav_equipes_services ;
 * - sav_utilisateurs_departements, sav_utilisateurs_services, sav_utilisateurs_equipes.
 */
class OrganisationModel extends BaseModel
{
    protected string $table = 'sav_departements';
    protected string $colPrefix = 'dep_';

    private array $entites = [
        'departements' => ['table' => 'sav_departements', 'pk' => 'dep_id', 'soc' => 'dep_societe_id', 'code' => 'dep_code', 'nom' => 'dep_nom', 'desc' => 'dep_description', 'statut' => 'dep_statut_id', 'responsable' => 'dep_responsable_utilisateur_id', 'cree' => 'dep_cree_le', 'mod' => 'dep_modifie_le', 'sup' => 'dep_supprime_le', 'arch' => 'dep_archive_le', 'prefix' => 'dep'],
        'secteurs'     => ['table' => 'sav_secteurs',     'pk' => 'sec_id', 'soc' => 'sec_societe_id', 'code' => 'sec_code', 'nom' => 'sec_nom', 'desc' => 'sec_description', 'statut' => 'sec_statut_id', 'cree' => 'sec_cree_le', 'mod' => 'sec_modifie_le', 'sup' => 'sec_supprime_le', 'arch' => 'sec_archive_le', 'prefix' => 'sec'],
        'services'     => ['table' => 'sav_services',     'pk' => 'srv_id', 'soc' => 'srv_societe_id', 'code' => 'srv_code', 'nom' => 'srv_nom', 'desc' => 'srv_description', 'statut' => 'srv_statut_id', 'responsable' => 'srv_responsable_utilisateur_id', 'cree' => 'srv_cree_le', 'mod' => 'srv_modifie_le', 'sup' => 'srv_supprime_le', 'arch' => 'srv_archive_le', 'prefix' => 'srv'],
        'equipes'      => ['table' => 'sav_equipes',      'pk' => 'equ_id', 'soc' => 'equ_societe_id', 'code' => 'equ_code', 'nom' => 'equ_nom', 'desc' => 'equ_description', 'statut' => 'equ_statut_id', 'responsable' => 'equ_responsable_utilisateur_id', 'cree' => 'equ_cree_le', 'mod' => 'equ_modifie_le', 'sup' => 'equ_supprime_le', 'arch' => 'equ_archive_le', 'prefix' => 'equ'],
    ];

    private array $liaisons = [
        'departements-secteurs' => ['table' => 'sav_departements_secteurs', 'pk' => 'dse_id', 'soc' => 'dse_societe_id', 'parent' => 'dse_departement_id', 'child' => 'dse_secteur_id', 'start' => 'dse_debute_le', 'end' => 'dse_termine_le', 'statut' => 'dse_statut_id', 'sup' => 'dse_supprime_le', 'arch' => 'dse_archive_le', 'prefix' => 'dse', 'parent_type' => 'departements', 'child_type' => 'secteurs'],
        'departements-services' => ['table' => 'sav_departements_services', 'pk' => 'dsv_id', 'soc' => 'dsv_societe_id', 'parent' => 'dsv_departement_id', 'child' => 'dsv_service_id', 'start' => 'dsv_debute_le', 'end' => 'dsv_termine_le', 'statut' => 'dsv_statut_id', 'sup' => 'dsv_supprime_le', 'arch' => 'dsv_archive_le', 'prefix' => 'dsv', 'parent_type' => 'departements', 'child_type' => 'services'],
        'secteurs-services'     => ['table' => 'sav_secteurs_services',     'pk' => 'ssv_id', 'soc' => 'ssv_societe_id', 'parent' => 'ssv_secteur_id', 'child' => 'ssv_service_id', 'start' => 'ssv_debute_le', 'end' => 'ssv_termine_le', 'statut' => 'ssv_statut_id', 'sup' => 'ssv_supprime_le', 'arch' => 'ssv_archive_le', 'prefix' => 'ssv', 'parent_type' => 'secteurs', 'child_type' => 'services'],
        'services-equipes'      => ['table' => 'sav_services_equipes',      'pk' => 'seq_id', 'soc' => 'seq_societe_id', 'parent' => 'seq_service_id', 'child' => 'seq_equipe_id', 'start' => 'seq_debute_le', 'end' => 'seq_termine_le', 'statut' => 'seq_statut_id', 'sup' => 'seq_supprime_le', 'arch' => 'seq_archive_le', 'prefix' => 'seq', 'parent_type' => 'services', 'child_type' => 'equipes'],
        'equipes-services'      => ['table' => 'sav_equipes_services',      'pk' => 'eqs_id', 'soc' => 'eqs_societe_id', 'parent' => 'eqs_equipe_id', 'child' => 'eqs_service_id', 'start' => 'eqs_debute_le', 'end' => 'eqs_termine_le', 'statut' => 'eqs_statut_id', 'sup' => 'eqs_supprime_le', 'arch' => 'eqs_archive_le', 'prefix' => 'eqs', 'parent_type' => 'equipes', 'child_type' => 'services'],
    ];

    public function tableauDeBord(?int $societeId = null): array
    {
        $filters = $societeId ? ['societe_id' => $societeId] : [];
        return [
            'stats' => [
                'departements' => $this->compterEntite('departements', $societeId),
                'secteurs' => $this->compterEntite('secteurs', $societeId),
                'services' => $this->compterEntite('services', $societeId),
                'equipes' => $this->compterEntite('equipes', $societeId),
                'liaisons' => $this->compterLiaisons($societeId),
                'affectations_utilisateurs' => $this->compterAffectationsUtilisateurs($societeId),
            ],
            'societes' => $this->societes(),
            'organigramme' => $this->organigramme($societeId),
            'dernieres_liaisons' => $this->liaisons([], 30, $societeId),
        ];
    }

    public function entites(string $type, array $filters = [], int $limit = 500): array
    {
        $m = $this->mapEntite($type);
        $where = ["{$m['sup']} IS NULL", "{$m['arch']} IS NULL"];
        $params = [];
        if (!empty($filters['societe_id'])) { $where[] = "{$m['soc']} = :societe_id"; $params['societe_id'] = (int)$filters['societe_id']; }
        if (($filters['q'] ?? '') !== '') { $where[] = "({$m['code']} LIKE :q OR {$m['nom']} LIKE :q OR {$m['desc']} LIKE :q)"; $params['q'] = '%' . trim((string)$filters['q']) . '%'; }
        return $this->db->fetchAll(
            "SELECT e.*, s.soc_nom AS societe_nom, st.sta_libelle AS statut_libelle
             FROM {$m['table']} e
             LEFT JOIN sav_societes s ON s.soc_id = e.{$m['soc']}
             LEFT JOIN sav_statuts st ON st.sta_id = e.{$m['statut']}
             WHERE " . implode(' AND ', $where) . "
             ORDER BY s.soc_nom ASC, e.{$m['nom']} ASC
             LIMIT " . $this->limit($limit),
            $params
        );
    }

    public function trouverEntite(string $type, int $id): ?array
    {
        $m = $this->mapEntite($type);
        return $this->db->fetch("SELECT * FROM {$m['table']} WHERE {$m['pk']} = :id AND {$m['sup']} IS NULL LIMIT 1", ['id' => $id]);
    }

    public function enregistrerEntite(string $type, array $input, ?int $id, ?int $userId): int
    {
        $m = $this->mapEntite($type);
        $societeId = (int)($input['societe_id'] ?? $input[$m['soc']] ?? 0);
        $nom = trim((string)($input['nom'] ?? $input[$m['nom']] ?? ''));
        if ($societeId <= 0 || $nom === '') {
            throw new \InvalidArgumentException('La société et le nom sont obligatoires.');
        }
        $payload = [
            $m['soc'] => $societeId,
            $m['code'] => $this->nullable(trim((string)($input['code'] ?? $input[$m['code']] ?? ''))),
            $m['nom'] => $nom,
            $m['desc'] => $this->nullable(trim((string)($input['description'] ?? $input[$m['desc']] ?? ''))),
            $m['statut'] => $this->nullableInt($input['statut_id'] ?? $input[$m['statut']] ?? null),
        ];
        if (isset($m['responsable'])) {
            $payload[$m['responsable']] = $this->nullableInt($input['responsable_user_id'] ?? $input[$m['responsable']] ?? null);
        }

        $ownTransaction = !$this->pdo->inTransaction();
        if ($ownTransaction) {
            $this->beginTransaction();
        }

        try {
            if ($id) {
                $payload[$m['mod']] = date('Y-m-d H:i:s');
                $payload[$m['prefix'] . '_modifie_par_utilisateur_id'] = $userId;
                $this->updateDynamic($m['table'], $m['pk'], $id, $payload);
                $savedId = $id;
            } else {
                $payload[$m['prefix'] . '_cree_par_utilisateur_id'] = $userId;
                $this->insertDynamic($m['table'], $payload);
                $savedId = (int)$this->db->lastInsertId();
            }

            if (isset($input['affectations_present'])) {
                $this->syncAffectationsEntite($type, $savedId, $societeId, $input, $userId);
            }

            if ($ownTransaction) {
                $this->commit();
            }

            return $savedId;
        } catch (\Throwable $e) {
            if ($ownTransaction) {
                $this->rollback();
            }
            throw $e;
        }
    }

    public function supprimerEntite(string $type, int $id, ?int $userId): bool
    {
        $m = $this->mapEntite($type);
        return $this->db->execute("UPDATE {$m['table']} SET {$m['sup']} = NOW(), {$m['prefix']}_supprime_par_utilisateur_id = :user_id WHERE {$m['pk']} = :id", ['id' => $id, 'user_id' => $userId]);
    }

    public function liaisons(array $filters = [], int $limit = 500, ?int $societeId = null): array
    {
        $rows = [];
        foreach ($this->liaisons as $type => $m) {
            if (!empty($filters['type']) && $filters['type'] !== $type) { continue; }
            $parent = $this->entites[$m['parent_type']];
            $child = $this->entites[$m['child_type']];
            $where = ["l.{$m['sup']} IS NULL", "l.{$m['arch']} IS NULL"];
            $params = [];
            if ($societeId) { $where[] = "l.{$m['soc']} = :societe_id"; $params['societe_id'] = $societeId; }
            if (!empty($filters['societe_id'])) { $where[] = "l.{$m['soc']} = :societe_id"; $params['societe_id'] = (int)$filters['societe_id']; }
            $sql = "SELECT l.*, :type_liaison AS type_liaison, s.soc_nom AS societe_nom,
                           p.{$parent['nom']} AS parent_nom, p.{$parent['code']} AS parent_code,
                           c.{$child['nom']} AS enfant_nom, c.{$child['code']} AS enfant_code,
                           st.sta_libelle AS statut_libelle
                    FROM {$m['table']} l
                    LEFT JOIN sav_societes s ON s.soc_id = l.{$m['soc']}
                    LEFT JOIN {$parent['table']} p ON p.{$parent['pk']} = l.{$m['parent']}
                    LEFT JOIN {$child['table']} c ON c.{$child['pk']} = l.{$m['child']}
                    LEFT JOIN sav_statuts st ON st.sta_id = l.{$m['statut']}
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY s.soc_nom ASC, parent_nom ASC, enfant_nom ASC
                    LIMIT " . $this->limit($limit);
            $rows = array_merge($rows, $this->db->fetchAll($sql, $params + ['type_liaison' => $type]));
        }
        usort($rows, static fn($a, $b) => [$a['societe_nom'] ?? '', $a['type_liaison'] ?? '', $a['parent_nom'] ?? ''] <=> [$b['societe_nom'] ?? '', $b['type_liaison'] ?? '', $b['parent_nom'] ?? '']);
        return $rows;
    }

    public function enregistrerLiaison(string $type, array $input, ?int $userId): int
    {
        $m = $this->mapLiaison($type);
        $societeId = (int)($input['societe_id'] ?? 0);
        $parentId = (int)($input['parent_id'] ?? 0);
        $childId = (int)($input['child_id'] ?? 0);
        $start = trim((string)($input['debute_le'] ?? date('Y-m-d')));
        if ($societeId <= 0 || $parentId <= 0 || $childId <= 0 || $start === '') {
            throw new \InvalidArgumentException('La société, le parent, l’enfant et la date de début sont obligatoires.');
        }
        $params = [
            'societe_id' => $societeId,
            'parent_id' => $parentId,
            'child_id' => $childId,
            'start' => $start,
            'end' => $this->nullable(trim((string)($input['termine_le'] ?? ''))),
            'statut_id' => $this->nullableInt($input['statut_id'] ?? null),
            'user_id' => $userId,
        ];
        $this->db->execute(
            "INSERT INTO {$m['table']} ({$m['soc']}, {$m['parent']}, {$m['child']}, {$m['start']}, {$m['end']}, {$m['statut']}, {$m['prefix']}_cree_par_utilisateur_id)
             VALUES (:societe_id, :parent_id, :child_id, :start, :end, :statut_id, :user_id)",
            $params
        );
        return (int)$this->db->lastInsertId();
    }

    public function supprimerLiaison(string $type, int $id, ?int $userId): bool
    {
        $m = $this->mapLiaison($type);
        return $this->db->execute("UPDATE {$m['table']} SET {$m['sup']} = NOW(), {$m['prefix']}_supprime_par_utilisateur_id = :user_id WHERE {$m['pk']} = :id", ['id' => $id, 'user_id' => $userId]);
    }

    public function organigramme(?int $societeId = null): array
    {
        $societes = $societeId ? array_values(array_filter($this->societes(), static fn($s) => (int)$s['soc_id'] === $societeId)) : $this->societes(50);
        $result = [];
        foreach ($societes as $societe) {
            $sid = (int)$societe['soc_id'];
            $result[] = [
                'societe' => $societe,
                'departements' => $this->entites('departements', ['societe_id' => $sid], 200),
                'secteurs' => $this->entites('secteurs', ['societe_id' => $sid], 200),
                'services' => $this->entites('services', ['societe_id' => $sid], 200),
                'equipes' => $this->entites('equipes', ['societe_id' => $sid], 200),
            ];
        }
        return $result;
    }

    public function societes(int $limit = 500): array
    {
        return $this->db->fetchAll('SELECT soc_id, soc_nom, soc_code FROM sav_societes WHERE soc_supprime_le IS NULL AND soc_archive_le IS NULL ORDER BY soc_nom ASC LIMIT ' . $this->limit($limit));
    }

    public function statutsOrganisation(): array
    {
        return $this->db->fetchAll("SELECT sta_id, sta_domaine, sta_libelle, sta_code FROM sav_statuts WHERE sta_supprime_le IS NULL AND (sta_domaine LIKE '%organisation%' OR sta_domaine LIKE '%structure%' OR sta_domaine LIKE '%general%' OR sta_domaine LIKE '%statut%') ORDER BY sta_domaine ASC, sta_ordre ASC, sta_libelle ASC LIMIT 300");
    }

    public function utilisateursActifs(?int $societeId = null): array
    {
        $where = ['u.uti_supprime_le IS NULL', 'u.uti_anonymise_le IS NULL'];
        $params = [];
        if ($societeId !== null && $societeId > 0) {
            $where[] = '(u.uti_societe_active_id = :societe_id OR EXISTS (
                SELECT 1
                FROM sav_adhesions_utilisateurs_societes aus
                WHERE aus.aus_utilisateur_id = u.uti_id
                  AND aus.aus_societe_id = :societe_id_adhesion
                  AND aus.aus_supprime_le IS NULL
                  AND aus.aus_archive_le IS NULL
                  AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= CURDATE())
            ))';
            $params['societe_id'] = $societeId;
            $params['societe_id_adhesion'] = $societeId;
        }

        return $this->db->fetchAll(
            "SELECT DISTINCT
                    u.uti_id AS utilisateur_id,
                    u.uti_email,
                    u.uti_identifiant,
                    CONCAT(COALESCE(p.pui_prenom, ''), ' ', COALESCE(p.pui_nom, '')) AS nom_complet
             FROM sav_utilisateurs u
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.pui_nom ASC, p.pui_prenom ASC, u.uti_email ASC
             LIMIT 1000",
            $params
        );
    }

    public function affectationsEntite(string $type, int $id): array
    {
        $m = $this->mapAffectation($type);
        if ($m === null || $id <= 0) {
            return [];
        }

        return $this->db->fetchAll(
            "SELECT
                    a.{$m['user']} AS utilisateur_id,
                    u.uti_email,
                    u.uti_identifiant,
                    CONCAT(COALESCE(p.pui_prenom, ''), ' ', COALESCE(p.pui_nom, '')) AS nom_complet
             FROM {$m['table']} a
             INNER JOIN sav_utilisateurs u ON u.uti_id = a.{$m['user']}
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
             WHERE a.{$m['item']} = :id
               AND a.{$m['sup']} IS NULL
               AND a.{$m['arch']} IS NULL
             ORDER BY p.pui_nom ASC, p.pui_prenom ASC, u.uti_email ASC",
            ['id' => $id]
        );
    }

    public function export(?int $societeId = null): array
    {
        return [
            'genere_le' => date('c'),
            'societe_id' => $societeId,
            'departements' => $this->entites('departements', $societeId ? ['societe_id' => $societeId] : [], 5000),
            'secteurs' => $this->entites('secteurs', $societeId ? ['societe_id' => $societeId] : [], 5000),
            'services' => $this->entites('services', $societeId ? ['societe_id' => $societeId] : [], 5000),
            'equipes' => $this->entites('equipes', $societeId ? ['societe_id' => $societeId] : [], 5000),
            'liaisons' => $this->liaisons([], 10000, $societeId),
        ];
    }

    public function auditer(string $action, string $table, int $id, ?int $userId, ?int $societeId, string $ip, array $meta = []): void
    {
        $this->db->execute(
            'INSERT INTO sav_journaux_audit (jau_utilisateur_id, jau_societe_id, jau_action, jau_table_cible, jau_id_cible, jau_adresse_ip, jau_metadata_json, jau_cree_le) VALUES (:user_id, :societe_id, :action, :table_cible, :id_cible, INET6_ATON(:ip), :meta, NOW())',
            ['user_id' => $userId, 'societe_id' => $societeId, 'action' => $action, 'table_cible' => $table, 'id_cible' => $id, 'ip' => $ip, 'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );
    }

    public function typeLabels(): array
    {
        return ['departements' => 'Départements', 'secteurs' => 'Secteurs', 'services' => 'Services', 'equipes' => 'Équipes'];
    }

    public function liaisonLabels(): array
    {
        return [
            'departements-secteurs' => 'Département → secteur',
            'departements-services' => 'Département → service',
            'secteurs-services' => 'Secteur → service',
            'services-equipes' => 'Service → équipe',
            'equipes-services' => 'Équipe → service',
        ];
    }

    public function optionsPourLiaison(string $type, ?int $societeId): array
    {
        $m = $this->mapLiaison($type);
        return [
            'parents' => $this->entites($m['parent_type'], $societeId ? ['societe_id' => $societeId] : [], 1000),
            'enfants' => $this->entites($m['child_type'], $societeId ? ['societe_id' => $societeId] : [], 1000),
            'parent_type' => $m['parent_type'],
            'child_type' => $m['child_type'],
        ];
    }

    private function compterEntite(string $type, ?int $societeId): int
    {
        $m = $this->mapEntite($type);
        $where = ["{$m['sup']} IS NULL", "{$m['arch']} IS NULL"];
        $params = [];
        if ($societeId) { $where[] = "{$m['soc']} = :societe_id"; $params['societe_id'] = $societeId; }
        return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM {$m['table']} WHERE " . implode(' AND ', $where), $params);
    }

    private function compterLiaisons(?int $societeId): int
    {
        $total = 0;
        foreach ($this->liaisons as $m) {
            $params = [];
            $where = ["{$m['sup']} IS NULL", "{$m['arch']} IS NULL"];
            if ($societeId) { $where[] = "{$m['soc']} = :societe_id"; $params['societe_id'] = $societeId; }
            $total += (int)$this->db->fetchColumn("SELECT COUNT(*) FROM {$m['table']} WHERE " . implode(' AND ', $where), $params);
        }
        return $total;
    }

    private function compterAffectationsUtilisateurs(?int $societeId): int
    {
        $tables = [
            ['sav_utilisateurs_departements', 'udp_supprime_le', 'udp_archive_le', 'udp_societe_id'],
            ['sav_utilisateurs_services', 'usv_supprime_le', 'usv_archive_le', 'usv_societe_id'],
            ['sav_utilisateurs_equipes', 'ueq_supprime_le', 'ueq_archive_le', 'ueq_societe_id'],
        ];
        $total = 0;
        foreach ($tables as [$table, $sup, $arch, $soc]) {
            $params = [];
            $where = ["{$sup} IS NULL", "{$arch} IS NULL"];
            if ($societeId) { $where[] = "{$soc} = :societe_id"; $params['societe_id'] = $societeId; }
            $total += (int)$this->db->fetchColumn("SELECT COUNT(*) FROM {$table} WHERE " . implode(' AND ', $where), $params);
        }
        return $total;
    }

    private function syncAffectationsEntite(string $type, int $entityId, int $societeId, array $input, ?int $userId): void
    {
        $m = $this->mapAffectation($type);
        if ($m === null || $entityId <= 0 || $societeId <= 0) {
            return;
        }

        $responsableId = $this->nullableInt($input['responsable_user_id'] ?? null);
        $userIds = array_values(array_unique(array_filter(array_map(
            'intval',
            array_merge((array)($input['utilisateur_ids'] ?? []), $responsableId ? [$responsableId] : [])
        ), static fn(int $id): bool => $id > 0)));

        $this->db->execute(
            "UPDATE {$m['table']}
             SET {$m['arch']} = NOW(), {$m['prefix']}_archive_par_utilisateur_id = :user_id
             WHERE {$m['item']} = :entity_id
               AND {$m['arch']} IS NULL
               AND {$m['sup']} IS NULL",
            ['entity_id' => $entityId, 'user_id' => $userId]
        );

        if ($userIds === []) {
            return;
        }

        $statutId = $this->statusId('general', 'actif');
        foreach ($userIds as $assignedUserId) {
            $this->db->execute(
                "INSERT INTO {$m['table']}
                 ({$m['user']}, {$m['soc']}, {$m['item']}, {$m['start']}, {$m['statut']}, {$m['prefix']}_cree_par_utilisateur_id)
                 VALUES (:assigned_user_id, :societe_id, :entity_id, CURDATE(), :statut_id, :user_id)",
                [
                    'assigned_user_id' => $assignedUserId,
                    'societe_id' => $societeId,
                    'entity_id' => $entityId,
                    'statut_id' => $statutId,
                    'user_id' => $userId,
                ]
            );
        }
    }

    private function mapAffectation(string $type): ?array
    {
        return [
            'departements' => ['table' => 'sav_utilisateurs_departements', 'prefix' => 'udp', 'user' => 'udp_utilisateur_id', 'soc' => 'udp_societe_id', 'item' => 'udp_departement_id', 'start' => 'udp_debute_le', 'statut' => 'udp_statut_id', 'sup' => 'udp_supprime_le', 'arch' => 'udp_archive_le'],
            'services' => ['table' => 'sav_utilisateurs_services', 'prefix' => 'usv', 'user' => 'usv_utilisateur_id', 'soc' => 'usv_societe_id', 'item' => 'usv_service_id', 'start' => 'usv_debute_le', 'statut' => 'usv_statut_id', 'sup' => 'usv_supprime_le', 'arch' => 'usv_archive_le'],
            'equipes' => ['table' => 'sav_utilisateurs_equipes', 'prefix' => 'ueq', 'user' => 'ueq_utilisateur_id', 'soc' => 'ueq_societe_id', 'item' => 'ueq_equipe_id', 'start' => 'ueq_debute_le', 'statut' => 'ueq_statut_id', 'sup' => 'ueq_supprime_le', 'arch' => 'ueq_archive_le'],
        ][$type] ?? null;
    }

    private function mapEntite(string $type): array
    {
        if (!isset($this->entites[$type])) { throw new \InvalidArgumentException('Type de structure inconnu.'); }
        return $this->entites[$type];
    }

    private function mapLiaison(string $type): array
    {
        if (!isset($this->liaisons[$type])) { throw new \InvalidArgumentException('Type de liaison inconnu.'); }
        return $this->liaisons[$type];
    }

    private function limit(int $limit): int { return max(1, min($limit, 10000)); }
    private function nullable(string $value): ?string { return $value === '' ? null : $value; }
    private function nullableInt(mixed $value): ?int { $i = (int)$value; return $i > 0 ? $i : null; }
}
