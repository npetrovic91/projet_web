<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Validation\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Modèle Validation aligné sur le schéma SQL actuel.
 * Tables sources : sav_demandes_validation, sav_regles_validation,
 * sav_journaux_audit, sav_societes, sav_utilisateurs, sav_profils_utilisateurs,
 * sav_permissions, sav_modules, sav_statuts.
 */
class ValidationModel extends BaseModel
{
    protected string $table = 'sav_demandes_validation';
    protected string $colPrefix = 'dva_';

    public function stats(): array
    {
        return [
            'demandes_total' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_demandes_validation WHERE dva_supprime_le IS NULL"),
            'demandes_en_attente' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_demandes_validation WHERE dva_supprime_le IS NULL AND dva_decision IS NULL"),
            'demandes_validees' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_demandes_validation WHERE dva_supprime_le IS NULL AND dva_decision IN ('approuvee','validee','approved','accepted')"),
            'demandes_refusees' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_demandes_validation WHERE dva_supprime_le IS NULL AND dva_decision IN ('refusee','rejetee','denied','rejected')"),
            'regles_actives' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_regles_validation WHERE rva_supprime_le IS NULL"),
        ];
    }

    public function demandes(array $filters = [], int $limit = 200): array
    {
        $where = ['d.dva_supprime_le IS NULL'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(d.dva_type_demande LIKE :q OR d.dva_table_cible LIKE :q OR d.dva_motif LIKE :q OR d.dva_decision LIKE :q OR soc.soc_nom LIKE :q OR duse.use_email LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        if (!empty($filters['societe_id'])) {
            $where[] = 'd.dva_societe_id = :societe_id';
            $params['societe_id'] = (int) $filters['societe_id'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'd.dva_type_demande = :type';
            $params['type'] = trim((string) $filters['type']);
        }
        if (($filters['decision'] ?? '') === 'en_attente') {
            $where[] = 'd.dva_decision IS NULL';
        } elseif (!empty($filters['decision'])) {
            $where[] = 'd.dva_decision = :decision';
            $params['decision'] = trim((string) $filters['decision']);
        }

        $params['limit'] = max(1, min(500, $limit));

        return $this->db->fetchAll(
            "SELECT d.*,
                    soc.soc_nom AS societe_nom,
                    duse.use_email AS demandeur_email,
                    CONCAT(COALESCE(dprof.pui_prenom, ''), ' ', COALESCE(dprof.pui_nom, '')) AS demandeur_nom,
                    vuse.use_email AS validateur_email,
                    CONCAT(COALESCE(vprof.pui_prenom, ''), ' ', COALESCE(vprof.pui_nom, '')) AS validateur_nom,
                    st.sta_code AS statut_code,
                    st.sta_libelle AS statut_libelle
             FROM sav_demandes_validation d
             LEFT JOIN sav_societes soc ON soc.soc_id = d.dva_societe_id
             LEFT JOIN sav_utilisateurs duse ON duse.use_id = d.dva_demandeur_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs dprof ON dprof.pui_utilisateur_id = d.dva_demandeur_utilisateur_id AND dprof.pui_supprime_le IS NULL
             LEFT JOIN sav_utilisateurs vuse ON vuse.use_id = d.dva_validateur_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs vprof ON vprof.pui_utilisateur_id = d.dva_validateur_utilisateur_id AND vprof.pui_supprime_le IS NULL
             LEFT JOIN sav_statuts st ON st.sta_id = d.dva_statut_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY
                CASE WHEN d.dva_decision IS NULL THEN 0 ELSE 1 END ASC,
                d.dva_cree_le DESC
             LIMIT :limit",
            $params
        );
    }

    public function findDemande(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT d.*,
                    soc.soc_nom AS societe_nom,
                    duse.use_email AS demandeur_email,
                    CONCAT(COALESCE(dprof.pui_prenom, ''), ' ', COALESCE(dprof.pui_nom, '')) AS demandeur_nom,
                    vuse.use_email AS validateur_email,
                    CONCAT(COALESCE(vprof.pui_prenom, ''), ' ', COALESCE(vprof.pui_nom, '')) AS validateur_nom,
                    st.sta_code AS statut_code,
                    st.sta_libelle AS statut_libelle
             FROM sav_demandes_validation d
             LEFT JOIN sav_societes soc ON soc.soc_id = d.dva_societe_id
             LEFT JOIN sav_utilisateurs duse ON duse.use_id = d.dva_demandeur_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs dprof ON dprof.pui_utilisateur_id = d.dva_demandeur_utilisateur_id AND dprof.pui_supprime_le IS NULL
             LEFT JOIN sav_utilisateurs vuse ON vuse.use_id = d.dva_validateur_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs vprof ON vprof.pui_utilisateur_id = d.dva_validateur_utilisateur_id AND vprof.pui_supprime_le IS NULL
             LEFT JOIN sav_statuts st ON st.sta_id = d.dva_statut_id
             WHERE d.dva_id = :id AND d.dva_supprime_le IS NULL
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function createDemande(array $data, int $userId): int
    {
        $type = trim((string) ($data['dva_type_demande'] ?? $data['type_demande'] ?? 'operation_sensible'));
        if ($type === '') {
            throw new \InvalidArgumentException('Le type de demande est obligatoire.');
        }
        $payload = $this->jsonPayload($data['dva_donnees_json'] ?? $data['donnees_json'] ?? $data['payload'] ?? null);

        $this->db->execute(
            "INSERT INTO sav_demandes_validation
                (dva_uuid, dva_type_demande, dva_societe_id, dva_demandeur_utilisateur_id,
                 dva_table_cible, dva_id_cible, dva_donnees_json, dva_motif, dva_statut_id,
                 dva_cree_par_utilisateur_id, dva_modifie_par_utilisateur_id)
             VALUES
                (:uuid, :type_demande, :societe_id, :demandeur_id,
                 :table_cible, :id_cible, :donnees_json, :motif, :statut_id,
                 :user_id, :user_id)",
            [
                'uuid' => $this->uuid(),
                'type_demande' => $type,
                'societe_id' => $this->nullableInt($data['dva_societe_id'] ?? $data['societe_id'] ?? null),
                'demandeur_id' => $this->nullableInt($data['dva_demandeur_utilisateur_id'] ?? $data['demandeur_utilisateur_id'] ?? null) ?? $userId,
                'table_cible' => $this->nullOrString($data['dva_table_cible'] ?? $data['table_cible'] ?? null, 120),
                'id_cible' => $this->nullableInt($data['dva_id_cible'] ?? $data['id_cible'] ?? null),
                'donnees_json' => $payload,
                'motif' => $this->nullOrString($data['dva_motif'] ?? $data['motif'] ?? null, 5000),
                'statut_id' => $this->statusId('validation', 'en_attente') ?? $this->statusId('general', 'actif'),
                'user_id' => $userId,
            ]
        );
        $id = (int) $this->db->lastInsertId();
        $this->audit($userId, 'validation.demande.creer', 'sav_demandes_validation', $id, 'Création demande de validation');
        return $id;
    }

    public function decideDemande(int $id, string $decision, ?string $commentaire, int $userId): bool
    {
        $decision = trim($decision);
        if (!in_array($decision, ['approuvee', 'refusee', 'annulee'], true)) {
            throw new \InvalidArgumentException('Décision invalide.');
        }
        $statusCode = match ($decision) {
            'approuvee' => 'valide',
            'refusee' => 'refuse',
            default => 'annule',
        };
        $ok = $this->db->execute(
            "UPDATE sav_demandes_validation
             SET dva_validateur_utilisateur_id = :validateur_id,
                 dva_decision = :decision,
                 dva_decision_le = NOW(),
                 dva_commentaire_decision = :commentaire,
                 dva_statut_id = COALESCE(:statut_id, dva_statut_id),
                 dva_modifie_par_utilisateur_id = :user_id
             WHERE dva_id = :id AND dva_supprime_le IS NULL AND dva_decision IS NULL",
            [
                'id' => $id,
                'validateur_id' => $userId,
                'decision' => $decision,
                'commentaire' => $this->nullOrString($commentaire, 5000),
                'statut_id' => $this->statusId('validation', $statusCode),
                'user_id' => $userId,
            ]
        );
        if ($ok) {
            $this->audit($userId, 'validation.demande.' . $decision, 'sav_demandes_validation', $id, $commentaire ?: 'Décision de validation');
        }
        return $ok;
    }

    public function softDeleteDemande(int $id, int $userId): bool
    {
        $ok = $this->db->execute(
            "UPDATE sav_demandes_validation
             SET dva_supprime_le = NOW(), dva_supprime_par_utilisateur_id = :user_id
             WHERE dva_id = :id AND dva_supprime_le IS NULL",
            ['id' => $id, 'user_id' => $userId]
        );
        if ($ok) {
            $this->audit($userId, 'validation.demande.supprimer', 'sav_demandes_validation', $id, 'Suppression logique demande validation');
        }
        return $ok;
    }

    public function regles(array $filters = []): array
    {
        $where = ['r.rva_supprime_le IS NULL'];
        $params = [];
        if (!empty($filters['q'])) {
            $where[] = '(r.rva_code LIKE :q OR r.rva_nom LIKE :q OR r.rva_type_operation LIKE :q OR r.rva_permission_validateur_code LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        if (!empty($filters['societe_id'])) {
            $where[] = 'r.rva_societe_id = :societe_id';
            $params['societe_id'] = (int) $filters['societe_id'];
        }
        if (!empty($filters['type_operation'])) {
            $where[] = 'r.rva_type_operation = :type_operation';
            $params['type_operation'] = trim((string) $filters['type_operation']);
        }

        return $this->db->fetchAll(
            "SELECT r.*, soc.soc_nom AS societe_nom, m.mod_nom AS module_nom,
                    st.sta_code AS statut_code, st.sta_libelle AS statut_libelle
             FROM sav_regles_validation r
             LEFT JOIN sav_societes soc ON soc.soc_id = r.rva_societe_id
             LEFT JOIN sav_modules m ON m.mod_id = r.rva_module_id
             LEFT JOIN sav_statuts st ON st.sta_id = r.rva_statut_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY r.rva_type_operation ASC, soc.soc_nom ASC, r.rva_nom ASC",
            $params
        );
    }

    public function findRegle(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT r.*, soc.soc_nom AS societe_nom, m.mod_nom AS module_nom
             FROM sav_regles_validation r
             LEFT JOIN sav_societes soc ON soc.soc_id = r.rva_societe_id
             LEFT JOIN sav_modules m ON m.mod_id = r.rva_module_id
             WHERE r.rva_id = :id AND r.rva_supprime_le IS NULL
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function createRegle(array $data, int $userId): int
    {
        $code = trim((string) ($data['rva_code'] ?? $data['code'] ?? ''));
        $nom = trim((string) ($data['rva_nom'] ?? $data['nom'] ?? ''));
        $type = trim((string) ($data['rva_type_operation'] ?? $data['type_operation'] ?? 'operation_sensible'));
        if ($code === '' || $nom === '') {
            throw new \InvalidArgumentException('Le code et le nom de la règle sont obligatoires.');
        }
        $conditions = $this->jsonPayload($data['rva_conditions_json'] ?? $data['conditions_json'] ?? null);
        $this->db->execute(
            "INSERT INTO sav_regles_validation
                (rva_code, rva_nom, rva_type_operation, rva_module_id, rva_societe_id,
                 rva_conditions_json, rva_nombre_validateurs_requis, rva_permission_validateur_code,
                 rva_statut_id, rva_cree_par_utilisateur_id, rva_modifie_par_utilisateur_id)
             VALUES
                (:code, :nom, :type_operation, :module_id, :societe_id,
                 :conditions_json, :validateurs, :permission_code,
                 :statut_id, :user_id, :user_id)",
            [
                'code' => $code,
                'nom' => $nom,
                'type_operation' => $type,
                'module_id' => $this->nullableInt($data['rva_module_id'] ?? $data['module_id'] ?? null),
                'societe_id' => $this->nullableInt($data['rva_societe_id'] ?? $data['societe_id'] ?? null),
                'conditions_json' => $conditions,
                'validateurs' => max(1, min(9, (int) ($data['rva_nombre_validateurs_requis'] ?? $data['nombre_validateurs_requis'] ?? 1))),
                'permission_code' => $this->nullOrString($data['rva_permission_validateur_code'] ?? $data['permission_validateur_code'] ?? null, 120),
                'statut_id' => $this->nullableInt($data['rva_statut_id'] ?? $data['statut_id'] ?? null) ?? $this->statusId('general', 'actif'),
                'user_id' => $userId,
            ]
        );
        $id = (int) $this->db->lastInsertId();
        $this->audit($userId, 'validation.regle.creer', 'sav_regles_validation', $id, 'Création règle validation');
        return $id;
    }

    public function updateRegle(int $id, array $data, int $userId): bool
    {
        $code = trim((string) ($data['rva_code'] ?? $data['code'] ?? ''));
        $nom = trim((string) ($data['rva_nom'] ?? $data['nom'] ?? ''));
        $type = trim((string) ($data['rva_type_operation'] ?? $data['type_operation'] ?? 'operation_sensible'));
        if ($code === '' || $nom === '') {
            throw new \InvalidArgumentException('Le code et le nom de la règle sont obligatoires.');
        }
        $conditions = $this->jsonPayload($data['rva_conditions_json'] ?? $data['conditions_json'] ?? null);
        $ok = $this->db->execute(
            "UPDATE sav_regles_validation
             SET rva_code = :code,
                 rva_nom = :nom,
                 rva_type_operation = :type_operation,
                 rva_module_id = :module_id,
                 rva_societe_id = :societe_id,
                 rva_conditions_json = :conditions_json,
                 rva_nombre_validateurs_requis = :validateurs,
                 rva_permission_validateur_code = :permission_code,
                 rva_statut_id = :statut_id,
                 rva_modifie_par_utilisateur_id = :user_id
             WHERE rva_id = :id AND rva_supprime_le IS NULL",
            [
                'id' => $id,
                'code' => $code,
                'nom' => $nom,
                'type_operation' => $type,
                'module_id' => $this->nullableInt($data['rva_module_id'] ?? $data['module_id'] ?? null),
                'societe_id' => $this->nullableInt($data['rva_societe_id'] ?? $data['societe_id'] ?? null),
                'conditions_json' => $conditions,
                'validateurs' => max(1, min(9, (int) ($data['rva_nombre_validateurs_requis'] ?? $data['nombre_validateurs_requis'] ?? 1))),
                'permission_code' => $this->nullOrString($data['rva_permission_validateur_code'] ?? $data['permission_validateur_code'] ?? null, 120),
                'statut_id' => $this->nullableInt($data['rva_statut_id'] ?? $data['statut_id'] ?? null) ?? $this->statusId('general', 'actif'),
                'user_id' => $userId,
            ]
        );
        if ($ok) {
            $this->audit($userId, 'validation.regle.modifier', 'sav_regles_validation', $id, 'Modification règle validation');
        }
        return $ok;
    }

    public function softDeleteRegle(int $id, int $userId): bool
    {
        $ok = $this->db->execute(
            "UPDATE sav_regles_validation
             SET rva_supprime_le = NOW(), rva_supprime_par_utilisateur_id = :user_id
             WHERE rva_id = :id AND rva_supprime_le IS NULL",
            ['id' => $id, 'user_id' => $userId]
        );
        if ($ok) {
            $this->audit($userId, 'validation.regle.supprimer', 'sav_regles_validation', $id, 'Suppression logique règle validation');
        }
        return $ok;
    }

    public function utilisateurs(?int $societeId = null): array
    {
        $params = [];
        $join = '';
        $where = ['u.use_deleted_at IS NULL'];
        if ($societeId) {
            $join = 'INNER JOIN sav_adhesions_utilisateurs_societes aus ON aus.aus_utilisateur_id = u.use_id AND aus.aus_supprime_le IS NULL AND aus.aus_archive_le IS NULL AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= CURDATE())';
            $where[] = 'aus.aus_societe_id = :societe_id';
            $params['societe_id'] = $societeId;
        }
        return $this->db->fetchAll(
            "SELECT DISTINCT u.use_id, u.use_email,
                    CONCAT(COALESCE(p.pui_prenom, ''), ' ', COALESCE(p.pui_nom, '')) AS nom_complet
             FROM sav_utilisateurs u
             $join
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.use_id AND p.pui_supprime_le IS NULL
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.pui_nom ASC, p.pui_prenom ASC, u.use_email ASC
             LIMIT 500",
            $params
        );
    }

    public function refs(): array
    {
        return [
            'societes' => $this->db->fetchAll("SELECT soc_id, soc_nom FROM sav_societes WHERE soc_supprime_le IS NULL AND soc_archive_le IS NULL ORDER BY soc_nom ASC LIMIT 500"),
            'modules' => $this->db->fetchAll("SELECT mod_id, mod_code, mod_nom FROM sav_modules WHERE mod_supprime_le IS NULL AND mod_archive_le IS NULL ORDER BY mod_nom ASC LIMIT 200"),
            'permissions' => $this->db->fetchAll("SELECT per_id, per_code, per_description FROM sav_permissions WHERE per_supprime_le IS NULL AND per_archive_le IS NULL ORDER BY per_code ASC LIMIT 500"),
            'statuts' => $this->db->fetchAll("SELECT sta_id, sta_domaine, sta_code, sta_libelle FROM sav_statuts WHERE sta_supprime_le IS NULL AND sta_est_actif = 1 ORDER BY sta_domaine ASC, sta_ordre ASC, sta_libelle ASC"),
            'utilisateurs' => $this->utilisateurs(),
            'types_demande' => ['achat', 'finance', 'securite', 'donnee_sensible', 'creation_societe', 'modification_utilisateur', 'operation_sensible', 'validation_panier', 'autre'],
            'types_operation' => ['achat', 'finance', 'securite', 'donnee_sensible', 'creation_societe', 'modification_utilisateur', 'validation_panier', 'autre'],
            'decisions' => ['en_attente', 'approuvee', 'refusee', 'annulee'],
        ];
    }

    public function export(): array
    {
        return [
            'generated_at' => date(DATE_ATOM),
            'stats' => $this->stats(),
            'demandes' => $this->demandes([], 500),
            'regles' => $this->regles(),
        ];
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }
        return (int) $value;
    }

    private function nullOrString(mixed $value, int $max = 255): ?string
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return null;
        }
        return mb_substr($value, 0, $max);
    }

    private function jsonPayload(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }
        json_decode($text, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Le champ JSON est invalide : ' . json_last_error_msg());
        }
        return $text;
    }

    private function statusId(string $domain, string $code): ?int
    {
        $id = $this->db->fetchColumn(
            "SELECT sta_id FROM sav_statuts WHERE sta_domaine = :domain AND sta_code = :code AND sta_supprime_le IS NULL LIMIT 1",
            ['domain' => $domain, 'code' => $code]
        );
        return $id ? (int) $id : null;
    }

    private function audit(?int $userId, string $action, string $table, int $targetId, ?string $reason = null): void
    {
        $this->db->execute(
            "INSERT INTO sav_journaux_audit
                (jau_utilisateur_id, jau_societe_id, jau_action, jau_table_cible, jau_id_cible, jau_raison, jau_metadata_json)
             VALUES
                (:user_id, :societe_id, :action, :table_cible, :id_cible, :raison, :metadata_json)",
            [
                'user_id' => $userId,
                'societe_id' => $_SESSION['active_company_id'] ?? null,
                'action' => $action,
                'table_cible' => $table,
                'id_cible' => $targetId,
                'raison' => $reason,
                'metadata_json' => json_encode(['module' => 'Validation'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );
    }
}
