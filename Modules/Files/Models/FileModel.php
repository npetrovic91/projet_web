<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Files\Models;

use Nenad\Autosav\Core\Model\BaseModel;
use Nenad\Autosav\Core\Security\Encryption;
use Nenad\Autosav\Core\Security\Class\FileUploadValidator;

/**
 * Gestion des fichiers alignée sur :
 * - sav_fichiers
 * - sav_contenus_fichiers
 * - sav_liaisons_fichiers
 */
class FileModel extends BaseModel
{
    protected string $table = 'sav_fichiers';
    protected string $colPrefix = 'fic_';

    public function lister(array $filters = [], int $userId = 0, int $companyId = 0, bool $bypass = false, int $limit = 200): array
    {
        $where = ['f.fic_supprime_le IS NULL'];
        $params = [];
        $this->addAccessScope($where, $params, 'f', $userId, $companyId, $bypass);

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(f.fic_nom_original LIKE :q OR f.fic_mime_type LIKE :q OR f.fic_checksum_sha256 LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $targetType = trim((string)($filters['target_type'] ?? ''));
        if ($targetType !== '') {
            $where[] = 'l.lfi_cible_type = :target_type AND l.lfi_supprime_le IS NULL';
            $params['target_type'] = $targetType;
        }

        $targetId = (int)($filters['target_id'] ?? 0);
        if ($targetId > 0) {
            $where[] = 'l.lfi_cible_id = :target_id AND l.lfi_supprime_le IS NULL';
            $params['target_id'] = $targetId;
        }

        $limit = max(1, min(500, $limit));
        return $this->db->fetchAll(
            'SELECT f.*,
                    COUNT(l.lfi_id) AS liaisons_total,
                    GROUP_CONCAT(DISTINCT CONCAT(l.lfi_cible_type, "#", l.lfi_cible_id) ORDER BY l.lfi_cible_type SEPARATOR ", ") AS cibles
               FROM sav_fichiers f
          LEFT JOIN sav_liaisons_fichiers l ON l.lfi_fichier_id = f.fic_id AND l.lfi_supprime_le IS NULL
              WHERE ' . implode(' AND ', $where) . '
           GROUP BY f.fic_id
           ORDER BY f.fic_cree_le DESC, f.fic_id DESC
              LIMIT ' . $limit,
            $params
        );
    }

    public function statistiques(int $userId = 0, int $companyId = 0, bool $bypass = false): array
    {
        $where = ['f.fic_supprime_le IS NULL'];
        $params = [];
        $this->addAccessScope($where, $params, 'f', $userId, $companyId, $bypass);
        return $this->db->fetch(
            'SELECT COUNT(*) AS total,
                    COALESCE(SUM(f.fic_taille_octets), 0) AS taille_octets,
                    SUM(CASE WHEN f.fic_est_chiffre = 1 THEN 1 ELSE 0 END) AS chiffres,
                    SUM(CASE WHEN f.fic_archive_le IS NOT NULL THEN 1 ELSE 0 END) AS archives
               FROM sav_fichiers f
              WHERE ' . implode(' AND ', $where),
            $params
        ) ?? ['total' => 0, 'taille_octets' => 0, 'chiffres' => 0, 'archives' => 0];
    }

    public function trouver(int $id, int $userId = 0, int $companyId = 0, bool $bypass = false): ?array
    {
        $where = ['f.fic_id = :id', 'f.fic_supprime_le IS NULL'];
        $params = ['id' => $id];
        $this->addAccessScope($where, $params, 'f', $userId, $companyId, $bypass);
        return $this->db->fetch(
            'SELECT f.* FROM sav_fichiers f WHERE ' . implode(' AND ', $where) . ' LIMIT 1',
            $params
        );
    }

    public function contenu(int $id, int $userId = 0, int $companyId = 0, bool $bypass = false): ?array
    {
        $where = ['f.fic_id = :id', 'f.fic_supprime_le IS NULL'];
        $params = ['id' => $id];
        $this->addAccessScope($where, $params, 'f', $userId, $companyId, $bypass);
        $row = $this->db->fetch(
            'SELECT f.*, c.cfi_contenu_blob
               FROM sav_fichiers f
               JOIN sav_contenus_fichiers c ON c.cfi_fichier_id = f.fic_id
              WHERE ' . implode(' AND ', $where) . '
              LIMIT 1',
            $params
        );
        if ($row && !empty($row['fic_est_chiffre'])) {
            $row['cfi_contenu_blob'] = (new Encryption())->decrypt((string) $row['cfi_contenu_blob']);
        }
        return $row;
    }

    public function liaisons(int $fileId, int $userId = 0, int $companyId = 0, bool $bypass = false): array
    {
        if (!$this->trouver($fileId, $userId, $companyId, $bypass)) {
            return [];
        }
        return $this->db->fetchAll(
            'SELECT *
               FROM sav_liaisons_fichiers
              WHERE lfi_fichier_id = :id
                AND lfi_supprime_le IS NULL
           ORDER BY lfi_cible_type ASC, lfi_ordre ASC, lfi_id ASC',
            ['id' => $fileId]
        );
    }

    public function creerDepuisUpload(array $file, array $input, int $userId = 0, int $companyId = 0): int
    {
        $validated = FileUploadValidator::validate($file);
        $tmp = $validated['tmp'];
        $content = file_get_contents($tmp);
        if ($content === false) {
            throw new \RuntimeException('Impossible de lire le contenu du fichier.');
        }

        $original = (string) $validated['original'];
        $mime = (string) $validated['mime'];

        $this->pdo->beginTransaction();
        try {
            $uuid = $this->uuid();
            $checksum = hash('sha256', $content);
            $isEncrypted = !empty($input['fic_est_chiffre']);
            $storedContent = $isEncrypted ? (new Encryption())->encrypt($content) : $content;
            $this->db->execute(
                'INSERT INTO sav_fichiers
                    (fic_uuid, fic_nom_original, fic_nom_stockage, fic_mime_type, fic_taille_octets, fic_checksum_sha256,
                     fic_est_chiffre, fic_statut_id, fic_cree_le, fic_modifie_le, fic_cree_par_utilisateur_id)
                 VALUES
                    (:uuid, :original, :storage, :mime, :size, :checksum,
                     :encrypted, :statut_id, NOW(), NOW(), :user_id)',
                [
                    'uuid' => $uuid,
                    'original' => mb_substr($original, 0, 255),
                    'storage' => $uuid . '-' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', mb_substr($original, 0, 120)),
                    'mime' => mb_substr($mime, 0, 120),
                    'size' => strlen($content),
                    'checksum' => $checksum,
                    'encrypted' => $isEncrypted ? 1 : 0,
                    'statut_id' => $this->nullableInt($input['fic_statut_id'] ?? null),
                    'user_id' => $userId ?: null,
                ]
            );
            $id = (int)$this->pdo->lastInsertId();
            $this->db->execute(
                'INSERT INTO sav_contenus_fichiers (cfi_fichier_id, cfi_contenu_blob, cfi_cree_le, cfi_modifie_le)
                 VALUES (:id, :content, NOW(), NOW())',
                ['id' => $id, 'content' => $storedContent]
            );

            if ($companyId > 0) {
                $this->lier($id, 'societe', $companyId, 'contexte_societe', $userId, false, $userId, $companyId);
            }
            $targetType = trim((string)($input['lfi_cible_type'] ?? ''));
            $targetId = (int)($input['lfi_cible_id'] ?? 0);
            if ($targetType !== '' && $targetId > 0) {
                $this->lier($id, $targetType, $targetId, (string)($input['lfi_type_liaison'] ?? 'piece_jointe'), $userId, false, $userId, $companyId);
            }
            $this->pdo->commit();
            $this->journaliser('fichier.upload', $id, $userId, ['nom' => $original, 'taille' => strlen($content)]);
            return $id;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function lier(int $fileId, string $targetType, int $targetId, string $linkType = 'piece_jointe', int $userId = 0, bool $transaction = true, int $accessUserId = 0, int $companyId = 0, bool $bypass = false): void
    {
        if (!$this->trouver($fileId, $accessUserId ?: $userId, $companyId, $bypass)) {
            throw new \RuntimeException('Fichier introuvable ou non autorise.');
        }
        if (!$bypass && mb_strtolower(trim($targetType)) === 'societe' && $targetId !== $companyId) {
            throw new \RuntimeException('Liaison vers une autre societe interdite.');
        }
        if ($transaction && !$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
        }
        try {
            $this->db->execute(
                'INSERT INTO sav_liaisons_fichiers
                    (lfi_fichier_id, lfi_cible_type, lfi_cible_id, lfi_type_liaison, lfi_ordre, lfi_cree_le, lfi_modifie_le, lfi_cree_par_utilisateur_id)
                 VALUES
                    (:file_id, :target_type, :target_id, :link_type, :ordre, NOW(), NOW(), :user_id)',
                [
                    'file_id' => $fileId,
                    'target_type' => mb_substr($targetType, 0, 120),
                    'target_id' => $targetId,
                    'link_type' => mb_substr($linkType ?: 'piece_jointe', 0, 80),
                    'ordre' => 100,
                    'user_id' => $userId ?: null,
                ]
            );
            if ($transaction && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($transaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function supprimer(int $id, int $userId = 0, int $companyId = 0, bool $bypass = false): bool
    {
        $where = ['fic_id = :id', 'fic_supprime_le IS NULL'];
        $params = ['id' => $id, 'user_id' => $userId ?: null];
        $this->addAccessScope($where, $params, 'sav_fichiers', $userId, $companyId, $bypass);
        $this->db->execute(
            'UPDATE sav_fichiers
                SET fic_supprime_le = NOW(), fic_modifie_le = NOW(), fic_supprime_par_utilisateur_id = :user_id
              WHERE ' . implode(' AND ', $where),
            $params
        );
        $ok = $this->db->rowCount() > 0;
        if ($ok) {
            $this->journaliser('fichier.supprimer', $id, $userId, []);
        }
        return $ok;
    }

    public function statuts(): array
    {
        return $this->db->fetchAll('SELECT sta_id, sta_code, sta_libelle FROM sav_statuts ORDER BY sta_libelle ASC');
    }

    private function nullableInt(mixed $value): ?int
    {
        $int = (int)$value;
        return $int > 0 ? $int : null;
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
                    'table_cible' => 'sav_fichiers',
                    'id_cible' => $id ?: null,
                    'ip' => function_exists('client_ip') ? @inet_pton((string) client_ip()) : null,
                    'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable) {
            // L'audit ne doit jamais bloquer une opération métier.
        }
    }

    private function addAccessScope(array &$where, array &$params, string $alias, int $userId, int $companyId, bool $bypass): void
    {
        if ($bypass) {
            return;
        }
        if ($userId <= 0 || $companyId <= 0) {
            $where[] = '1 = 0';
            return;
        }
        $where[] = '(' . $alias . '.fic_cree_par_utilisateur_id = :access_user_id
            OR EXISTS (
                SELECT 1
                  FROM sav_liaisons_fichiers access_link
                 WHERE access_link.lfi_fichier_id = ' . $alias . '.fic_id
                   AND access_link.lfi_cible_type = \'societe\'
                   AND access_link.lfi_cible_id = :access_company_id
                   AND access_link.lfi_supprime_le IS NULL
            ))';
        $params['access_user_id'] = $userId;
        $params['access_company_id'] = $companyId;
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
