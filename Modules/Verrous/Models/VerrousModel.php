<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Verrous\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Verrous d'entités / concurrence / sessions / historique de contexte.
 *
 * Tables SQL réelles utilisées :
 * - sav_verrous_entites
 * - sav_verrous_maintenance
 * - sav_sessions_utilisateurs
 * - sav_historique_contextes_utilisateurs
 * - sav_utilisateurs
 * - sav_profils_utilisateurs
 * - sav_societes
 * - sav_statuts
 * - sav_journaux_audit
 */
class VerrousModel extends BaseModel
{
    protected string $table = 'sav_verrous_entites';
    protected string $colPrefix = 'ven_';

    public function stats(): array
    {
        return [
            'verrous_actifs' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_verrous_entites WHERE ven_libere_le IS NULL AND ven_expire_le > NOW()"),
            'verrous_expires' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_verrous_entites WHERE ven_libere_le IS NULL AND ven_expire_le <= NOW()"),
            'sessions_actives' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_sessions_utilisateurs WHERE seu_supprime_le IS NULL AND seu_revoquee_le IS NULL AND seu_expire_le > NOW()"),
            'contextes_24h' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_historique_contextes_utilisateurs WHERE hcu_cree_le >= DATE_SUB(NOW(), INTERVAL 1 DAY)"),
            'maintenance_active' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_verrous_maintenance WHERE vma_libere_le IS NULL AND vma_expire_le > NOW()"),
        ];
    }

    public function verrous(array $filters = [], int $limit = 300): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['cible_type'])) { $where[] = 'v.ven_cible_type = :cible_type'; $params['cible_type'] = (string)$filters['cible_type']; }
        if (!empty($filters['utilisateur_id'])) { $where[] = 'v.ven_utilisateur_id = :utilisateur_id'; $params['utilisateur_id'] = (int)$filters['utilisateur_id']; }
        if (($filters['etat'] ?? '') === 'actif') { $where[] = 'v.ven_libere_le IS NULL AND v.ven_expire_le > NOW()'; }
        if (($filters['etat'] ?? '') === 'expire') { $where[] = 'v.ven_libere_le IS NULL AND v.ven_expire_le <= NOW()'; }
        if (($filters['etat'] ?? '') === 'libere') { $where[] = 'v.ven_libere_le IS NOT NULL'; }
        if (!empty($filters['q'])) {
            $where[] = '(v.ven_cible_type LIKE :q OR v.ven_raison LIKE :q OR u.uti_email_normalise LIKE :q OR p.pui_nom LIKE :q OR p.pui_prenom LIKE :q)';
            $params['q'] = '%' . (string)$filters['q'] . '%';
        }
        $params['limit'] = max(1, min(1000, $limit));
        return $this->db->fetchAll(
            "SELECT v.*, u.uti_email_normalise, p.pui_nom, p.pui_prenom, st.sta_code AS statut_code, st.sta_libelle AS statut_libelle,
                    s.seu_identifiant_session_hash, s.seu_societe_active_id, soc.soc_nom AS societe_active_nom
             FROM sav_verrous_entites v
             INNER JOIN sav_utilisateurs u ON u.uti_id = v.ven_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
             LEFT JOIN sav_sessions_utilisateurs s ON s.seu_id = v.ven_session_utilisateur_id
             LEFT JOIN sav_societes soc ON soc.soc_id = s.seu_societe_active_id
             LEFT JOIN sav_statuts st ON st.sta_id = v.ven_statut_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY CASE WHEN v.ven_libere_le IS NULL AND v.ven_expire_le > NOW() THEN 0 WHEN v.ven_libere_le IS NULL THEN 1 ELSE 2 END,
                      v.ven_expire_le ASC, v.ven_verrouille_le DESC
             LIMIT :limit",
            $params
        );
    }

    public function sessions(array $filters = [], int $limit = 300): array
    {
        $where = ['se.seu_supprime_le IS NULL'];
        $params = [];
        if (($filters['etat_session'] ?? '') === 'active') { $where[] = 'se.seu_revoquee_le IS NULL AND se.seu_expire_le > NOW()'; }
        if (($filters['etat_session'] ?? '') === 'expiree') { $where[] = 'se.seu_expire_le <= NOW() AND se.seu_revoquee_le IS NULL'; }
        if (($filters['etat_session'] ?? '') === 'revoquee') { $where[] = 'se.seu_revoquee_le IS NOT NULL'; }
        if (!empty($filters['utilisateur_id'])) { $where[] = 'se.seu_utilisateur_id = :utilisateur_id'; $params['utilisateur_id'] = (int)$filters['utilisateur_id']; }
        if (!empty($filters['societe_id'])) { $where[] = 'se.seu_societe_active_id = :societe_id'; $params['societe_id'] = (int)$filters['societe_id']; }
        $params['limit'] = max(1, min(1000, $limit));
        return $this->db->fetchAll(
            "SELECT se.*, u.uti_email_normalise, p.pui_nom, p.pui_prenom, soc.soc_nom AS societe_active_nom,
                    st.sta_code AS statut_code, st.sta_libelle AS statut_libelle
             FROM sav_sessions_utilisateurs se
             INNER JOIN sav_utilisateurs u ON u.uti_id = se.seu_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
             LEFT JOIN sav_societes soc ON soc.soc_id = se.seu_societe_active_id
             LEFT JOIN sav_statuts st ON st.sta_id = se.seu_statut_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY se.seu_derniere_activite_le DESC, se.seu_expire_le DESC
             LIMIT :limit",
            $params
        );
    }

    public function contextes(array $filters = [], int $limit = 300): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['utilisateur_id'])) { $where[] = 'h.hcu_utilisateur_id = :utilisateur_id'; $params['utilisateur_id'] = (int)$filters['utilisateur_id']; }
        if (!empty($filters['societe_id'])) { $where[] = 'h.hcu_societe_id = :societe_id'; $params['societe_id'] = (int)$filters['societe_id']; }
        if (!empty($filters['action'])) { $where[] = 'h.hcu_action = :action'; $params['action'] = (string)$filters['action']; }
        if (!empty($filters['date_debut'])) { $where[] = 'DATE(h.hcu_cree_le) >= :date_debut'; $params['date_debut'] = (string)$filters['date_debut']; }
        if (!empty($filters['date_fin'])) { $where[] = 'DATE(h.hcu_cree_le) <= :date_fin'; $params['date_fin'] = (string)$filters['date_fin']; }
        $params['limit'] = max(1, min(1000, $limit));
        return $this->db->fetchAll(
            "SELECT h.*, u.uti_email_normalise, p.pui_nom, p.pui_prenom,
                    soc.soc_nom AS societe_nom, con.soc_nom AS concession_nom, mar.soc_nom AS marque_nom,
                    srv.srv_nom AS service_nom, equ.equ_nom AS equipe_nom
             FROM sav_historique_contextes_utilisateurs h
             INNER JOIN sav_utilisateurs u ON u.uti_id = h.hcu_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
             LEFT JOIN sav_societes soc ON soc.soc_id = h.hcu_societe_id
             LEFT JOIN sav_societes con ON con.soc_id = h.hcu_concession_id
             LEFT JOIN sav_societes mar ON mar.soc_id = h.hcu_marque_id
             LEFT JOIN sav_services srv ON srv.srv_id = h.hcu_service_id
             LEFT JOIN sav_equipes equ ON equ.equ_id = h.hcu_equipe_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY h.hcu_cree_le DESC
             LIMIT :limit",
            $params
        );
    }

    public function verrousMaintenance(int $limit = 200): array
    {
        return $this->db->fetchAll(
            "SELECT v.*, e.exm_code_politique, e.exm_statut, e.exm_debut_le, e.exm_fin_le
             FROM sav_verrous_maintenance v
             LEFT JOIN sav_executions_maintenance e ON e.exm_id = v.vma_execution_maintenance_id
             ORDER BY CASE WHEN v.vma_libere_le IS NULL AND v.vma_expire_le > NOW() THEN 0 ELSE 1 END, v.vma_expire_le DESC
             LIMIT :limit",
            ['limit' => max(1, min(500, $limit))]
        );
    }

    public function refs(): array
    {
        return [
            'utilisateurs' => $this->db->fetchAll("SELECT u.uti_id, u.uti_email_normalise, p.pui_nom, p.pui_prenom FROM sav_utilisateurs u LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL WHERE u.uti_supprime_le IS NULL ORDER BY p.pui_nom, p.pui_prenom, u.uti_email_normalise LIMIT 500"),
            'societes' => $this->db->fetchAll("SELECT soc_id, soc_nom FROM sav_societes WHERE soc_supprime_le IS NULL ORDER BY soc_nom LIMIT 500"),
            'cibles' => ['societe' => 'Société', 'utilisateur' => 'Utilisateur', 'document' => 'Document', 'fichier' => 'Fichier', 'validation' => 'Validation', 'standard' => 'Standard', 'autre' => 'Autre'],
            'etats' => ['actif' => 'Actif', 'expire' => 'Expiré', 'libere' => 'Libéré'],
        ];
    }

    public function creerVerrou(array $data, int $userId): int
    {
        $duree = max(1, min(1440, (int)($data['duree_minutes'] ?? 15)));
        $this->db->execute(
            "INSERT INTO sav_verrous_entites (ven_cible_type, ven_cible_id, ven_utilisateur_id, ven_session_utilisateur_id, ven_raison, ven_expire_le, ven_cree_le)
             VALUES (:type, :id, :user_id, :session_id, :raison, DATE_ADD(NOW(), INTERVAL :duree MINUTE), NOW())",
            [
                'type' => trim((string)($data['cible_type'] ?? 'autre')),
                'id' => (int)($data['cible_id'] ?? 0),
                'user_id' => (int)($data['utilisateur_id'] ?? $userId),
                'session_id' => !empty($data['session_utilisateur_id']) ? (int)$data['session_utilisateur_id'] : null,
                'raison' => trim((string)($data['raison'] ?? 'Verrou manuel')) ?: null,
                'duree' => $duree,
            ]
        );
        $id = (int)$this->db->lastInsertId();
        $this->audit($userId, 'verrou.creer', 'sav_verrous_entites', $id, ['cible_type' => $data['cible_type'] ?? 'autre', 'cible_id' => $data['cible_id'] ?? 0]);
        return $id;
    }

    public function libererVerrou(int $id, int $userId, string $raison = ''): bool
    {
        $ok = $this->db->execute("UPDATE sav_verrous_entites SET ven_libere_le = NOW(), ven_modifie_le = NOW() WHERE ven_id = :id AND ven_libere_le IS NULL", ['id' => $id]);
        $this->audit($userId, 'verrou.liberer', 'sav_verrous_entites', $id, ['raison' => $raison]);
        return $ok;
    }

    public function revoquerSession(int $id, int $userId, string $motif = ''): bool
    {
        $ok = $this->db->execute("UPDATE sav_sessions_utilisateurs SET seu_revoquee_le = NOW(), seu_motif_revocation = :motif, seu_modifie_le = NOW() WHERE seu_id = :id AND seu_revoquee_le IS NULL", ['id' => $id, 'motif' => $motif ?: 'Révocation administrative']);
        $this->audit($userId, 'session.revoquer', 'sav_sessions_utilisateurs', $id, ['motif' => $motif]);
        return $ok;
    }

    public function export(array $filters = []): array
    {
        return [
            'genere_le' => date('c'),
            'stats' => $this->stats(),
            'verrous' => $this->verrous($filters, 1000),
            'sessions' => $this->sessions($filters, 1000),
            'contextes' => $this->contextes($filters, 1000),
            'maintenance' => $this->verrousMaintenance(500),
        ];
    }

    private function audit(int $userId, string $action, string $table, int $id, array $metadata = []): void
    {
        $this->db->execute(
            "INSERT INTO sav_journaux_audit (jau_utilisateur_id, jau_action, jau_table_cible, jau_id_cible, jau_metadata_json, jau_cree_le)
             VALUES (:user_id, :action, :table_cible, :id_cible, :metadata, NOW())",
            [
                'user_id' => $userId ?: null,
                'action' => $action,
                'table_cible' => $table,
                'id_cible' => $id ?: null,
                'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );
    }
}
