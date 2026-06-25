<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Contacts\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Contacts sociétés alignés sur la base SQL française.
 *
 * Tables couvertes :
 * - sav_contacts_societes : contacts internes ou externes rattachés à une société ;
 * - sav_types_contacts : typologie administrable des contacts ;
 * - sav_societes, sav_utilisateurs, sav_profils_utilisateurs, sav_statuts.
 */
class ContactSocieteModel extends BaseModel
{
    protected string $table = 'sav_contacts_societes';
    protected string $colPrefix = 'cts_';

    public function stats(?int $societeId = null): array
    {
        $where = 'cts.cts_supprime_le IS NULL';
        $params = [];
        if ($societeId !== null && $societeId > 0) {
            $where .= ' AND cts.cts_societe_id = :societe_id';
            $params['societe_id'] = $societeId;
        }

        $row = $this->fetchOne("SELECT
                COUNT(*) AS contacts_total,
                SUM(CASE WHEN cts.cts_utilisateur_id IS NOT NULL THEN 1 ELSE 0 END) AS contacts_internes,
                SUM(CASE WHEN cts.cts_email_externe IS NOT NULL AND cts.cts_email_externe <> '' THEN 1 ELSE 0 END) AS contacts_externes,
                COUNT(DISTINCT cts.cts_societe_id) AS societes_couvertes
            FROM sav_contacts_societes cts
            WHERE {$where}", $params) ?? [];

        $types = $this->fetchAll("SELECT tco.tco_id, tco.tco_nom, COUNT(cts.cts_id) AS total
            FROM sav_types_contacts tco
            LEFT JOIN sav_contacts_societes cts
              ON cts.cts_type_contact_id = tco.tco_id
             AND cts.cts_supprime_le IS NULL" . ($societeId ? ' AND cts.cts_societe_id = :type_societe_id' : '') . "
            WHERE tco.tco_supprime_le IS NULL
            GROUP BY tco.tco_id, tco.tco_nom
            ORDER BY tco.tco_nom", $societeId ? ['type_societe_id' => $societeId] : []);

        return [
            'contacts_total' => (int)($row['contacts_total'] ?? 0),
            'contacts_internes' => (int)($row['contacts_internes'] ?? 0),
            'contacts_externes' => (int)($row['contacts_externes'] ?? 0),
            'societes_couvertes' => (int)($row['societes_couvertes'] ?? 0),
            'par_type' => $types,
        ];
    }

    public function listerContacts(array $filters = []): array
    {
        $where = ['cts.cts_supprime_le IS NULL'];
        $params = [];
        if (!empty($filters['societe_id'])) {
            $where[] = 'cts.cts_societe_id = :societe_id';
            $params['societe_id'] = (int)$filters['societe_id'];
        }
        if (!empty($filters['type_contact_id'])) {
            $where[] = 'cts.cts_type_contact_id = :type_contact_id';
            $params['type_contact_id'] = (int)$filters['type_contact_id'];
        }
        if (!empty($filters['statut_id'])) {
            $where[] = 'cts.cts_statut_id = :statut_id';
            $params['statut_id'] = (int)$filters['statut_id'];
        }
        if (($filters['mode'] ?? '') === 'interne') {
            $where[] = 'cts.cts_utilisateur_id IS NOT NULL';
        } elseif (($filters['mode'] ?? '') === 'externe') {
            $where[] = 'cts.cts_utilisateur_id IS NULL';
        }
        if (!empty($filters['q'])) {
            $where[] = '(cts.cts_email_externe LIKE :q OR cts.cts_prenom LIKE :q OR cts.cts_nom LIKE :q OR soc.soc_nom LIKE :q OR pui.pui_nom LIKE :q OR pui.pui_prenom LIKE :q OR uti.uti_email LIKE :q)';
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }

        return $this->fetchAll("SELECT
                cts.*,
                soc.soc_nom AS societe_nom,
                tco.tco_nom AS type_contact_nom,
                sta.sta_libelle AS statut_nom,
                uti.uti_email AS utilisateur_email,
                pui.pui_prenom AS utilisateur_prenom,
                pui.pui_nom AS utilisateur_nom,
                COALESCE(NULLIF(CONCAT(TRIM(COALESCE(cts.cts_prenom, '')), ' ', TRIM(COALESCE(cts.cts_nom, ''))), ' '),
                         NULLIF(CONCAT(TRIM(COALESCE(pui.pui_prenom, '')), ' ', TRIM(COALESCE(pui.pui_nom, ''))), ' '),
                         cts.cts_email_externe,
                         uti.uti_email) AS contact_libelle,
                COALESCE(cts.cts_email_externe, uti.uti_email) AS contact_email
            FROM sav_contacts_societes cts
            INNER JOIN sav_societes soc ON soc.soc_id = cts.cts_societe_id
            LEFT JOIN sav_types_contacts tco ON tco.tco_id = cts.cts_type_contact_id
            LEFT JOIN sav_statuts sta ON sta.sta_id = cts.cts_statut_id
            LEFT JOIN sav_utilisateurs uti ON uti.uti_id = cts.cts_utilisateur_id
            LEFT JOIN sav_profils_utilisateurs pui ON pui.pui_utilisateur_id = uti.uti_id AND pui.pui_supprime_le IS NULL
            WHERE " . implode(' AND ', $where) . "
            ORDER BY soc.soc_nom ASC, tco.tco_nom ASC, contact_libelle ASC, cts.cts_id DESC", $params);
    }

    public function trouverContact(int $id): ?array
    {
        return $this->fetchOne("SELECT cts.*, soc.soc_nom AS societe_nom, tco.tco_nom AS type_contact_nom,
                uti.uti_email AS utilisateur_email, pui.pui_prenom AS utilisateur_prenom, pui.pui_nom AS utilisateur_nom
            FROM sav_contacts_societes cts
            INNER JOIN sav_societes soc ON soc.soc_id = cts.cts_societe_id
            LEFT JOIN sav_types_contacts tco ON tco.tco_id = cts.cts_type_contact_id
            LEFT JOIN sav_utilisateurs uti ON uti.uti_id = cts.cts_utilisateur_id
            LEFT JOIN sav_profils_utilisateurs pui ON pui.pui_utilisateur_id = uti.uti_id AND pui.pui_supprime_le IS NULL
            WHERE cts.cts_id = :id AND cts.cts_supprime_le IS NULL
            LIMIT 1", ['id' => $id]);
    }

    public function enregistrerContact(array $data, ?int $id = null, ?int $userId = null): int
    {
        $payload = [
            'cts_societe_id' => (int)($data['cts_societe_id'] ?? 0),
            'cts_utilisateur_id' => !empty($data['cts_utilisateur_id']) ? (int)$data['cts_utilisateur_id'] : null,
            'cts_email_externe' => trim((string)($data['cts_email_externe'] ?? '')) ?: null,
            'cts_prenom' => trim((string)($data['cts_prenom'] ?? '')) ?: null,
            'cts_nom' => trim((string)($data['cts_nom'] ?? '')) ?: null,
            'cts_type_contact_id' => !empty($data['cts_type_contact_id']) ? (int)$data['cts_type_contact_id'] : null,
            'cts_statut_id' => !empty($data['cts_statut_id']) ? (int)$data['cts_statut_id'] : null,
        ];
        if ($payload['cts_societe_id'] <= 0) {
            throw new \InvalidArgumentException('La société du contact est obligatoire.');
        }
        if ($payload['cts_utilisateur_id'] === null && $payload['cts_email_externe'] === null) {
            throw new \InvalidArgumentException('Un contact doit être lié à un utilisateur interne ou posséder un email externe.');
        }
        if ($payload['cts_email_externe'] !== null && !filter_var($payload['cts_email_externe'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('L’email externe du contact est invalide.');
        }

        if ($id !== null) {
            $payload['cts_modifie_par_utilisateur_id'] = $userId;
            $this->query('UPDATE sav_contacts_societes SET
                    cts_societe_id = :cts_societe_id,
                    cts_utilisateur_id = :cts_utilisateur_id,
                    cts_email_externe = :cts_email_externe,
                    cts_prenom = :cts_prenom,
                    cts_nom = :cts_nom,
                    cts_type_contact_id = :cts_type_contact_id,
                    cts_statut_id = :cts_statut_id,
                    cts_modifie_par_utilisateur_id = :cts_modifie_par_utilisateur_id,
                    cts_modifie_le = NOW()
                WHERE cts_id = :id AND cts_supprime_le IS NULL', $payload + ['id' => $id]);
            return $id;
        }

        $payload['cts_cree_par_utilisateur_id'] = $userId;
        $this->query('INSERT INTO sav_contacts_societes
            (cts_societe_id, cts_utilisateur_id, cts_email_externe, cts_prenom, cts_nom, cts_type_contact_id, cts_statut_id, cts_cree_par_utilisateur_id, cts_cree_le)
            VALUES
            (:cts_societe_id, :cts_utilisateur_id, :cts_email_externe, :cts_prenom, :cts_nom, :cts_type_contact_id, :cts_statut_id, :cts_cree_par_utilisateur_id, NOW())', $payload);
        return (int)$this->pdo->lastInsertId();
    }

    public function supprimerContact(int $id, ?int $userId = null): void
    {
        $this->query('UPDATE sav_contacts_societes
            SET cts_supprime_le = NOW(), cts_supprime_par_utilisateur_id = :user_id
            WHERE cts_id = :id AND cts_supprime_le IS NULL', ['id' => $id, 'user_id' => $userId]);
    }

    public function listerTypes(array $filters = []): array
    {
        $where = ['tco.tco_supprime_le IS NULL'];
        $params = [];
        if (!empty($filters['q'])) {
            $where[] = '(tco.tco_code LIKE :q OR tco.tco_nom LIKE :q OR tco.tco_description LIKE :q)';
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }
        return $this->fetchAll("SELECT tco.*, sta.sta_libelle AS statut_nom,
                COUNT(cts.cts_id) AS contacts_total
            FROM sav_types_contacts tco
            LEFT JOIN sav_statuts sta ON sta.sta_id = tco.tco_statut_id
            LEFT JOIN sav_contacts_societes cts ON cts.cts_type_contact_id = tco.tco_id AND cts.cts_supprime_le IS NULL
            WHERE " . implode(' AND ', $where) . "
            GROUP BY tco.tco_id
            ORDER BY tco.tco_nom ASC", $params);
    }

    public function trouverType(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM sav_types_contacts WHERE tco_id = :id AND tco_supprime_le IS NULL LIMIT 1', ['id' => $id]);
    }

    public function enregistrerType(array $data, ?int $id = null): int
    {
        $payload = [
            'tco_code' => trim((string)($data['tco_code'] ?? '')),
            'tco_nom' => trim((string)($data['tco_nom'] ?? '')),
            'tco_description' => trim((string)($data['tco_description'] ?? '')) ?: null,
            'tco_statut_id' => !empty($data['tco_statut_id']) ? (int)$data['tco_statut_id'] : null,
        ];
        if ($payload['tco_code'] === '' || $payload['tco_nom'] === '') {
            throw new \InvalidArgumentException('Le code et le nom du type de contact sont obligatoires.');
        }
        if ($id !== null) {
            $this->query('UPDATE sav_types_contacts SET
                tco_code = :tco_code, tco_nom = :tco_nom, tco_description = :tco_description,
                tco_statut_id = :tco_statut_id, tco_modifie_le = NOW()
                WHERE tco_id = :id AND tco_supprime_le IS NULL', $payload + ['id' => $id]);
            return $id;
        }
        $this->query('INSERT INTO sav_types_contacts (tco_code, tco_nom, tco_description, tco_statut_id, tco_cree_le)
            VALUES (:tco_code, :tco_nom, :tco_description, :tco_statut_id, NOW())', $payload);
        return (int)$this->pdo->lastInsertId();
    }

    public function supprimerType(int $id): void
    {
        $this->query('UPDATE sav_types_contacts SET tco_supprime_le = NOW() WHERE tco_id = :id AND tco_supprime_le IS NULL', ['id' => $id]);
    }

    public function societes(): array
    {
        return $this->fetchAll('SELECT soc_id, soc_nom, soc_code FROM sav_societes WHERE soc_supprime_le IS NULL ORDER BY soc_nom ASC');
    }

    public function utilisateurs(?int $societeId = null): array
    {
        $params = [];
        $join = '';
        $where = 'uti.uti_supprime_le IS NULL';
        if ($societeId !== null && $societeId > 0) {
            $join = 'INNER JOIN sav_adhesions_utilisateurs_societes aus ON aus.aus_utilisateur_id = uti.uti_id AND aus.aus_supprime_le IS NULL AND aus.aus_societe_id = :societe_id';
            $params['societe_id'] = $societeId;
        }
        return $this->fetchAll("SELECT DISTINCT uti.uti_id, uti.uti_email, pui.pui_prenom, pui.pui_nom,
                COALESCE(NULLIF(CONCAT(TRIM(COALESCE(pui.pui_prenom, '')), ' ', TRIM(COALESCE(pui.pui_nom, ''))), ' '), uti.uti_email) AS utilisateur_libelle
            FROM sav_utilisateurs uti
            {$join}
            LEFT JOIN sav_profils_utilisateurs pui ON pui.pui_utilisateur_id = uti.uti_id AND pui.pui_supprime_le IS NULL
            WHERE {$where}
            ORDER BY utilisateur_libelle ASC", $params);
    }

    public function statuts(): array
    {
        return $this->fetchAll("SELECT sta_id, sta_libelle AS sta_nom, sta_code, sta_domaine FROM sav_statuts WHERE sta_supprime_le IS NULL ORDER BY sta_domaine ASC, sta_libelle ASC");
    }

    public function audit(string $action, string $table, int $targetId, ?int $userId, ?string $ip = null, array $meta = []): void
    {
        $this->query('INSERT INTO sav_journaux_audit
            (jau_utilisateur_id, jau_action, jau_table_cible, jau_id_cible, jau_adresse_ip, jau_metadata_json, jau_cree_le)
            VALUES (:user_id, :action, :table_cible, :id_cible, INET6_ATON(:ip), :metadata, NOW())', [
            'user_id' => $userId,
            'action' => $action,
            'table_cible' => $table,
            'id_cible' => $targetId,
            'ip' => $ip ?: '127.0.0.1',
            'metadata' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
