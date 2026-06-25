<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Relations\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Relations sociétés alignées sur la base SQL française.
 *
 * Tables couvertes :
 * - sav_relations_societes : relations historisées entre sociétés ;
 * - sav_types_relations_societes : référentiel des types de relations ;
 * - sav_representations_marques_societes : représentation concession / marque / importateur / constructeur ;
 * - sav_societes, sav_statuts, sav_journaux_audit.
 */
class RelationSocieteModel extends BaseModel
{
    protected string $table = 'sav_relations_societes';
    protected string $colPrefix = 'rso_';

    public function stats(?int $societeId = null): array
    {
        $where = ['rso.rso_supprime_le IS NULL', 'rso.rso_archive_le IS NULL'];
        $params = [];
        if ($societeId !== null && $societeId > 0) {
            $where[] = '(rso.rso_societe_source_id = :societe_id OR rso.rso_societe_cible_id = :societe_id)';
            $params['societe_id'] = $societeId;
        }
        $clause = implode(' AND ', $where);
        $row = $this->fetchOne("SELECT
                COUNT(*) AS relations_total,
                COUNT(DISTINCT rso.rso_societe_source_id) AS societes_sources,
                COUNT(DISTINCT rso.rso_societe_cible_id) AS societes_cibles,
                SUM(CASE WHEN rso.rso_termine_le IS NULL OR rso.rso_termine_le >= CURDATE() THEN 1 ELSE 0 END) AS relations_actives,
                SUM(CASE WHEN rso.rso_termine_le IS NOT NULL AND rso.rso_termine_le < CURDATE() THEN 1 ELSE 0 END) AS relations_terminees
            FROM sav_relations_societes rso
            WHERE {$clause}", $params) ?? [];

        $types = $this->fetchAll("SELECT tre.tre_id, tre.tre_code, tre.tre_nom, COUNT(rso.rso_id) AS total
            FROM sav_types_relations_societes tre
            LEFT JOIN sav_relations_societes rso
              ON rso.rso_type_relation_societe_id = tre.tre_id
             AND rso.rso_supprime_le IS NULL
             AND rso.rso_archive_le IS NULL" . ($societeId ? ' AND (rso.rso_societe_source_id = :type_societe_id OR rso.rso_societe_cible_id = :type_societe_id)' : '') . "
            WHERE tre.tre_supprime_le IS NULL AND tre.tre_archive_le IS NULL
            GROUP BY tre.tre_id, tre.tre_code, tre.tre_nom
            ORDER BY tre.tre_nom", $societeId ? ['type_societe_id' => $societeId] : []);

        $representations = $this->fetchOne("SELECT COUNT(*) AS total,
                COUNT(DISTINCT rma_concession_societe_id) AS concessions,
                COUNT(DISTINCT rma_marque_societe_id) AS marques
            FROM sav_representations_marques_societes
            WHERE rma_supprime_le IS NULL AND rma_archive_le IS NULL" . ($societeId ? ' AND (rma_concession_societe_id = :rma_societe_id OR rma_marque_societe_id = :rma_societe_id OR rma_importateur_societe_id = :rma_societe_id OR rma_constructeur_societe_id = :rma_societe_id)' : ''), $societeId ? ['rma_societe_id' => $societeId] : []) ?? [];

        return [
            'relations_total' => (int)($row['relations_total'] ?? 0),
            'relations_actives' => (int)($row['relations_actives'] ?? 0),
            'relations_terminees' => (int)($row['relations_terminees'] ?? 0),
            'societes_sources' => (int)($row['societes_sources'] ?? 0),
            'societes_cibles' => (int)($row['societes_cibles'] ?? 0),
            'types' => $types,
            'representations_total' => (int)($representations['total'] ?? 0),
            'representations_concessions' => (int)($representations['concessions'] ?? 0),
            'representations_marques' => (int)($representations['marques'] ?? 0),
        ];
    }

    public function listerRelations(array $filters = []): array
    {
        $where = ['rso.rso_supprime_le IS NULL', 'rso.rso_archive_le IS NULL'];
        $params = [];
        if (!empty($filters['societe_id'])) {
            $where[] = '(rso.rso_societe_source_id = :societe_id OR rso.rso_societe_cible_id = :societe_id)';
            $params['societe_id'] = (int)$filters['societe_id'];
        }
        if (!empty($filters['source_id'])) {
            $where[] = 'rso.rso_societe_source_id = :source_id';
            $params['source_id'] = (int)$filters['source_id'];
        }
        if (!empty($filters['target_id'])) {
            $where[] = 'rso.rso_societe_cible_id = :target_id';
            $params['target_id'] = (int)$filters['target_id'];
        }
        if (!empty($filters['type_id'])) {
            $where[] = 'rso.rso_type_relation_societe_id = :type_id';
            $params['type_id'] = (int)$filters['type_id'];
        }
        if (!empty($filters['statut_id'])) {
            $where[] = 'rso.rso_statut_id = :statut_id';
            $params['statut_id'] = (int)$filters['statut_id'];
        }
        if (($filters['periode'] ?? '') === 'active') {
            $where[] = '(rso.rso_termine_le IS NULL OR rso.rso_termine_le >= CURDATE())';
        } elseif (($filters['periode'] ?? '') === 'terminee') {
            $where[] = 'rso.rso_termine_le IS NOT NULL AND rso.rso_termine_le < CURDATE()';
        }
        if (!empty($filters['q'])) {
            $where[] = '(src.soc_nom LIKE :q OR cible.soc_nom LIKE :q OR tre.tre_nom LIKE :q OR tre.tre_code LIKE :q)';
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }
        $sql = "SELECT rso.*, src.soc_nom AS societe_source_nom, cible.soc_nom AS societe_cible_nom,
                   tre.tre_code, tre.tre_nom AS type_relation_nom, tre.tre_est_directionnel,
                   sta.sta_libelle AS statut_nom, createur.soc_nom AS cree_par_societe_nom
            FROM sav_relations_societes rso
            INNER JOIN sav_societes src ON src.soc_id = rso.rso_societe_source_id
            INNER JOIN sav_societes cible ON cible.soc_id = rso.rso_societe_cible_id
            INNER JOIN sav_types_relations_societes tre ON tre.tre_id = rso.rso_type_relation_societe_id
            LEFT JOIN sav_societes createur ON createur.soc_id = rso.rso_cree_par_societe_id
            LEFT JOIN sav_statuts sta ON sta.sta_id = rso.rso_statut_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY src.soc_nom, tre.tre_nom, cible.soc_nom, rso.rso_debute_le DESC";
        return $this->fetchAll($sql, $params);
    }

    public function trouverRelation(int $id): ?array
    {
        return $this->fetchOne("SELECT rso.*, src.soc_nom AS societe_source_nom, cible.soc_nom AS societe_cible_nom, tre.tre_nom AS type_relation_nom
            FROM sav_relations_societes rso
            INNER JOIN sav_societes src ON src.soc_id = rso.rso_societe_source_id
            INNER JOIN sav_societes cible ON cible.soc_id = rso.rso_societe_cible_id
            INNER JOIN sav_types_relations_societes tre ON tre.tre_id = rso.rso_type_relation_societe_id
            WHERE rso.rso_id = :id AND rso.rso_supprime_le IS NULL
            LIMIT 1", ['id' => $id]);
    }

    public function enregistrerRelation(array $data, ?int $id, ?int $userId): int
    {
        $source = (int)($data['rso_societe_source_id'] ?? 0);
        $cible = (int)($data['rso_societe_cible_id'] ?? 0);
        if ($source <= 0 || $cible <= 0) {
            throw new \InvalidArgumentException('La société source et la société cible sont obligatoires.');
        }
        if ($source === $cible) {
            throw new \InvalidArgumentException('Une société ne peut pas être reliée à elle-même dans cette relation.');
        }
        $payload = [
            'rso_societe_source_id' => $source,
            'rso_societe_cible_id' => $cible,
            'rso_type_relation_societe_id' => (int)($data['rso_type_relation_societe_id'] ?? 0),
            'rso_debute_le' => (string)($data['rso_debute_le'] ?? date('Y-m-d')),
            'rso_termine_le' => !empty($data['rso_termine_le']) ? (string)$data['rso_termine_le'] : null,
            'rso_statut_id' => !empty($data['rso_statut_id']) ? (int)$data['rso_statut_id'] : null,
            'rso_cree_par_societe_id' => !empty($data['rso_cree_par_societe_id']) ? (int)$data['rso_cree_par_societe_id'] : $source,
        ];
        if ($payload['rso_type_relation_societe_id'] <= 0) {
            throw new \InvalidArgumentException('Le type de relation est obligatoire.');
        }
        if ($id) {
            $payload['rso_modifie_par_utilisateur_id'] = $userId;
            $sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($payload)));
            $payload['id'] = $id;
            $this->query("UPDATE sav_relations_societes SET {$sets}, rso_modifie_le = NOW() WHERE rso_id = :id", $payload);
            return $id;
        }
        $payload['rso_cree_par_utilisateur_id'] = $userId;
        return $this->insert($payload);
    }

    public function supprimerRelation(int $id, ?int $userId): void
    {
        $this->query("UPDATE sav_relations_societes
            SET rso_supprime_le = NOW(), rso_supprime_par_utilisateur_id = :user_id
            WHERE rso_id = :id", ['id' => $id, 'user_id' => $userId]);
    }

    public function listerTypes(array $filters = []): array
    {
        $where = ['tre.tre_supprime_le IS NULL', 'tre.tre_archive_le IS NULL'];
        $params = [];
        if (!empty($filters['q'])) {
            $where[] = '(tre.tre_code LIKE :q OR tre.tre_nom LIKE :q OR tre.tre_description LIKE :q)';
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }
        return $this->fetchAll("SELECT tre.*, sta.sta_libelle AS statut_nom, COUNT(rso.rso_id) AS relations_total
            FROM sav_types_relations_societes tre
            LEFT JOIN sav_statuts sta ON sta.sta_id = tre.tre_statut_id
            LEFT JOIN sav_relations_societes rso ON rso.rso_type_relation_societe_id = tre.tre_id AND rso.rso_supprime_le IS NULL
            WHERE " . implode(' AND ', $where) . "
            GROUP BY tre.tre_id
            ORDER BY tre.tre_nom", $params);
    }

    public function trouverType(int $id): ?array
    {
        return $this->fetchOne("SELECT * FROM sav_types_relations_societes WHERE tre_id = :id AND tre_supprime_le IS NULL LIMIT 1", ['id' => $id]);
    }

    public function enregistrerType(array $data, ?int $id, ?int $userId): int
    {
        $code = trim((string)($data['tre_code'] ?? ''));
        $nom = trim((string)($data['tre_nom'] ?? ''));
        if ($code === '' || $nom === '') {
            throw new \InvalidArgumentException('Le code et le nom du type de relation sont obligatoires.');
        }
        $payload = [
            'tre_code' => $code,
            'tre_nom' => $nom,
            'tre_description' => trim((string)($data['tre_description'] ?? '')) ?: null,
            'tre_est_directionnel' => !empty($data['tre_est_directionnel']) ? 1 : 0,
            'tre_statut_id' => !empty($data['tre_statut_id']) ? (int)$data['tre_statut_id'] : null,
        ];
        if ($id) {
            $payload['tre_modifie_par_utilisateur_id'] = $userId;
            $sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($payload)));
            $payload['id'] = $id;
            $this->query("UPDATE sav_types_relations_societes SET {$sets}, tre_modifie_le = NOW() WHERE tre_id = :id", $payload);
            return $id;
        }
        $payload['tre_cree_par_utilisateur_id'] = $userId;
        $this->query("INSERT INTO sav_types_relations_societes (tre_code, tre_nom, tre_description, tre_est_directionnel, tre_statut_id, tre_cree_par_utilisateur_id)
            VALUES (:tre_code, :tre_nom, :tre_description, :tre_est_directionnel, :tre_statut_id, :tre_cree_par_utilisateur_id)", $payload);
        return (int)$this->pdo->lastInsertId();
    }

    public function supprimerType(int $id, ?int $userId): void
    {
        $this->query("UPDATE sav_types_relations_societes
            SET tre_supprime_le = NOW(), tre_supprime_par_utilisateur_id = :user_id
            WHERE tre_id = :id", ['id' => $id, 'user_id' => $userId]);
    }

    public function listerRepresentations(array $filters = []): array
    {
        $where = ['rma.rma_supprime_le IS NULL', 'rma.rma_archive_le IS NULL'];
        $params = [];
        foreach (['concession' => 'rma_concession_societe_id', 'marque' => 'rma_marque_societe_id', 'importateur' => 'rma_importateur_societe_id', 'constructeur' => 'rma_constructeur_societe_id'] as $key => $col) {
            if (!empty($filters[$key . '_id'])) {
                $where[] = "rma.{$col} = :{$key}_id";
                $params[$key . '_id'] = (int)$filters[$key . '_id'];
            }
        }
        if (!empty($filters['societe_id'])) {
            $where[] = '(rma.rma_concession_societe_id = :societe_id OR rma.rma_marque_societe_id = :societe_id OR rma.rma_importateur_societe_id = :societe_id OR rma.rma_constructeur_societe_id = :societe_id)';
            $params['societe_id'] = (int)$filters['societe_id'];
        }
        return $this->fetchAll("SELECT rma.*, concession.soc_nom AS concession_nom, marque.soc_nom AS marque_nom,
                   importateur.soc_nom AS importateur_nom, constructeur.soc_nom AS constructeur_nom, sta.sta_libelle AS statut_nom
            FROM sav_representations_marques_societes rma
            INNER JOIN sav_societes concession ON concession.soc_id = rma.rma_concession_societe_id
            INNER JOIN sav_societes marque ON marque.soc_id = rma.rma_marque_societe_id
            LEFT JOIN sav_societes importateur ON importateur.soc_id = rma.rma_importateur_societe_id
            LEFT JOIN sav_societes constructeur ON constructeur.soc_id = rma.rma_constructeur_societe_id
            LEFT JOIN sav_statuts sta ON sta.sta_id = rma.rma_statut_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY concession.soc_nom, marque.soc_nom, rma.rma_debute_le DESC", $params);
    }

    public function trouverRepresentation(int $id): ?array
    {
        return $this->fetchOne("SELECT * FROM sav_representations_marques_societes WHERE rma_id = :id AND rma_supprime_le IS NULL LIMIT 1", ['id' => $id]);
    }

    public function enregistrerRepresentation(array $data, ?int $id, ?int $userId): int
    {
        $payload = [
            'rma_concession_societe_id' => (int)($data['rma_concession_societe_id'] ?? 0),
            'rma_marque_societe_id' => (int)($data['rma_marque_societe_id'] ?? 0),
            'rma_importateur_societe_id' => !empty($data['rma_importateur_societe_id']) ? (int)$data['rma_importateur_societe_id'] : null,
            'rma_constructeur_societe_id' => !empty($data['rma_constructeur_societe_id']) ? (int)$data['rma_constructeur_societe_id'] : null,
            'rma_debute_le' => (string)($data['rma_debute_le'] ?? date('Y-m-d')),
            'rma_termine_le' => !empty($data['rma_termine_le']) ? (string)$data['rma_termine_le'] : null,
            'rma_statut_id' => !empty($data['rma_statut_id']) ? (int)$data['rma_statut_id'] : null,
        ];
        if ($payload['rma_concession_societe_id'] <= 0 || $payload['rma_marque_societe_id'] <= 0) {
            throw new \InvalidArgumentException('La concession et la marque sont obligatoires.');
        }
        if ($id) {
            $payload['rma_modifie_par_utilisateur_id'] = $userId;
            $sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($payload)));
            $payload['id'] = $id;
            $this->query("UPDATE sav_representations_marques_societes SET {$sets}, rma_modifie_le = NOW() WHERE rma_id = :id", $payload);
            return $id;
        }
        $payload['rma_cree_par_utilisateur_id'] = $userId;
        $cols = implode(', ', array_keys($payload));
        $placeholders = ':' . implode(', :', array_keys($payload));
        $this->query("INSERT INTO sav_representations_marques_societes ({$cols}) VALUES ({$placeholders})", $payload);
        return (int)$this->pdo->lastInsertId();
    }

    public function supprimerRepresentation(int $id, ?int $userId): void
    {
        $this->query("UPDATE sav_representations_marques_societes
            SET rma_supprime_le = NOW(), rma_supprime_par_utilisateur_id = :user_id
            WHERE rma_id = :id", ['id' => $id, 'user_id' => $userId]);
    }

    public function societes(): array
    {
        return $this->fetchAll("SELECT soc_id, soc_nom, soc_code FROM sav_societes
            WHERE soc_supprime_le IS NULL AND soc_archive_le IS NULL
            ORDER BY soc_nom");
    }

    public function statuts(): array
    {
        return $this->fetchAll("SELECT sta_id, sta_domaine, sta_code, sta_libelle AS sta_nom FROM sav_statuts
            WHERE sta_supprime_le IS NULL
            ORDER BY sta_domaine, sta_libelle");
    }

    public function audit(string $action, string $table, int $targetId, ?int $userId, ?string $ip, array $metadata = []): void
    {
        $this->query("INSERT INTO sav_journaux_audit
            (jau_utilisateur_id, jau_action, jau_table_cible, jau_id_cible, jau_adresse_ip, jau_metadata_json, jau_cree_le)
            VALUES (:user_id, :action, :table_cible, :id_cible, INET6_ATON(:ip), :metadata, NOW())", [
                'user_id' => $userId,
                'action' => $action,
                'table_cible' => $table,
                'id_cible' => $targetId,
                'ip' => $ip ?: '127.0.0.1',
                'metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ]);
    }
}
