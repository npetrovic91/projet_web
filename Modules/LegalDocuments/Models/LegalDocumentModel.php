<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\LegalDocuments\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Documents juridiques alignés sur :
 * - sav_documents_juridiques
 * - sav_liens_documents_juridiques_societes
 * - sav_acceptations_documents_juridiques_utilisateurs
 */
class LegalDocumentModel extends BaseModel
{
    protected string $table = 'sav_documents_juridiques';
    protected string $colPrefix = 'dju_';

    public function lister(array $filters = [], int $limit = 200): array
    {
        $where = ['d.dju_supprime_le IS NULL'];
        $params = [];
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(d.dju_titre LIKE :q OR d.dju_type_document LIKE :q OR d.dju_version LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }
        $type = trim((string)($filters['type'] ?? ''));
        if ($type !== '') {
            $where[] = 'd.dju_type_document = :type';
            $params['type'] = $type;
        }
        $companyId = (int)($filters['company_id'] ?? 0);
        if ($companyId > 0) {
            $where[] = '(d.dju_societe_id = :company_id OR l.ldj_societe_id = :company_id)';
            $params['company_id'] = $companyId;
        }
        $limit = max(1, min(500, $limit));
        return $this->db->fetchAll(
            'SELECT d.*, s.soc_nom AS societe_nom, m.soc_nom AS marque_nom,
                    COUNT(DISTINCT l.ldj_id) AS societes_liees,
                    COUNT(DISTINCT a.adj_id) AS acceptations_total
               FROM sav_documents_juridiques d
          LEFT JOIN sav_societes s ON s.soc_id = d.dju_societe_id
          LEFT JOIN sav_societes m ON m.soc_id = d.dju_marque_societe_id
          LEFT JOIN sav_liens_documents_juridiques_societes l ON l.ldj_document_juridique_id = d.dju_id AND l.ldj_supprime_le IS NULL
          LEFT JOIN sav_acceptations_documents_juridiques_utilisateurs a ON a.adj_document_juridique_id = d.dju_id
              WHERE ' . implode(' AND ', $where) . '
           GROUP BY d.dju_id
           ORDER BY d.dju_type_document ASC, d.dju_valide_du DESC, d.dju_cree_le DESC
              LIMIT ' . $limit,
            $params
        );
    }

    public function statistiques(): array
    {
        return $this->db->fetch(
            'SELECT COUNT(*) AS total,
                    COUNT(DISTINCT dju_type_document) AS types,
                    SUM(CASE WHEN dju_archive_le IS NOT NULL THEN 1 ELSE 0 END) AS archives,
                    SUM(CASE WHEN dju_valide_au IS NOT NULL AND dju_valide_au < CURDATE() THEN 1 ELSE 0 END) AS expires
               FROM sav_documents_juridiques
              WHERE dju_supprime_le IS NULL'
        ) ?? ['total' => 0, 'types' => 0, 'archives' => 0, 'expires' => 0];
    }

    public function types(): array
    {
        return $this->db->fetchAll(
            'SELECT dju_type_document AS type, COUNT(*) AS total
               FROM sav_documents_juridiques
              WHERE dju_supprime_le IS NULL
           GROUP BY dju_type_document
           ORDER BY dju_type_document ASC'
        );
    }

    public function trouver(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT d.*, s.soc_nom AS societe_nom, m.soc_nom AS marque_nom
               FROM sav_documents_juridiques d
          LEFT JOIN sav_societes s ON s.soc_id = d.dju_societe_id
          LEFT JOIN sav_societes m ON m.soc_id = d.dju_marque_societe_id
              WHERE d.dju_id = :id
                AND d.dju_supprime_le IS NULL
              LIMIT 1',
            ['id' => $id]
        );
    }

    public function creer(array $input, int $userId = 0): int
    {
        $data = $this->normaliser($input);
        $this->db->execute(
            'INSERT INTO sav_documents_juridiques
                (dju_societe_id, dju_marque_societe_id, dju_type_document, dju_titre, dju_version, dju_contenu,
                 dju_valide_du, dju_valide_au, dju_statut_id, dju_cree_le, dju_modifie_le, dju_cree_par_utilisateur_id)
             VALUES
                (:societe_id, :marque_id, :type, :titre, :version, :contenu,
                 :valide_du, :valide_au, :statut_id, NOW(), NOW(), :user_id)',
            [
                'societe_id' => $data['dju_societe_id'],
                'marque_id' => $data['dju_marque_societe_id'],
                'type' => $data['dju_type_document'],
                'titre' => $data['dju_titre'],
                'version' => $data['dju_version'],
                'contenu' => $data['dju_contenu'],
                'valide_du' => $data['dju_valide_du'],
                'valide_au' => $data['dju_valide_au'],
                'statut_id' => $data['dju_statut_id'],
                'user_id' => $userId ?: null,
            ]
        );
        $newId = (int)$this->pdo->lastInsertId();
        $this->journaliser('document_juridique.creer', $newId, $userId, ['type' => $data['dju_type_document'], 'titre' => $data['dju_titre']]);
        return $newId;
    }

    public function modifier(int $id, array $input, int $userId = 0): bool
    {
        $data = $this->normaliser($input);
        $this->db->execute(
            'UPDATE sav_documents_juridiques
                SET dju_societe_id = :societe_id,
                    dju_marque_societe_id = :marque_id,
                    dju_type_document = :type,
                    dju_titre = :titre,
                    dju_version = :version,
                    dju_contenu = :contenu,
                    dju_valide_du = :valide_du,
                    dju_valide_au = :valide_au,
                    dju_statut_id = :statut_id,
                    dju_modifie_le = NOW(),
                    dju_modifie_par_utilisateur_id = :user_id
              WHERE dju_id = :id AND dju_supprime_le IS NULL',
            [
                'id' => $id,
                'societe_id' => $data['dju_societe_id'],
                'marque_id' => $data['dju_marque_societe_id'],
                'type' => $data['dju_type_document'],
                'titre' => $data['dju_titre'],
                'version' => $data['dju_version'],
                'contenu' => $data['dju_contenu'],
                'valide_du' => $data['dju_valide_du'],
                'valide_au' => $data['dju_valide_au'],
                'statut_id' => $data['dju_statut_id'],
                'user_id' => $userId ?: null,
            ]
        );
        $ok = $this->db->rowCount() > 0;
        if ($ok) {
            $this->journaliser('document_juridique.modifier', $id, $userId, ['version' => $data['dju_version']]);
        }
        return $ok;
    }

    public function supprimer(int $id, int $userId = 0): bool
    {
        $this->db->execute(
            'UPDATE sav_documents_juridiques
                SET dju_supprime_le = NOW(), dju_modifie_le = NOW(), dju_supprime_par_utilisateur_id = :user_id
              WHERE dju_id = :id AND dju_supprime_le IS NULL',
            ['id' => $id, 'user_id' => $userId ?: null]
        );
        $ok = $this->db->rowCount() > 0;
        if ($ok) {
            $this->journaliser('document_juridique.supprimer', $id, $userId, []);
        }
        return $ok;
    }

    public function lierSociete(int $documentId, int $companyId, bool $mandatory, int $priority = 100, ?int $statusId = null): void
    {
        $this->db->execute(
            'INSERT INTO sav_liens_documents_juridiques_societes
                (ldj_societe_id, ldj_document_juridique_id, ldj_priorite, ldj_est_obligatoire, ldj_statut_id, ldj_cree_le, ldj_modifie_le)
             VALUES (:company_id, :document_id, :priority, :mandatory, :status_id, NOW(), NOW())',
            [
                'company_id' => $companyId,
                'document_id' => $documentId,
                'priority' => max(0, $priority),
                'mandatory' => $mandatory ? 1 : 0,
                'status_id' => $statusId,
            ]
        );
        $this->journaliser('document_juridique.lier_societe', $documentId, null, ['company_id' => $companyId, 'mandatory' => $mandatory]);
    }

    public function liensSocietes(int $documentId): array
    {
        return $this->db->fetchAll(
            'SELECT l.*, s.soc_nom
               FROM sav_liens_documents_juridiques_societes l
               JOIN sav_societes s ON s.soc_id = l.ldj_societe_id
              WHERE l.ldj_document_juridique_id = :id
                AND l.ldj_supprime_le IS NULL
           ORDER BY l.ldj_priorite ASC, s.soc_nom ASC',
            ['id' => $documentId]
        );
    }

    public function acceptations(int $documentId, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        return $this->db->fetchAll(
            'SELECT a.*, u.uti_email, p.pui_nom, p.pui_prenom
               FROM sav_acceptations_documents_juridiques_utilisateurs a
               JOIN sav_utilisateurs u ON u.uti_id = a.adj_utilisateur_id
          LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
              WHERE a.adj_document_juridique_id = :id
           ORDER BY a.adj_accepte_le DESC
              LIMIT ' . $limit,
            ['id' => $documentId]
        );
    }

    public function accepter(int $documentId, int $userId, string $version, string $ip, string $userAgent): void
    {
        $this->db->execute(
            'INSERT INTO sav_acceptations_documents_juridiques_utilisateurs
                (adj_utilisateur_id, adj_document_juridique_id, adj_version, adj_accepte_le, adj_adresse_ip, adj_user_agent, adj_cree_le)
             VALUES (:user_id, :document_id, :version, NOW(), :ip, :ua, NOW())',
            [
                'user_id' => $userId,
                'document_id' => $documentId,
                'version' => $version,
                'ip' => inet_pton($ip) ?: null,
                'ua' => mb_substr($userAgent, 0, 255),
            ]
        );
    }

    public function societes(): array
    {
        return $this->db->fetchAll('SELECT soc_id, soc_nom, soc_code FROM sav_societes WHERE soc_supprime_le IS NULL ORDER BY soc_nom ASC LIMIT 500');
    }

    public function statuts(): array
    {
        return $this->db->fetchAll('SELECT sta_id, sta_code, sta_libelle FROM sav_statuts ORDER BY sta_libelle ASC');
    }

    private function normaliser(array $input): array
    {
        $type = trim((string)($input['dju_type_document'] ?? '')) ?: 'cgu';
        $title = trim((string)($input['dju_titre'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Le titre est obligatoire.');
        }
        $content = (string)($input['dju_contenu'] ?? '');
        if (trim($content) === '') {
            throw new \InvalidArgumentException('Le contenu du document est obligatoire.');
        }
        return [
            'dju_societe_id' => $this->nullableInt($input['dju_societe_id'] ?? null),
            'dju_marque_societe_id' => $this->nullableInt($input['dju_marque_societe_id'] ?? null),
            'dju_type_document' => mb_substr($type, 0, 80),
            'dju_titre' => mb_substr($title, 0, 160),
            'dju_version' => mb_substr(trim((string)($input['dju_version'] ?? '1.0')) ?: '1.0', 0, 30),
            'dju_contenu' => $content,
            'dju_valide_du' => $this->nullableDate($input['dju_valide_du'] ?? null),
            'dju_valide_au' => $this->nullableDate($input['dju_valide_au'] ?? null),
            'dju_statut_id' => $this->nullableInt($input['dju_statut_id'] ?? null),
        ];
    }

    private function nullableInt(mixed $value): ?int
    {
        $int = (int)$value;
        return $int > 0 ? $int : null;
    }

    private function nullableDate(mixed $value): ?string
    {
        $value = trim((string)$value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    /** CORRECTIF 2.3 (audit) : aucune mutation n'était journalisée. */
    private function journaliser(string $action, int $id, ?int $userId, array $metadata): void
    {
        try {
            $this->db->execute(
                'INSERT INTO sav_journaux_audit
                    (jau_utilisateur_id, jau_action, jau_table_cible, jau_id_cible, jau_adresse_ip, jau_metadata_json, jau_cree_le)
                 VALUES
                    (:user_id, :action, :table_cible, :id_cible, :ip, :metadata, NOW())',
                [
                    'user_id' => $userId ?: null,
                    'action' => $action,
                    'table_cible' => 'sav_documents_juridiques',
                    'id_cible' => $id ?: null,
                    'ip' => function_exists('client_ip') ? @inet_pton((string) client_ip()) : null,
                    'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable) {
            // L'audit ne doit jamais bloquer une opération métier.
        }
    }
}
