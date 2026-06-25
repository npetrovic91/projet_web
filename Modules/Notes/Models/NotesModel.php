<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notes\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Notes / etiquettes / affectations generiques.
 *
 * Tables SQL reelles utilisees :
 * - sav_notes
 * - sav_etiquettes
 * - sav_affectations_etiquettes
 * - sav_societes
 * - sav_utilisateurs
 * - sav_profils_utilisateurs
 * - sav_statuts
 * - sav_journaux_audit
 */
class NotesModel extends BaseModel
{
    protected string $table = 'sav_notes';
    protected string $colPrefix = 'nte_';

    public function stats(): array
    {
        return [
            'notes_total' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_notes WHERE nte_supprime_le IS NULL"),
            'notes_privees' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_notes WHERE nte_supprime_le IS NULL AND nte_est_privee = 1"),
            'etiquettes_total' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_etiquettes WHERE eti_supprime_le IS NULL AND eti_archive_le IS NULL"),
            'affectations_total' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_affectations_etiquettes WHERE afe_supprime_le IS NULL"),
        ];
    }

    public function notes(array $filters = [], int $limit = 250): array
    {
        $where = ['n.nte_supprime_le IS NULL'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(n.nte_titre LIKE :q OR n.nte_contenu LIKE :q OR n.nte_cible_type LIKE :q OR soc.soc_nom LIKE :q OR u.use_email LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        if (!empty($filters['societe_id'])) {
            $where[] = 'n.nte_societe_id = :societe_id';
            $params['societe_id'] = (int) $filters['societe_id'];
        }
        if (!empty($filters['cible_type'])) {
            $where[] = 'n.nte_cible_type = :cible_type';
            $params['cible_type'] = trim((string) $filters['cible_type']);
        }
        if (!empty($filters['cible_id'])) {
            $where[] = 'n.nte_cible_id = :cible_id';
            $params['cible_id'] = (int) $filters['cible_id'];
        }
        if (($filters['visibilite'] ?? '') === 'privee') {
            $where[] = 'n.nte_est_privee = 1';
        } elseif (($filters['visibilite'] ?? '') === 'publique') {
            $where[] = 'n.nte_est_privee = 0';
        }
        if (!empty($filters['etiquette_id'])) {
            $where[] = 'EXISTS (SELECT 1 FROM sav_affectations_etiquettes af WHERE af.afe_cible_type = CONCAT(\'note:\', n.nte_cible_type) AND af.afe_cible_id = n.nte_id AND af.afe_etiquette_id = :etiquette_id AND af.afe_supprime_le IS NULL)';
            $params['etiquette_id'] = (int) $filters['etiquette_id'];
        }

        $params['limit'] = max(1, min(500, $limit));

        return $this->db->fetchAll(
            "SELECT n.*,
                    soc.soc_nom AS societe_nom,
                    u.use_email AS auteur_email,
                    CONCAT(COALESCE(p.pui_prenom, ''), ' ', COALESCE(p.pui_nom, '')) AS auteur_nom,
                    st.sta_code AS statut_code,
                    st.sta_libelle AS statut_libelle,
                    GROUP_CONCAT(DISTINCT eti.eti_libelle ORDER BY eti.eti_libelle SEPARATOR ', ') AS etiquettes_libelles,
                    GROUP_CONCAT(DISTINCT eti.eti_code ORDER BY eti.eti_code SEPARATOR ',') AS etiquettes_codes
             FROM sav_notes n
             LEFT JOIN sav_societes soc ON soc.soc_id = n.nte_societe_id
             LEFT JOIN sav_utilisateurs u ON u.use_id = n.nte_cree_par_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = n.nte_cree_par_utilisateur_id AND p.pui_supprime_le IS NULL
             LEFT JOIN sav_statuts st ON st.sta_id = n.nte_statut_id
             LEFT JOIN sav_affectations_etiquettes afe
                ON afe.afe_cible_type = CONCAT('note:', n.nte_cible_type)
               AND afe.afe_cible_id = n.nte_id
               AND afe.afe_supprime_le IS NULL
             LEFT JOIN sav_etiquettes eti
                ON eti.eti_id = afe.afe_etiquette_id
               AND eti.eti_supprime_le IS NULL
               AND eti.eti_archive_le IS NULL
             WHERE " . implode(' AND ', $where) . "
             GROUP BY n.nte_id
             ORDER BY soc.soc_nom ASC, n.nte_cible_type ASC, n.nte_cible_id ASC, n.nte_cree_le DESC
             LIMIT :limit",
            $params
        );
    }

    public function findNote(int $id): ?array
    {
        $note = $this->db->fetch(
            "SELECT n.*, soc.soc_nom AS societe_nom,
                    u.use_email AS auteur_email,
                    CONCAT(COALESCE(p.pui_prenom, ''), ' ', COALESCE(p.pui_nom, '')) AS auteur_nom
             FROM sav_notes n
             LEFT JOIN sav_societes soc ON soc.soc_id = n.nte_societe_id
             LEFT JOIN sav_utilisateurs u ON u.use_id = n.nte_cree_par_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = n.nte_cree_par_utilisateur_id AND p.pui_supprime_le IS NULL
             WHERE n.nte_id = :id AND n.nte_supprime_le IS NULL
             LIMIT 1",
            ['id' => $id]
        );
        if ($note) {
            $note['etiquettes'] = $this->etiquettesForTarget('note:' . $note['nte_cible_type'], (int) $note['nte_id']);
        }
        return $note;
    }

    public function createNote(array $data, int $userId): int
    {
        $cibleType = $this->targetType($data['nte_cible_type'] ?? $data['cible_type'] ?? 'general');
        $cibleId = max(0, (int) ($data['nte_cible_id'] ?? $data['cible_id'] ?? 0));
        $contenu = trim((string) ($data['nte_contenu'] ?? $data['contenu'] ?? ''));
        if ($contenu === '') {
            throw new \InvalidArgumentException('Le contenu de la note est obligatoire.');
        }

        $this->db->execute(
            "INSERT INTO sav_notes
                (nte_uuid, nte_societe_id, nte_cible_type, nte_cible_id, nte_titre, nte_contenu,
                 nte_est_privee, nte_statut_id, nte_cree_par_utilisateur_id, nte_modifie_par_utilisateur_id)
             VALUES
                (:uuid, :societe_id, :cible_type, :cible_id, :titre, :contenu,
                 :est_privee, :statut_id, :user_id, :user_id)",
            [
                'uuid' => $this->uuid(),
                'societe_id' => $this->nullableInt($data['nte_societe_id'] ?? $data['societe_id'] ?? null),
                'cible_type' => $cibleType,
                'cible_id' => $cibleId,
                'titre' => $this->nullOrString($data['nte_titre'] ?? $data['titre'] ?? null, 255),
                'contenu' => $contenu,
                'est_privee' => !empty($data['nte_est_privee'] ?? $data['est_privee'] ?? false) ? 1 : 0,
                'statut_id' => $this->nullableInt($data['nte_statut_id'] ?? $data['statut_id'] ?? null) ?? $this->statusId('general', 'actif'),
                'user_id' => $userId,
            ]
        );
        $id = (int) $this->db->lastInsertId();
        $this->syncEtiquettesForTarget('note:' . $cibleType, $id, $this->idsFromPost($data['etiquettes'] ?? $data['etiquette_ids'] ?? []), $userId);
        $this->audit($userId, 'note.creer', 'sav_notes', $id, 'Création note générique');
        return $id;
    }

    public function updateNote(int $id, array $data, int $userId): bool
    {
        $note = $this->findNote($id);
        if (!$note) {
            throw new \InvalidArgumentException('Note introuvable.');
        }
        $cibleType = $this->targetType($data['nte_cible_type'] ?? $data['cible_type'] ?? $note['nte_cible_type']);
        $contenu = trim((string) ($data['nte_contenu'] ?? $data['contenu'] ?? $note['nte_contenu']));
        if ($contenu === '') {
            throw new \InvalidArgumentException('Le contenu de la note est obligatoire.');
        }

        $ok = $this->db->execute(
            "UPDATE sav_notes
             SET nte_societe_id = :societe_id,
                 nte_cible_type = :cible_type,
                 nte_cible_id = :cible_id,
                 nte_titre = :titre,
                 nte_contenu = :contenu,
                 nte_est_privee = :est_privee,
                 nte_statut_id = COALESCE(:statut_id, nte_statut_id),
                 nte_modifie_par_utilisateur_id = :user_id
             WHERE nte_id = :id AND nte_supprime_le IS NULL",
            [
                'id' => $id,
                'societe_id' => $this->nullableInt($data['nte_societe_id'] ?? $data['societe_id'] ?? $note['nte_societe_id'] ?? null),
                'cible_type' => $cibleType,
                'cible_id' => max(0, (int) ($data['nte_cible_id'] ?? $data['cible_id'] ?? $note['nte_cible_id'] ?? 0)),
                'titre' => $this->nullOrString($data['nte_titre'] ?? $data['titre'] ?? null, 255),
                'contenu' => $contenu,
                'est_privee' => !empty($data['nte_est_privee'] ?? $data['est_privee'] ?? false) ? 1 : 0,
                'statut_id' => $this->nullableInt($data['nte_statut_id'] ?? $data['statut_id'] ?? null),
                'user_id' => $userId,
            ]
        );

        if (array_key_exists('etiquettes', $data) || array_key_exists('etiquette_ids', $data)) {
            $this->syncEtiquettesForTarget('note:' . $cibleType, $id, $this->idsFromPost($data['etiquettes'] ?? $data['etiquette_ids'] ?? []), $userId);
        }
        if ($ok) {
            $this->audit($userId, 'note.modifier', 'sav_notes', $id, 'Modification note générique');
        }
        return $ok;
    }

    public function softDeleteNote(int $id, int $userId): bool
    {
        $ok = $this->db->execute(
            "UPDATE sav_notes
             SET nte_supprime_le = NOW(), nte_supprime_par_utilisateur_id = :user_id
             WHERE nte_id = :id AND nte_supprime_le IS NULL",
            ['id' => $id, 'user_id' => $userId]
        );
        if ($ok) {
            $this->audit($userId, 'note.supprimer', 'sav_notes', $id, 'Suppression logique note générique');
        }
        return $ok;
    }

    public function etiquettes(array $filters = []): array
    {
        $where = ['e.eti_supprime_le IS NULL', 'e.eti_archive_le IS NULL'];
        $params = [];
        if (!empty($filters['q'])) {
            $where[] = '(e.eti_code LIKE :q OR e.eti_libelle LIKE :q OR e.eti_description LIKE :q OR soc.soc_nom LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        if (!empty($filters['societe_id'])) {
            $where[] = '(e.eti_societe_id = :societe_id OR e.eti_societe_id IS NULL)';
            $params['societe_id'] = (int) $filters['societe_id'];
        }
        return $this->db->fetchAll(
            "SELECT e.*, soc.soc_nom AS societe_nom, st.sta_code AS statut_code,
                    COUNT(afe.afe_id) AS affectations_count
             FROM sav_etiquettes e
             LEFT JOIN sav_societes soc ON soc.soc_id = e.eti_societe_id
             LEFT JOIN sav_statuts st ON st.sta_id = e.eti_statut_id
             LEFT JOIN sav_affectations_etiquettes afe ON afe.afe_etiquette_id = e.eti_id AND afe.afe_supprime_le IS NULL
             WHERE " . implode(' AND ', $where) . "
             GROUP BY e.eti_id
             ORDER BY soc.soc_nom ASC, e.eti_libelle ASC",
            $params
        );
    }

    public function findEtiquette(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT e.*, soc.soc_nom AS societe_nom
             FROM sav_etiquettes e
             LEFT JOIN sav_societes soc ON soc.soc_id = e.eti_societe_id
             WHERE e.eti_id = :id AND e.eti_supprime_le IS NULL AND e.eti_archive_le IS NULL
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function createEtiquette(array $data, int $userId): int
    {
        $code = $this->code($data['eti_code'] ?? $data['code'] ?? '');
        $libelle = trim((string) ($data['eti_libelle'] ?? $data['libelle'] ?? ''));
        if ($code === '' || $libelle === '') {
            throw new \InvalidArgumentException('Le code et le libellé de l’étiquette sont obligatoires.');
        }
        $this->db->execute(
            "INSERT INTO sav_etiquettes
                (eti_societe_id, eti_code, eti_libelle, eti_couleur, eti_description, eti_statut_id,
                 eti_cree_par_utilisateur_id, eti_modifie_par_utilisateur_id)
             VALUES
                (:societe_id, :code, :libelle, :couleur, :description, :statut_id,
                 :user_id, :user_id)",
            [
                'societe_id' => $this->nullableInt($data['eti_societe_id'] ?? $data['societe_id'] ?? null),
                'code' => $code,
                'libelle' => $libelle,
                'couleur' => $this->nullOrString($data['eti_couleur'] ?? $data['couleur'] ?? null, 20),
                'description' => $this->nullOrString($data['eti_description'] ?? $data['description'] ?? null, 5000),
                'statut_id' => $this->nullableInt($data['eti_statut_id'] ?? $data['statut_id'] ?? null) ?? $this->statusId('general', 'actif'),
                'user_id' => $userId,
            ]
        );
        $id = (int) $this->db->lastInsertId();
        $this->audit($userId, 'etiquette.creer', 'sav_etiquettes', $id, 'Création étiquette');
        return $id;
    }

    public function updateEtiquette(int $id, array $data, int $userId): bool
    {
        $code = $this->code($data['eti_code'] ?? $data['code'] ?? '');
        $libelle = trim((string) ($data['eti_libelle'] ?? $data['libelle'] ?? ''));
        if ($code === '' || $libelle === '') {
            throw new \InvalidArgumentException('Le code et le libellé de l’étiquette sont obligatoires.');
        }
        $ok = $this->db->execute(
            "UPDATE sav_etiquettes
             SET eti_societe_id = :societe_id,
                 eti_code = :code,
                 eti_libelle = :libelle,
                 eti_couleur = :couleur,
                 eti_description = :description,
                 eti_statut_id = COALESCE(:statut_id, eti_statut_id),
                 eti_modifie_par_utilisateur_id = :user_id
             WHERE eti_id = :id AND eti_supprime_le IS NULL AND eti_archive_le IS NULL",
            [
                'id' => $id,
                'societe_id' => $this->nullableInt($data['eti_societe_id'] ?? $data['societe_id'] ?? null),
                'code' => $code,
                'libelle' => $libelle,
                'couleur' => $this->nullOrString($data['eti_couleur'] ?? $data['couleur'] ?? null, 20),
                'description' => $this->nullOrString($data['eti_description'] ?? $data['description'] ?? null, 5000),
                'statut_id' => $this->nullableInt($data['eti_statut_id'] ?? $data['statut_id'] ?? null),
                'user_id' => $userId,
            ]
        );
        if ($ok) {
            $this->audit($userId, 'etiquette.modifier', 'sav_etiquettes', $id, 'Modification étiquette');
        }
        return $ok;
    }

    public function softDeleteEtiquette(int $id, int $userId): bool
    {
        $ok = $this->db->execute(
            "UPDATE sav_etiquettes
             SET eti_supprime_le = NOW(), eti_supprime_par_utilisateur_id = :user_id
             WHERE eti_id = :id AND eti_supprime_le IS NULL",
            ['id' => $id, 'user_id' => $userId]
        );
        if ($ok) {
            $this->db->execute(
                "UPDATE sav_affectations_etiquettes
                 SET afe_supprime_le = NOW(), afe_supprime_par_utilisateur_id = :user_id
                 WHERE afe_etiquette_id = :id AND afe_supprime_le IS NULL",
                ['id' => $id, 'user_id' => $userId]
            );
            $this->audit($userId, 'etiquette.supprimer', 'sav_etiquettes', $id, 'Suppression logique étiquette');
        }
        return $ok;
    }

    public function affectations(array $filters = [], int $limit = 300): array
    {
        $where = ['a.afe_supprime_le IS NULL', 'e.eti_supprime_le IS NULL', 'e.eti_archive_le IS NULL'];
        $params = [];
        if (!empty($filters['etiquette_id'])) {
            $where[] = 'a.afe_etiquette_id = :etiquette_id';
            $params['etiquette_id'] = (int) $filters['etiquette_id'];
        }
        if (!empty($filters['cible_type'])) {
            $where[] = 'a.afe_cible_type = :cible_type';
            $params['cible_type'] = trim((string) $filters['cible_type']);
        }
        if (!empty($filters['cible_id'])) {
            $where[] = 'a.afe_cible_id = :cible_id';
            $params['cible_id'] = (int) $filters['cible_id'];
        }
        $params['limit'] = max(1, min(500, $limit));
        return $this->db->fetchAll(
            "SELECT a.*, e.eti_code, e.eti_libelle, e.eti_couleur, e.eti_societe_id,
                    soc.soc_nom AS etiquette_societe_nom,
                    u.use_email AS auteur_email,
                    CONCAT(COALESCE(p.pui_prenom, ''), ' ', COALESCE(p.pui_nom, '')) AS auteur_nom
             FROM sav_affectations_etiquettes a
             INNER JOIN sav_etiquettes e ON e.eti_id = a.afe_etiquette_id
             LEFT JOIN sav_societes soc ON soc.soc_id = e.eti_societe_id
             LEFT JOIN sav_utilisateurs u ON u.use_id = a.afe_cree_par_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = a.afe_cree_par_utilisateur_id AND p.pui_supprime_le IS NULL
             WHERE " . implode(' AND ', $where) . "
             ORDER BY a.afe_cible_type ASC, a.afe_cible_id ASC, e.eti_libelle ASC
             LIMIT :limit",
            $params
        );
    }

    public function attachEtiquette(int $etiquetteId, string $cibleType, int $cibleId, int $userId): bool
    {
        $cibleType = $this->targetType($cibleType);
        $exists = $this->db->fetchColumn(
            "SELECT afe_id FROM sav_affectations_etiquettes
             WHERE afe_etiquette_id = :etiquette_id AND afe_cible_type = :cible_type AND afe_cible_id = :cible_id AND afe_supprime_le IS NULL
             LIMIT 1",
            ['etiquette_id' => $etiquetteId, 'cible_type' => $cibleType, 'cible_id' => $cibleId]
        );
        if ($exists) {
            return true;
        }
        $ok = $this->db->execute(
            "INSERT INTO sav_affectations_etiquettes
                (afe_etiquette_id, afe_cible_type, afe_cible_id, afe_cree_par_utilisateur_id)
             VALUES (:etiquette_id, :cible_type, :cible_id, :user_id)",
            ['etiquette_id' => $etiquetteId, 'cible_type' => $cibleType, 'cible_id' => $cibleId, 'user_id' => $userId]
        );
        if ($ok) {
            $id = (int) $this->db->lastInsertId();
            $this->audit($userId, 'etiquette.affecter', 'sav_affectations_etiquettes', $id, $cibleType . '#' . $cibleId);
        }
        return $ok;
    }

    public function detachAffectation(int $id, int $userId): bool
    {
        $ok = $this->db->execute(
            "UPDATE sav_affectations_etiquettes
             SET afe_supprime_le = NOW(), afe_supprime_par_utilisateur_id = :user_id
             WHERE afe_id = :id AND afe_supprime_le IS NULL",
            ['id' => $id, 'user_id' => $userId]
        );
        if ($ok) {
            $this->audit($userId, 'etiquette.detacher', 'sav_affectations_etiquettes', $id, 'Retrait affectation étiquette');
        }
        return $ok;
    }

    public function etiquettesForTarget(string $cibleType, int $cibleId): array
    {
        return $this->db->fetchAll(
            "SELECT a.afe_id, e.*
             FROM sav_affectations_etiquettes a
             INNER JOIN sav_etiquettes e ON e.eti_id = a.afe_etiquette_id
             WHERE a.afe_cible_type = :cible_type
               AND a.afe_cible_id = :cible_id
               AND a.afe_supprime_le IS NULL
               AND e.eti_supprime_le IS NULL
               AND e.eti_archive_le IS NULL
             ORDER BY e.eti_libelle ASC",
            ['cible_type' => $this->targetType($cibleType), 'cible_id' => $cibleId]
        );
    }

    public function refs(): array
    {
        return [
            'societes' => $this->db->fetchAll("SELECT soc_id, soc_nom FROM sav_societes WHERE soc_supprime_le IS NULL AND soc_archive_le IS NULL ORDER BY soc_nom ASC"),
            'statuts' => $this->db->fetchAll("SELECT sta_id, sta_domaine, sta_code, sta_libelle FROM sav_statuts WHERE sta_supprime_le IS NULL AND sta_est_actif = 1 ORDER BY sta_domaine ASC, sta_ordre ASC, sta_libelle ASC"),
            'etiquettes' => $this->etiquettes(),
            'cible_types' => $this->knownTargetTypes(),
        ];
    }

    public function export(): array
    {
        return [
            'exported_at' => date('c'),
            'stats' => $this->stats(),
            'notes' => $this->notes([], 500),
            'etiquettes' => $this->etiquettes(),
            'affectations' => $this->affectations([], 500),
        ];
    }

    private function syncEtiquettesForTarget(string $cibleType, int $cibleId, array $ids, int $userId): void
    {
        $cibleType = $this->targetType($cibleType);
        $current = $this->db->fetchAll(
            "SELECT afe_id, afe_etiquette_id FROM sav_affectations_etiquettes
             WHERE afe_cible_type = :cible_type AND afe_cible_id = :cible_id AND afe_supprime_le IS NULL",
            ['cible_type' => $cibleType, 'cible_id' => $cibleId]
        );
        $currentById = [];
        foreach ($current as $row) {
            $currentById[(int) $row['afe_etiquette_id']] = (int) $row['afe_id'];
        }
        foreach ($currentById as $etiquetteId => $afeId) {
            if (!in_array($etiquetteId, $ids, true)) {
                $this->detachAffectation($afeId, $userId);
            }
        }
        foreach ($ids as $id) {
            if (!isset($currentById[$id])) {
                $this->attachEtiquette($id, $cibleType, $cibleId, $userId);
            }
        }
    }

    private function idsFromPost(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[,;\s]+/', $value) ?: [];
        }
        if (!is_array($value)) {
            return [];
        }
        $ids = [];
        foreach ($value as $item) {
            $id = (int) $item;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function statusId(string $domain, string $code): ?int
    {
        $id = $this->db->fetchColumn(
            "SELECT sta_id FROM sav_statuts WHERE sta_domaine = :domain AND sta_code = :code AND sta_supprime_le IS NULL LIMIT 1",
            ['domain' => $domain, 'code' => $code]
        );
        return $id !== false && $id !== null ? (int) $id : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $int = (int) $value;
        return $int > 0 ? $int : null;
    }

    private function nullOrString(mixed $value, int $max): ?string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return null;
        }
        return mb_substr($text, 0, $max);
    }

    private function targetType(mixed $value): string
    {
        $text = trim((string) $value);
        $text = preg_replace('/[^a-zA-Z0-9_:\\.-]+/', '_', $text) ?: 'general';
        return mb_substr($text, 0, 120);
    }

    private function code(mixed $value): string
    {
        $code = strtolower(trim((string) $value));
        $code = preg_replace('/[^a-z0-9_.-]+/', '_', $code) ?: '';
        return mb_substr($code, 0, 80);
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function knownTargetTypes(): array
    {
        return [
            'general' => 'Général',
            'societe' => 'Société',
            'utilisateur' => 'Utilisateur',
            'module' => 'Module',
            'fichier' => 'Fichier',
            'document_juridique' => 'Document juridique',
            'validation' => 'Demande validation',
            'standard' => 'Standard',
            'notification' => 'Notification',
            'connecteur' => 'Connecteur',
        ];
    }

    private function audit(int $userId, string $action, string $table, int $targetId, ?string $reason = null): void
    {
        $this->db->execute(
            "INSERT INTO sav_journaux_audit
                (jau_utilisateur_id, jau_societe_id, jau_action, jau_table_cible, jau_id_cible, jau_raison, jau_metadata_json)
             VALUES (:user_id, :societe_id, :action, :table_cible, :id_cible, :raison, :metadata)",
            [
                'user_id' => $userId ?: null,
                'societe_id' => $_SESSION['active_company_id'] ?? null,
                'action' => $action,
                'table_cible' => $table,
                'id_cible' => $targetId,
                'raison' => $reason,
                'metadata' => json_encode(['module' => 'Notes'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );
    }
}
