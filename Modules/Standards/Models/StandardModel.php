<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Standards\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Modele Standards aligné sur le schéma SQL actuel.
 * Tables sources : sav_standards, sav_versions_standards,
 * sav_exigences_versions_standards.
 */
class StandardModel extends BaseModel
{
    protected string $table = 'sav_standards';
    protected string $colPrefix = 'std_';

    public function stats(): array
    {
        return [
            'standards' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_standards WHERE std_supprime_le IS NULL AND std_archive_le IS NULL"),
            'versions_actives' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_versions_standards WHERE vst_supprime_le IS NULL AND vst_archive_le IS NULL AND (vst_valide_au IS NULL OR vst_valide_au >= CURDATE())"),
            'exigences' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_exigences_versions_standards WHERE evs_supprime_le IS NULL"),
            'exigences_obligatoires' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_exigences_versions_standards WHERE evs_supprime_le IS NULL AND evs_est_obligatoire = 1"),
        ];
    }

    public function standards(array $filters = []): array
    {
        $where = ['s.std_supprime_le IS NULL', 's.std_archive_le IS NULL'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(s.std_code LIKE :q OR s.std_nom LIKE :q OR s.std_type_standard LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        if (!empty($filters['type'])) {
            $where[] = 's.std_type_standard = :type';
            $params['type'] = trim((string) $filters['type']);
        }
        if (!empty($filters['societe_id'])) {
            $where[] = 's.std_societe_proprietaire_id = :societe_id';
            $params['societe_id'] = (int) $filters['societe_id'];
        }

        return $this->db->fetchAll(
            "SELECT s.*, soc.soc_nom AS societe_proprietaire_nom,
                    st.sta_code AS statut_code, st.sta_libelle AS statut_libelle,
                    COUNT(DISTINCT v.vst_id) AS total_versions,
                    MAX(v.vst_valide_du) AS derniere_version_valide_du
             FROM sav_standards s
             LEFT JOIN sav_societes soc ON soc.soc_id = s.std_societe_proprietaire_id
             LEFT JOIN sav_statuts st ON st.sta_id = s.std_statut_id
             LEFT JOIN sav_versions_standards v ON v.vst_standard_id = s.std_id
                AND v.vst_supprime_le IS NULL AND v.vst_archive_le IS NULL
             WHERE " . implode(' AND ', $where) . "
             GROUP BY s.std_id
             ORDER BY s.std_type_standard ASC, soc.soc_nom ASC, s.std_nom ASC",
            $params
        );
    }

    public function findStandard(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT s.*, soc.soc_nom AS societe_proprietaire_nom,
                    st.sta_code AS statut_code, st.sta_libelle AS statut_libelle
             FROM sav_standards s
             LEFT JOIN sav_societes soc ON soc.soc_id = s.std_societe_proprietaire_id
             LEFT JOIN sav_statuts st ON st.sta_id = s.std_statut_id
             WHERE s.std_id = :id AND s.std_supprime_le IS NULL AND s.std_archive_le IS NULL
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function createStandard(array $data, ?int $userId): int
    {
        $code = strtoupper(trim((string) ($data['std_code'] ?? $data['code'] ?? '')));
        $nom = trim((string) ($data['std_nom'] ?? $data['nom'] ?? ''));
        $type = trim((string) ($data['std_type_standard'] ?? $data['type_standard'] ?? 'constructeur'));
        if ($code === '' || $nom === '') {
            throw new \InvalidArgumentException('Le code et le nom du standard sont obligatoires.');
        }

        $this->db->execute(
            "INSERT INTO sav_standards
                (std_code, std_nom, std_societe_proprietaire_id, std_type_standard,
                 std_statut_id, std_cree_par_utilisateur_id, std_modifie_par_utilisateur_id)
             VALUES
                (:code, :nom, :societe_id, :type_standard, :statut_id, :user_id, :user_id)",
            [
                'code' => $code,
                'nom' => $nom,
                'societe_id' => $this->nullableInt($data['std_societe_proprietaire_id'] ?? $data['societe_id'] ?? null),
                'type_standard' => $type,
                'statut_id' => $this->statusId('general', 'actif'),
                'user_id' => $userId,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateStandard(int $id, array $data, ?int $userId): bool
    {
        $code = strtoupper(trim((string) ($data['std_code'] ?? $data['code'] ?? '')));
        $nom = trim((string) ($data['std_nom'] ?? $data['nom'] ?? ''));
        if ($code === '' || $nom === '') {
            throw new \InvalidArgumentException('Le code et le nom du standard sont obligatoires.');
        }

        return $this->db->execute(
            "UPDATE sav_standards
             SET std_code = :code,
                 std_nom = :nom,
                 std_societe_proprietaire_id = :societe_id,
                 std_type_standard = :type_standard,
                 std_statut_id = :statut_id,
                 std_modifie_par_utilisateur_id = :user_id
             WHERE std_id = :id AND std_supprime_le IS NULL AND std_archive_le IS NULL",
            [
                'id' => $id,
                'code' => $code,
                'nom' => $nom,
                'societe_id' => $this->nullableInt($data['std_societe_proprietaire_id'] ?? $data['societe_id'] ?? null),
                'type_standard' => trim((string) ($data['std_type_standard'] ?? $data['type_standard'] ?? 'constructeur')),
                'statut_id' => $this->nullableInt($data['std_statut_id'] ?? $data['statut_id'] ?? null) ?? $this->statusId('general', 'actif'),
                'user_id' => $userId,
            ]
        );
    }

    public function softDeleteStandard(int $id, ?int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_standards
             SET std_supprime_le = NOW(), std_supprime_par_utilisateur_id = :user_id
             WHERE std_id = :id AND std_supprime_le IS NULL",
            ['id' => $id, 'user_id' => $userId]
        );
    }

    public function versions(?int $standardId = null): array
    {
        $where = ['v.vst_supprime_le IS NULL', 'v.vst_archive_le IS NULL'];
        $params = [];
        if ($standardId !== null) {
            $where[] = 'v.vst_standard_id = :standard_id';
            $params['standard_id'] = $standardId;
        }

        return $this->db->fetchAll(
            "SELECT v.*, s.std_code, s.std_nom, s.std_type_standard,
                    st.sta_code AS statut_code, st.sta_libelle AS statut_libelle,
                    COUNT(e.evs_id) AS total_exigences,
                    SUM(CASE WHEN e.evs_est_obligatoire = 1 THEN 1 ELSE 0 END) AS total_exigences_obligatoires
             FROM sav_versions_standards v
             INNER JOIN sav_standards s ON s.std_id = v.vst_standard_id
             LEFT JOIN sav_statuts st ON st.sta_id = v.vst_statut_id
             LEFT JOIN sav_exigences_versions_standards e ON e.evs_version_standard_id = v.vst_id AND e.evs_supprime_le IS NULL
             WHERE " . implode(' AND ', $where) . "
             GROUP BY v.vst_id
             ORDER BY s.std_nom ASC, v.vst_valide_du DESC, v.vst_version DESC",
            $params
        );
    }

    public function findVersion(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT v.*, s.std_code, s.std_nom, s.std_type_standard
             FROM sav_versions_standards v
             INNER JOIN sav_standards s ON s.std_id = v.vst_standard_id
             WHERE v.vst_id = :id AND v.vst_supprime_le IS NULL AND v.vst_archive_le IS NULL
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function createVersion(array $data, ?int $userId): int
    {
        $standardId = $this->nullableInt($data['vst_standard_id'] ?? $data['standard_id'] ?? null);
        $version = trim((string) ($data['vst_version'] ?? $data['version'] ?? ''));
        $from = trim((string) ($data['vst_valide_du'] ?? $data['valide_du'] ?? ''));
        if (!$standardId || $version === '' || $from === '') {
            throw new \InvalidArgumentException('Le standard, la version et la date de début sont obligatoires.');
        }
        // ACC-008 : toute nouvelle version démarre en brouillon, jamais
        // directement applicable. Elle doit être soumise puis validée par
        // un niveau supérieur (cf. StandardService::soumettrePourValidation/validerVersion).
        $this->db->execute(
            "INSERT INTO sav_versions_standards
                (vst_standard_id, vst_version, vst_valide_du, vst_valide_au, vst_statut_id, vst_etat_validation,
                 vst_cree_par_utilisateur_id, vst_modifie_par_utilisateur_id)
             VALUES
                (:standard_id, :version, :valide_du, :valide_au, :statut_id, 'draft', :user_id, :user_id)",
            [
                'standard_id' => $standardId,
                'version' => $version,
                'valide_du' => $from,
                'valide_au' => $this->emptyToNull($data['vst_valide_au'] ?? $data['valide_au'] ?? null),
                'statut_id' => $this->statusId('general', 'actif'),
                'user_id' => $userId,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Versions réellement applicables (ACC-008) : validées, jamais en
     * brouillon ni en attente. À utiliser par tout consommateur qui
     * affiche des standards "actifs" à des utilisateurs opérationnels.
     */
    public function versionsApprouvees(?int $standardId = null): array
    {
        $where = ["v.vst_supprime_le IS NULL", "v.vst_archive_le IS NULL", "v.vst_etat_validation = 'approved'"];
        $params = [];
        if ($standardId !== null) {
            $where[] = 'v.vst_standard_id = :standard_id';
            $params['standard_id'] = $standardId;
        }

        return $this->db->fetchAll(
            "SELECT v.*, s.std_code, s.std_nom, s.std_type_standard
             FROM sav_versions_standards v
             INNER JOIN sav_standards s ON s.std_id = v.vst_standard_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY s.std_nom ASC, v.vst_valide_du DESC, v.vst_version DESC",
            $params
        );
    }

    /**
     * Soumet une version en brouillon pour validation (draft -> pending_validation).
     * Refuse si la version n'est pas en brouillon.
     */
    public function soumettrePourValidation(int $id, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_versions_standards
             SET vst_etat_validation = 'pending_validation', vst_soumis_le = NOW(), vst_modifie_par_utilisateur_id = :user_id
             WHERE vst_id = :id AND vst_etat_validation = 'draft' AND vst_supprime_le IS NULL",
            ['id' => $id, 'user_id' => $userId]
        );
    }

    /**
     * Approuve une version en attente (pending_validation -> approved).
     * Le contrôle "validateur != auteur et de niveau supérieur" est fait
     * en amont par StandardService, pas ici : ce modèle ne fait que
     * persister la décision déjà autorisée.
     */
    public function validerVersion(int $id, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_versions_standards
             SET vst_etat_validation = 'approved', vst_valide_par_utilisateur_id = :user_id, vst_valide_le = NOW(),
                 vst_modifie_par_utilisateur_id = :user_id
             WHERE vst_id = :id AND vst_etat_validation = 'pending_validation' AND vst_supprime_le IS NULL",
            ['id' => $id, 'user_id' => $userId]
        );
    }

    public function rejeterVersion(int $id, int $userId, string $motif): bool
    {
        return $this->db->execute(
            "UPDATE sav_versions_standards
             SET vst_etat_validation = 'rejected', vst_valide_par_utilisateur_id = :user_id, vst_rejete_le = NOW(),
                 vst_motif_rejet = :motif, vst_modifie_par_utilisateur_id = :user_id
             WHERE vst_id = :id AND vst_etat_validation = 'pending_validation' AND vst_supprime_le IS NULL",
            ['id' => $id, 'user_id' => $userId, 'motif' => $motif]
        );
    }

    public function updateVersion(int $id, array $data, ?int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_versions_standards
             SET vst_version = :version,
                 vst_valide_du = :valide_du,
                 vst_valide_au = :valide_au,
                 vst_statut_id = :statut_id,
                 vst_modifie_par_utilisateur_id = :user_id
             WHERE vst_id = :id AND vst_supprime_le IS NULL AND vst_archive_le IS NULL",
            [
                'id' => $id,
                'version' => trim((string) ($data['vst_version'] ?? $data['version'] ?? '')),
                'valide_du' => trim((string) ($data['vst_valide_du'] ?? $data['valide_du'] ?? '')),
                'valide_au' => $this->emptyToNull($data['vst_valide_au'] ?? $data['valide_au'] ?? null),
                'statut_id' => $this->nullableInt($data['vst_statut_id'] ?? $data['statut_id'] ?? null) ?? $this->statusId('general', 'actif'),
                'user_id' => $userId,
            ]
        );
    }

    public function softDeleteVersion(int $id, ?int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_versions_standards SET vst_supprime_le = NOW(), vst_supprime_par_utilisateur_id = :user_id
             WHERE vst_id = :id AND vst_supprime_le IS NULL",
            ['id' => $id, 'user_id' => $userId]
        );
    }

    public function exigences(?int $versionId = null): array
    {
        $where = ['e.evs_supprime_le IS NULL'];
        $params = [];
        if ($versionId !== null) {
            $where[] = 'e.evs_version_standard_id = :version_id';
            $params['version_id'] = $versionId;
        }

        return $this->db->fetchAll(
            "SELECT e.*, v.vst_version, s.std_code, s.std_nom,
                    c.cmp_code, c.cmp_nom,
                    ce.cer_code, ce.cer_nom,
                    n.nco_code, n.nco_nom, n.nco_rang
             FROM sav_exigences_versions_standards e
             INNER JOIN sav_versions_standards v ON v.vst_id = e.evs_version_standard_id
             INNER JOIN sav_standards s ON s.std_id = v.vst_standard_id
             LEFT JOIN sav_competences c ON c.cmp_id = e.evs_competence_id
             LEFT JOIN sav_certifications ce ON ce.cer_id = e.evs_certification_id
             LEFT JOIN sav_niveaux_competences n ON n.nco_id = e.evs_niveau_competence_minimum_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY s.std_nom ASC, v.vst_valide_du DESC, e.evs_est_obligatoire DESC, e.evs_type_exigence ASC",
            $params
        );
    }

    public function createExigence(array $data, ?int $userId): int
    {
        $versionId = $this->nullableInt($data['evs_version_standard_id'] ?? $data['version_id'] ?? null);
        $type = trim((string) ($data['evs_type_exigence'] ?? $data['type_exigence'] ?? 'competence'));
        if (!$versionId || $type === '') {
            throw new \InvalidArgumentException('La version et le type d’exigence sont obligatoires.');
        }
        $this->db->execute(
            "INSERT INTO sav_exigences_versions_standards
                (evs_version_standard_id, evs_type_exigence, evs_competence_id, evs_certification_id,
                 evs_niveau_competence_minimum_id, evs_est_obligatoire, evs_cree_par_utilisateur_id,
                 evs_modifie_par_utilisateur_id)
             VALUES
                (:version_id, :type_exigence, :competence_id, :certification_id,
                 :niveau_id, :obligatoire, :user_id, :user_id)",
            [
                'version_id' => $versionId,
                'type_exigence' => $type,
                'competence_id' => $this->nullableInt($data['evs_competence_id'] ?? $data['competence_id'] ?? null),
                'certification_id' => $this->nullableInt($data['evs_certification_id'] ?? $data['certification_id'] ?? null),
                'niveau_id' => $this->nullableInt($data['evs_niveau_competence_minimum_id'] ?? $data['niveau_id'] ?? null),
                'obligatoire' => !empty($data['evs_est_obligatoire'] ?? $data['obligatoire'] ?? true) ? 1 : 0,
                'user_id' => $userId,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function softDeleteExigence(int $id, ?int $userId): bool
    {
        return $this->db->execute(
            "UPDATE sav_exigences_versions_standards SET evs_supprime_le = NOW(), evs_supprime_par_utilisateur_id = :user_id
             WHERE evs_id = :id AND evs_supprime_le IS NULL",
            ['id' => $id, 'user_id' => $userId]
        );
    }

    public function referentiels(): array
    {
        return [
            'societes' => $this->db->fetchAll("SELECT soc_id, soc_nom FROM sav_societes WHERE soc_supprime_le IS NULL AND soc_archive_le IS NULL ORDER BY soc_nom ASC"),
            'standards' => $this->db->fetchAll("SELECT std_id, std_code, std_nom FROM sav_standards WHERE std_supprime_le IS NULL AND std_archive_le IS NULL ORDER BY std_nom ASC"),
            'versions' => $this->db->fetchAll("SELECT v.vst_id, v.vst_version, s.std_code, s.std_nom FROM sav_versions_standards v INNER JOIN sav_standards s ON s.std_id = v.vst_standard_id WHERE v.vst_supprime_le IS NULL AND v.vst_archive_le IS NULL ORDER BY s.std_nom ASC, v.vst_valide_du DESC"),
            'competences' => $this->db->fetchAll("SELECT cmp_id, cmp_code, cmp_nom FROM sav_competences WHERE cmp_supprime_le IS NULL AND cmp_archive_le IS NULL ORDER BY cmp_nom ASC"),
            'certifications' => $this->db->fetchAll("SELECT cer_id, cer_code, cer_nom FROM sav_certifications WHERE cer_supprime_le IS NULL AND cer_archive_le IS NULL ORDER BY cer_nom ASC"),
            'niveaux' => $this->db->fetchAll("SELECT nco_id, nco_code, nco_nom, nco_rang FROM sav_niveaux_competences WHERE nco_supprime_le IS NULL ORDER BY nco_rang ASC"),
            'statuts' => $this->db->fetchAll("SELECT sta_id, sta_code, sta_libelle FROM sav_statuts WHERE sta_supprime_le IS NULL ORDER BY sta_domaine ASC, sta_libelle ASC"),
            'types_standard' => ['constructeur', 'importateur', 'concession', 'marque', 'qualite', 'securite', 'formation'],
        ];
    }

    public function evaluationUtilisateurs(int $versionId): array
    {
        return $this->db->fetchAll(
            "SELECT u.uti_id, u.uti_email, p.pui_prenom, p.pui_nom, soc.soc_nom,
                    COUNT(DISTINCT e.evs_id) AS exigences_total,
                    SUM(CASE WHEN e.evs_est_obligatoire = 1 THEN 1 ELSE 0 END) AS exigences_obligatoires,
                    SUM(CASE WHEN e.evs_competence_id IS NOT NULL AND cu.cut_id IS NOT NULL
                              AND (e.evs_niveau_competence_minimum_id IS NULL OR ncu.nco_rang >= nmin.nco_rang)
                             THEN 1
                             WHEN e.evs_certification_id IS NOT NULL AND certu.ceu_id IS NOT NULL
                              AND (certu.ceu_expire_le IS NULL OR certu.ceu_expire_le >= CURDATE())
                             THEN 1
                             ELSE 0 END) AS exigences_couvertes
             FROM sav_exigences_versions_standards e
             CROSS JOIN sav_adhesions_utilisateurs_societes a
             INNER JOIN sav_utilisateurs u ON u.uti_id = a.aus_utilisateur_id AND u.uti_supprime_le IS NULL
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
             LEFT JOIN sav_societes soc ON soc.soc_id = a.aus_societe_id
             LEFT JOIN sav_niveaux_competences nmin ON nmin.nco_id = e.evs_niveau_competence_minimum_id
             LEFT JOIN sav_competences_utilisateurs cu ON cu.cut_utilisateur_id = u.uti_id
                AND cu.cut_competence_id = e.evs_competence_id
                AND cu.cut_societe_id = a.aus_societe_id
                AND cu.cut_supprime_le IS NULL AND cu.cut_archive_le IS NULL
             LEFT JOIN sav_niveaux_competences ncu ON ncu.nco_id = cu.cut_niveau_competence_id
             LEFT JOIN sav_certifications_utilisateurs certu ON certu.ceu_utilisateur_id = u.uti_id
                AND certu.ceu_certification_id = e.evs_certification_id
                AND certu.ceu_societe_id = a.aus_societe_id
                AND certu.ceu_supprime_le IS NULL AND certu.ceu_archive_le IS NULL
             WHERE e.evs_version_standard_id = :version_id
               AND e.evs_supprime_le IS NULL
               AND a.aus_supprime_le IS NULL AND a.aus_archive_le IS NULL
               AND (a.aus_termine_le IS NULL OR a.aus_termine_le >= CURDATE())
             GROUP BY u.uti_id, a.aus_societe_id
             ORDER BY soc.soc_nom ASC, p.pui_nom ASC, p.pui_prenom ASC",
            ['version_id' => $versionId]
        );
    }

    public function statusId(string $domain, string $code): ?int
    {
        $id = $this->db->fetchColumn(
            "SELECT sta_id FROM sav_statuts WHERE sta_domaine = :domain AND sta_code = :code AND sta_supprime_le IS NULL LIMIT 1",
            ['domain' => $domain, 'code' => $code]
        );
        return $id !== false && $id !== null ? (int) $id : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return null;
        }
        return (int) $value;
    }

    private function emptyToNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
