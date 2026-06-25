<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Core\Database\Database;
use PDO;

/**
 * Recherche globale alignée sur le schéma SQL actuel.
 * Utilisateurs : sav_utilisateurs + sav_profils_utilisateurs.
 * Sociétés/marques : sav_societes + affectations de types.
 */
final class GlobalSearchService implements ServiceInterface{
    private const MAX_RESULTS_PER_TYPE = 5;

    public function __construct(private Database $db) {}

    public function search(string $query, int $userId, bool $isSuperAdmin = false): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return ['users' => [], 'companies' => [], 'brands' => [], 'query' => $query];
        }

        $like  = '%' . $query . '%';
        $limit = self::MAX_RESULTS_PER_TYPE;
        $companyIds = $isSuperAdmin ? [] : $this->getUserCompanyIds($userId);

        return [
            'query'     => $query,
            'users'     => $this->searchUsers($like, $companyIds, $isSuperAdmin, $limit),
            'companies' => $this->searchCompanies($like, $companyIds, $isSuperAdmin, $limit),
            'brands'    => $this->searchBrands($like, $limit),
        ];
    }

    private function searchUsers(string $like, array $companyIds, bool $isSuperAdmin, int $limit): array
    {
        $where = "u.uti_supprime_le IS NULL
                  AND (u.uti_email LIKE :q1 OR u.uti_identifiant LIKE :q2
                       OR p.pui_nom LIKE :q3 OR p.pui_prenom LIKE :q4)";
        $params = [':q1'=>$like, ':q2'=>$like, ':q3'=>$like, ':q4'=>$like];

        if (!$isSuperAdmin && !empty($companyIds)) {
            $ph = [];
            foreach ($companyIds as $i => $id) {
                $ph[] = ':sc' . $i;
                $params[':sc' . $i] = $id;
            }
            $where .= " AND EXISTS (
                SELECT 1 FROM sav_adhesions_utilisateurs_societes aus
                WHERE aus.aus_utilisateur_id = u.uti_id
                  AND aus.aus_societe_id IN (" . implode(',', $ph) . ")
                  AND aus.aus_supprime_le IS NULL
                  AND aus.aus_archive_le IS NULL
            )";
        } elseif (!$isSuperAdmin) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT u.uti_id AS id,
                    TRIM(CONCAT(COALESCE(p.pui_prenom, ''), ' ', COALESCE(p.pui_nom, ''))) AS label,
                    u.uti_email AS sublabel,
                    NULL AS avatar,
                    NULL AS avatar_color,
                    'user' AS type,
                    CONCAT('/users/', u.uti_id) AS url
             FROM sav_utilisateurs u
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
             WHERE $where
             ORDER BY p.pui_nom ASC, p.pui_prenom ASC, u.uti_email ASC
             LIMIT :lim"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function searchCompanies(string $like, array $companyIds, bool $isSuperAdmin, int $limit): array
    {
        if (!$isSuperAdmin && empty($companyIds)) return [];

        $where  = "s.soc_supprime_le IS NULL AND (s.soc_nom LIKE :q1 OR s.soc_nom_court LIKE :q2 OR s.soc_code LIKE :q3)";
        $params = [':q1' => $like, ':q2' => $like, ':q3' => $like];

        if (!$isSuperAdmin && !empty($companyIds)) {
            $ph = [];
            foreach ($companyIds as $i => $id) {
                $ph[] = ':sc' . $i;
                $params[':sc' . $i] = $id;
            }
            $where .= " AND s.soc_id IN (" . implode(',', $ph) . ")";
        }

        $stmt = $this->db->prepare(
            "SELECT s.soc_id AS id,
                    s.soc_nom AS label,
                    (
                      SELECT GROUP_CONCAT(DISTINCT t.tso_nom ORDER BY t.tso_nom SEPARATOR ', ')
                      FROM sav_affectations_types_societes ats
                      INNER JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id
                      WHERE ats.ats_societe_id = s.soc_id
                        AND ats.ats_supprime_le IS NULL
                        AND ats.ats_archive_le IS NULL
                    ) AS sublabel,
                    NULL AS avatar,
                    NULL AS avatar_color,
                    'company' AS type,
                    CONCAT('/companies/', s.soc_id) AS url
             FROM sav_societes s
             WHERE $where
             ORDER BY s.soc_nom ASC
             LIMIT :lim"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function searchBrands(string $like, int $limit): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.soc_id AS id,
                    s.soc_nom AS label,
                    s.soc_code AS sublabel,
                    NULL AS avatar,
                    NULL AS avatar_color,
                    'brand' AS type,
                    CONCAT('/brands/', s.soc_id, '/edit') AS url
             FROM sav_societes s
             WHERE s.soc_supprime_le IS NULL
               AND s.soc_archive_le IS NULL
               AND (s.soc_nom LIKE :q1 OR s.soc_code LIKE :q2)
               AND EXISTS (
                   SELECT 1
                   FROM sav_affectations_types_societes ats
                   INNER JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id
                   WHERE ats.ats_societe_id = s.soc_id
                     AND ats.ats_supprime_le IS NULL
                     AND ats.ats_archive_le IS NULL
                     AND LOWER(t.tso_code) = 'marque'
               )
             ORDER BY s.soc_nom ASC
             LIMIT :lim"
        );
        $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getUserCompanyIds(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT aus_societe_id
             FROM sav_adhesions_utilisateurs_societes
             WHERE aus_utilisateur_id = :uid
               AND aus_supprime_le IS NULL
               AND aus_archive_le IS NULL
               AND (aus_termine_le IS NULL OR aus_termine_le >= CURDATE())",
            ['uid' => $userId]
        );
        return array_map('intval', array_column($rows, 'aus_societe_id'));
    }
}
