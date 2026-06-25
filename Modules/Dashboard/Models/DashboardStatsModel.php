<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Dashboard\Models;

use Nenad\Autosav\Core\Database\Database;
use PDO;

/**
 * Statistiques Dashboard alignées sur le dump SQL de référence.
 *
 * Règle de tri métier demandée : utilisateur → société d’appartenance → niveaux.
 * Les niveaux exposés combinent : niveau applicatif issu des rôles contextuels,
 * niveau de compétence maximal et certifications actives.
 */
class DashboardStatsModel
{
    private Database $db;
    private PDO $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->pdo = $this->db->getPdo();
    }

    public function snapshot(?int $companyId, bool $globalScope): array
    {
        $params = [];
        $membershipWhere = 'aus.aus_supprime_le IS NULL AND aus.aus_archive_le IS NULL AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= CURDATE())';
        if (!$globalScope && $companyId) {
            $membershipWhere .= ' AND aus.aus_societe_id = :company_id';
            $params['company_id'] = $companyId;
        }

        $usersSql = "SELECT COUNT(DISTINCT u.uti_id)
                     FROM sav_utilisateurs u
                     INNER JOIN sav_adhesions_utilisateurs_societes aus ON aus.aus_utilisateur_id = u.uti_id
                     WHERE u.uti_supprime_le IS NULL
                       AND u.uti_anonymise_le IS NULL
                       AND {$membershipWhere}";

        $companiesSql = "SELECT COUNT(DISTINCT s.soc_id)
                         FROM sav_societes s
                         WHERE s.soc_supprime_le IS NULL AND s.soc_archive_le IS NULL";
        $companiesParams = [];
        if (!$globalScope && $companyId) {
            $companiesSql .= ' AND s.soc_id = :company_id';
            $companiesParams['company_id'] = $companyId;
        }

        $brandsSql = "SELECT COUNT(DISTINCT rma.rma_marque_societe_id)
                      FROM sav_representations_marques_societes rma
                      WHERE rma.rma_supprime_le IS NULL
                        AND rma.rma_archive_le IS NULL
                        AND (rma.rma_termine_le IS NULL OR rma.rma_termine_le >= CURDATE())";
        $brandsParams = [];
        if (!$globalScope && $companyId) {
            $brandsSql .= ' AND rma.rma_concession_societe_id = :company_id';
            $brandsParams['company_id'] = $companyId;
        }

        return [
            'users_total' => (int) $this->fetchColumn($usersSql, $params),
            'companies_total' => (int) $this->fetchColumn($companiesSql, $companiesParams),
            'brands_total' => (int) $this->fetchColumn($brandsSql, $brandsParams),
            'notifications_unread' => 0,
            'failed_logins_24h' => (int) $this->fetchColumn(
                "SELECT COUNT(*) FROM sav_tentatives_connexion
                 WHERE tcn_succes = 0 AND tcn_cree_le >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
            ),
            'security_blocks_active' => (int) $this->fetchColumn(
                "SELECT COUNT(*) FROM sav_blocages_securite
                 WHERE bse_supprime_le IS NULL
                   AND (bse_termine_le IS NULL OR bse_termine_le > NOW())"
            ),
            'gdpr_pending' => (int) $this->fetchColumn(
                "SELECT COUNT(*) FROM sav_demandes_rgpd drg
                 LEFT JOIN sav_statuts st ON st.sta_id = drg.drg_statut_id
                 WHERE drg.drg_supprime_le IS NULL
                   AND (st.sta_code IS NULL OR st.sta_code NOT IN ('traite', 'termine', 'ferme', 'archive', 'supprime'))"
            ),
            'maintenance_active' => in_array((string) $this->fetchColumn(
                "SELECT JSON_UNQUOTE(JSON_EXTRACT(pap_valeur_json, '$.valeur'))
                 FROM sav_parametres_application
                 WHERE pap_domaine = 'application'
                   AND pap_cle = 'mode_maintenance'
                   AND pap_supprime_le IS NULL
                 LIMIT 1"
            ), ['1', 'true', 'vrai', 'oui'], true),
        ];
    }

    public function unreadNotificationsForUser(int $userId, ?int $companyId = null, int $limit = 5): array
    {
        $params = ['user_id' => $userId, 'limit' => max(1, min(20, $limit))];
        $whereCompany = '';
        if ($companyId) {
            $whereCompany = ' AND (n.not_societe_id IS NULL OR n.not_societe_id = :company_id)';
            $params['company_id'] = $companyId;
        }

        $sql = "SELECT n.not_id, n.not_type, n.not_priorite, n.not_titre, n.not_message,
                       n.not_lien_url, n.not_cree_le, d.dno_lu_le, d.dno_envoye_le
                FROM sav_destinataires_notifications d
                INNER JOIN sav_notifications n ON n.not_id = d.dno_notification_id
                WHERE d.dno_utilisateur_id = :user_id
                  AND d.dno_supprime_le IS NULL
                  AND d.dno_lu_le IS NULL
                  AND n.not_supprime_le IS NULL
                  AND (n.not_expire_le IS NULL OR n.not_expire_le > NOW())
                  {$whereCompany}
                ORDER BY n.not_priorite DESC, n.not_cree_le DESC
                LIMIT :limit";

        return $this->fetchAll($sql, $params);
    }

    public function recentSecurityAttempts(int $limit = 8): array
    {
        return $this->fetchAll(
            "SELECT tcn_id, tcn_utilisateur_id, tcn_email_tente, tcn_email_normalise,
                    INET6_NTOA(tcn_adresse_ip) AS adresse_ip, tcn_succes, tcn_raison_echec, tcn_cree_le
             FROM sav_tentatives_connexion
             ORDER BY tcn_cree_le DESC
             LIMIT :limit",
            ['limit' => max(1, min(25, $limit))]
        );
    }

    public function usersByCompanyAndLevels(?int $companyId, bool $globalScope, int $limit = 50): array
    {
        $params = ['limit' => max(1, min(200, $limit))];
        $companyFilter = '';
        if (!$globalScope && $companyId) {
            $companyFilter = ' AND aus.aus_societe_id = :company_id';
            $params['company_id'] = $companyId;
        }

        $sql = "SELECT
                    u.uti_id,
                    u.uti_email,
                    u.uti_identifiant,
                    u.uti_est_verrouille,
                    u.uti_derniere_connexion_le,
                    p.pui_nom,
                    p.pui_prenom,
                    s.soc_id,
                    s.soc_code,
                    s.soc_nom,
                    st.sta_code AS statut_code,
                    st.sta_libelle AS statut_libelle,
                    COALESCE(roles.roles_codes, '') AS roles_codes,
                    COALESCE(roles.roles_noms, '') AS roles_noms,
                    COALESCE(roles.niveau_applicatif_rang, 0) AS niveau_applicatif_rang,
                    COALESCE(skill.niveau_competence_rang, 0) AS niveau_competence_rang,
                    COALESCE(skill.niveau_competence_nom, 'Non renseigné') AS niveau_competence_nom,
                    COALESCE(skill.competences_resume, '') AS competences_resume,
                    COALESCE(cert.certifications_total, 0) AS certifications_total,
                    COALESCE(struct.services_noms, '') AS services_noms,
                    COALESCE(struct.equipes_noms, '') AS equipes_noms,
                    COALESCE(manager.manager_nom, '') AS manager_nom
                FROM sav_adhesions_utilisateurs_societes aus
                INNER JOIN sav_utilisateurs u ON u.uti_id = aus.aus_utilisateur_id
                LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
                INNER JOIN sav_societes s ON s.soc_id = aus.aus_societe_id
                LEFT JOIN sav_statuts st ON st.sta_id = u.uti_statut_id
                LEFT JOIN (
                    SELECT rcu.rcu_utilisateur_id,
                           COALESCE(rcu.rcu_societe_id, 0) AS societe_id,
                           GROUP_CONCAT(DISTINCT r.rol_code ORDER BY r.rol_code SEPARATOR ', ') AS roles_codes,
                           GROUP_CONCAT(DISTINCT r.rol_nom ORDER BY r.rol_nom SEPARATOR ', ') AS roles_noms,
                           MAX(CASE
                               WHEN r.rol_code IN ('super_administrateur', 'super_admin', 'SUPERADMIN') THEN 100
                               WHEN r.rol_code IN ('administrateur_general', 'admin_general') THEN 90
                               WHEN r.rol_code IN ('administrateur_departement', 'admin_departement') THEN 80
                               WHEN r.rol_code IN ('administrateur_service', 'admin_service') THEN 70
                               WHEN r.rol_code IN ('administrateur_equipe', 'admin_equipe', 'manager') THEN 60
                               ELSE 10
                           END) AS niveau_applicatif_rang
                    FROM sav_roles_contextuels_utilisateurs rcu
                    INNER JOIN sav_roles r ON r.rol_id = rcu.rcu_role_id
                    WHERE rcu.rcu_supprime_le IS NULL
                      AND rcu.rcu_archive_le IS NULL
                      AND (rcu.rcu_termine_le IS NULL OR rcu.rcu_termine_le >= CURDATE())
                    GROUP BY rcu.rcu_utilisateur_id, COALESCE(rcu.rcu_societe_id, 0)
                ) roles ON roles.rcu_utilisateur_id = u.uti_id AND (roles.societe_id = aus.aus_societe_id OR roles.societe_id = 0)
                LEFT JOIN (
                    SELECT cut.cut_utilisateur_id,
                           cut.cut_societe_id,
                           MAX(nco.nco_rang) AS niveau_competence_rang,
                           SUBSTRING_INDEX(GROUP_CONCAT(nco.nco_nom ORDER BY nco.nco_rang DESC, nco.nco_nom SEPARATOR '||'), '||', 1) AS niveau_competence_nom,
                           GROUP_CONCAT(DISTINCT CONCAT(cmp.cmp_nom, ' — ', nco.nco_nom) ORDER BY nco.nco_rang DESC, cmp.cmp_nom SEPARATOR ', ') AS competences_resume
                    FROM sav_competences_utilisateurs cut
                    INNER JOIN sav_competences cmp ON cmp.cmp_id = cut.cut_competence_id
                    INNER JOIN sav_niveaux_competences nco ON nco.nco_id = cut.cut_niveau_competence_id
                    WHERE cut.cut_supprime_le IS NULL
                      AND cut.cut_archive_le IS NULL
                      AND (cut.cut_termine_le IS NULL OR cut.cut_termine_le >= CURDATE())
                    GROUP BY cut.cut_utilisateur_id, cut.cut_societe_id
                ) skill ON skill.cut_utilisateur_id = u.uti_id AND skill.cut_societe_id = aus.aus_societe_id
                LEFT JOIN (
                    SELECT ceu.ceu_utilisateur_id,
                           ceu.ceu_societe_id,
                           COUNT(DISTINCT ceu.ceu_certification_id) AS certifications_total
                    FROM sav_certifications_utilisateurs ceu
                    WHERE ceu.ceu_supprime_le IS NULL
                      AND ceu.ceu_archive_le IS NULL
                      AND (ceu.ceu_expire_le IS NULL OR ceu.ceu_expire_le >= CURDATE())
                    GROUP BY ceu.ceu_utilisateur_id, ceu.ceu_societe_id
                ) cert ON cert.ceu_utilisateur_id = u.uti_id AND cert.ceu_societe_id = aus.aus_societe_id
                LEFT JOIN (
                    SELECT usv.usv_utilisateur_id,
                           usv.usv_societe_id,
                           GROUP_CONCAT(DISTINCT srv.srv_nom ORDER BY srv.srv_nom SEPARATOR ', ') AS services_noms,
                           GROUP_CONCAT(DISTINCT equ.equ_nom ORDER BY equ.equ_nom SEPARATOR ', ') AS equipes_noms
                    FROM sav_utilisateurs_services usv
                    LEFT JOIN sav_services srv ON srv.srv_id = usv.usv_service_id
                    LEFT JOIN sav_utilisateurs_equipes ueq ON ueq.ueq_utilisateur_id = usv.usv_utilisateur_id AND ueq.ueq_societe_id = usv.usv_societe_id AND ueq.ueq_supprime_le IS NULL AND ueq.ueq_archive_le IS NULL
                    LEFT JOIN sav_equipes equ ON equ.equ_id = ueq.ueq_equipe_id
                    WHERE usv.usv_supprime_le IS NULL
                      AND usv.usv_archive_le IS NULL
                      AND (usv.usv_termine_le IS NULL OR usv.usv_termine_le >= CURDATE())
                    GROUP BY usv.usv_utilisateur_id, usv.usv_societe_id
                ) struct ON struct.usv_utilisateur_id = u.uti_id AND struct.usv_societe_id = aus.aus_societe_id
                LEFT JOIN (
                    SELECT hiu.hiu_utilisateur_id,
                           hiu.hiu_societe_id,
                           TRIM(CONCAT(COALESCE(pm.pui_prenom, ''), ' ', COALESCE(pm.pui_nom, ''))) AS manager_nom
                    FROM sav_hierarchie_utilisateurs hiu
                    INNER JOIN sav_utilisateurs um ON um.uti_id = hiu.hiu_superieur_utilisateur_id
                    LEFT JOIN sav_profils_utilisateurs pm ON pm.pui_utilisateur_id = um.uti_id AND pm.pui_supprime_le IS NULL
                    WHERE hiu.hiu_supprime_le IS NULL
                      AND hiu.hiu_archive_le IS NULL
                      AND (hiu.hiu_termine_le IS NULL OR hiu.hiu_termine_le >= CURDATE())
                ) manager ON manager.hiu_utilisateur_id = u.uti_id AND manager.hiu_societe_id = aus.aus_societe_id
                WHERE aus.aus_supprime_le IS NULL
                  AND aus.aus_archive_le IS NULL
                  AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= CURDATE())
                  AND u.uti_supprime_le IS NULL
                  AND u.uti_anonymise_le IS NULL
                  AND s.soc_supprime_le IS NULL
                  AND s.soc_archive_le IS NULL
                  {$companyFilter}
                ORDER BY
                    COALESCE(NULLIF(p.pui_nom, ''), u.uti_email) ASC,
                    COALESCE(NULLIF(p.pui_prenom, ''), '') ASC,
                    s.soc_nom ASC,
                    niveau_applicatif_rang DESC,
                    niveau_competence_rang DESC,
                    roles_noms ASC
                LIMIT :limit";

        return array_map([$this, 'normaliseUserLevelRow'], $this->fetchAll($sql, $params));
    }

    public function companyLevelSummary(?int $companyId, bool $globalScope): array
    {
        $rows = $this->usersByCompanyAndLevels($companyId, $globalScope, 200);
        $summary = [];
        foreach ($rows as $row) {
            $socId = (int) $row['societe_id'];
            if (!isset($summary[$socId])) {
                $summary[$socId] = [
                    'societe_id' => $socId,
                    'societe_nom' => $row['societe_nom'],
                    'utilisateurs_total' => 0,
                    'niveau_applicatif_max' => 0,
                    'niveau_competence_max' => 0,
                    'certifications_total' => 0,
                ];
            }
            $summary[$socId]['utilisateurs_total']++;
            $summary[$socId]['niveau_applicatif_max'] = max($summary[$socId]['niveau_applicatif_max'], (int) $row['niveau_applicatif_rang']);
            $summary[$socId]['niveau_competence_max'] = max($summary[$socId]['niveau_competence_max'], (int) $row['niveau_competence_rang']);
            $summary[$socId]['certifications_total'] += (int) $row['certifications_total'];
        }

        usort($summary, static function (array $a, array $b): int {
            return strcmp((string) $a['societe_nom'], (string) $b['societe_nom'])
                ?: ((int) $b['niveau_applicatif_max'] <=> (int) $a['niveau_applicatif_max'])
                ?: ((int) $b['niveau_competence_max'] <=> (int) $a['niveau_competence_max']);
        });

        return $summary;
    }

    private function normaliseUserLevelRow(array $row): array
    {
        $name = trim((string) ($row['pui_prenom'] ?? '') . ' ' . (string) ($row['pui_nom'] ?? ''));
        if ($name === '') {
            $name = (string) ($row['uti_email'] ?? 'Utilisateur');
        }

        $row['utilisateur_id'] = (int) ($row['uti_id'] ?? 0);
        $row['utilisateur_nom'] = $name;
        $row['societe_id'] = (int) ($row['soc_id'] ?? 0);
        $row['societe_nom'] = (string) ($row['soc_nom'] ?? 'Société non renseignée');
        $row['niveau_applicatif_rang'] = (int) ($row['niveau_applicatif_rang'] ?? 0);
        $row['niveau_applicatif_label'] = $this->roleLevelLabel($row['niveau_applicatif_rang']);
        $row['niveau_competence_rang'] = (int) ($row['niveau_competence_rang'] ?? 0);
        $row['certifications_total'] = (int) ($row['certifications_total'] ?? 0);
        return $row;
    }

    private function roleLevelLabel(int $rank): string
    {
        return match (true) {
            $rank >= 100 => 'Super-admin',
            $rank >= 90 => 'Admin général',
            $rank >= 80 => 'Admin département',
            $rank >= 70 => 'Admin service',
            $rank >= 60 => 'Admin équipe / manager',
            $rank > 0 => 'Utilisateur',
            default => 'Non attribué',
        };
    }


    // ══════════════════════════════════════════════════════════════════
    // PROFILS PAR TYPE DE SOCIÉTÉ
    // ══════════════════════════════════════════════════════════════════

    /**
     * Retourne les codes de type (tso_code) de la société active.
     * Ex : ['constructeur'], ['importateur'], ['marque'], ['concession'].
     *
     * @return string[]
     */
    public function getCompanyTypeCodes(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }
        $rows = $this->fetchAll(
            "SELECT ts.tso_code
               FROM sav_types_societes ts
         INNER JOIN sav_affectations_types_societes ats
                 ON ats.ats_type_societe_id = ts.tso_id
              WHERE ats.ats_societe_id   = :cid
                AND ats.ats_supprime_le IS NULL
                AND ats.ats_archive_le  IS NULL
                AND ts.tso_supprime_le  IS NULL
                AND ts.tso_archive_le   IS NULL",
            ['cid' => $companyId]
        );
        return array_column($rows, 'tso_code');
    }

    /**
     * Statistiques tableau de bord pour un CONSTRUCTEUR.
     *
     * Indicateurs :
     *   – utilisateurs_total   : collaborateurs rattachés à cette société
     *   – marques_total        : marques (sociétés de type marque) dont ce
     *                           constructeur est propriétaire (ats filtrés)
     *   – concessions_total    : points de vente représentant au moins une
     *                           des marques du constructeur
     *   – representations_total: lignes actives dans sav_representations_marques_societes
     *                           pour les marques du constructeur
     */
    /**
     * Statistiques tableau de bord pour un CONSTRUCTEUR.
     *
     * Modèle de données retenu :
     *   Le schema ne possède pas de FK directe constructeur → marque.
     *   Un constructeur (ou importateur primaire) apparaît comme
     *   `rma_concession_societe_id` dans sav_representations_marques_societes
     *   pour les marques qu'il "distribue" en tête de réseau.
     *   Les concessions du réseau sont les AUTRES rma_concession_societe_id
     *   qui représentent les mêmes marques.
     *
     * Indicateurs :
     *   – utilisateurs_total   : collaborateurs rattachés à cette société
     *   – marques_total        : marques (tso_code='marque') dont ce constructeur
     *                           est représentant de tête de réseau
     *   – concessions_total    : points de vente tiers représentant ces mêmes marques
     *   – representations_total: lignes actives liées aux marques du constructeur
     */
    /**
     * Statistiques tableau de bord pour un CONSTRUCTEUR.
     *
     * La table sav_representations_marques_societes possède une colonne
     * rma_constructeur_societe_id qui est le FK direct vers le constructeur.
     * On filtre sur cette colonne pour récupérer :
     *   – les marques gérées par ce constructeur
     *   – les concessions de son réseau
     *   – les représentations actives
     */
    public function constructeurStats(int $companyId): array
    {
        if ($companyId <= 0) {
            return $this->emptyTypeStats();
        }

        // 1 — Collaborateurs rattachés à la société constructeur
        $utilisateurs = (int) $this->fetchColumn(
            "SELECT COUNT(DISTINCT aus.aus_utilisateur_id)
               FROM sav_adhesions_utilisateurs_societes aus
               JOIN sav_utilisateurs u ON u.uti_id = aus.aus_utilisateur_id
              WHERE aus.aus_societe_id   = :cid
                AND aus.aus_supprime_le IS NULL
                AND aus.aus_archive_le  IS NULL
                AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= CURDATE())
                AND u.uti_supprime_le   IS NULL",
            ['cid' => $companyId]
        );

        // 2 — Marques portant ce constructeur (rma_constructeur_societe_id)
        $marques = (int) $this->fetchColumn(
            "SELECT COUNT(DISTINCT rma.rma_marque_societe_id)
               FROM sav_representations_marques_societes rma
              WHERE rma.rma_constructeur_societe_id = :cid
                AND rma.rma_supprime_le IS NULL
                AND rma.rma_archive_le  IS NULL
                AND (rma.rma_termine_le IS NULL OR rma.rma_termine_le >= CURDATE())",
            ['cid' => $companyId]
        );

        // 3 — Concessions actives représentant les marques du constructeur
        $concessions = (int) $this->fetchColumn(
            "SELECT COUNT(DISTINCT rma.rma_concession_societe_id)
               FROM sav_representations_marques_societes rma
              WHERE rma.rma_constructeur_societe_id = :cid
                AND rma.rma_supprime_le IS NULL
                AND rma.rma_archive_le  IS NULL
                AND (rma.rma_termine_le IS NULL OR rma.rma_termine_le >= CURDATE())",
            ['cid' => $companyId]
        );

        // 4 — Représentations actives portant ce constructeur
        $representations = (int) $this->fetchColumn(
            "SELECT COUNT(*)
               FROM sav_representations_marques_societes rma
              WHERE rma.rma_constructeur_societe_id = :cid
                AND rma.rma_supprime_le IS NULL
                AND rma.rma_archive_le  IS NULL
                AND (rma.rma_termine_le IS NULL OR rma.rma_termine_le >= CURDATE())",
            ['cid' => $companyId]
        );

        return [
            'utilisateurs_total'    => $utilisateurs,
            'marques_total'         => $marques,
            'concessions_total'     => $concessions,
            'representations_total' => $representations,
        ];
    }

    /**
     * Statistiques tableau de bord pour un IMPORTATEUR.
     *
     * Indicateurs :
     *   – utilisateurs_total   : collaborateurs rattachés à cette société
     *   – marques_importees    : marques (soc_type marque) dont cet importateur
     *                           est distributeur (via rma comme concession)
     *   – concessions_reseau   : autres concessions dans le même réseau de marques
     *   – representations_total: lignes actives pour les marques de cet importateur
     */
    public function importateurStats(int $companyId): array
    {
        if ($companyId <= 0) {
            return $this->emptyTypeStats();
        }

        // 1 — Collaborateurs
        $utilisateurs = (int) $this->fetchColumn(
            "SELECT COUNT(DISTINCT aus.aus_utilisateur_id)
               FROM sav_adhesions_utilisateurs_societes aus
               JOIN sav_utilisateurs u ON u.uti_id = aus.aus_utilisateur_id
              WHERE aus.aus_societe_id   = :cid
                AND aus.aus_supprime_le IS NULL
                AND aus.aus_archive_le  IS NULL
                AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= CURDATE())
                AND u.uti_supprime_le   IS NULL",
            ['cid' => $companyId]
        );

        // 2 — Marques portant cet importateur (rma_importateur_societe_id)
        $marques = (int) $this->fetchColumn(
            "SELECT COUNT(DISTINCT rma.rma_marque_societe_id)
               FROM sav_representations_marques_societes rma
              WHERE rma.rma_importateur_societe_id = :cid
                AND rma.rma_supprime_le IS NULL
                AND rma.rma_archive_le  IS NULL
                AND (rma.rma_termine_le IS NULL OR rma.rma_termine_le >= CURDATE())",
            ['cid' => $companyId]
        );

        // 3 — Concessions actives dans le réseau de cet importateur
        $concessions = (int) $this->fetchColumn(
            "SELECT COUNT(DISTINCT rma.rma_concession_societe_id)
               FROM sav_representations_marques_societes rma
              WHERE rma.rma_importateur_societe_id = :cid
                AND rma.rma_supprime_le IS NULL
                AND rma.rma_archive_le  IS NULL
                AND (rma.rma_termine_le IS NULL OR rma.rma_termine_le >= CURDATE())",
            ['cid' => $companyId]
        );

        // 4 — Représentations actives portant cet importateur
        $representations = (int) $this->fetchColumn(
            "SELECT COUNT(*)
               FROM sav_representations_marques_societes rma
              WHERE rma.rma_importateur_societe_id = :cid
                AND rma.rma_supprime_le IS NULL
                AND rma.rma_archive_le  IS NULL
                AND (rma.rma_termine_le IS NULL OR rma.rma_termine_le >= CURDATE())",
            ['cid' => $companyId]
        );

        return [
            'utilisateurs_total' => $utilisateurs,
            'marques_importees'  => $marques,
            'concessions_reseau' => $concessions,
            'representations_total' => $representations,
        ];
    }

    /**
     * Statistiques tableau de bord pour une MARQUE.
     *
     * Indicateurs :
     *   – utilisateurs_total   : collaborateurs rattachés à cette société
     *   – concessions_total    : points de vente qui représentent cette marque
     *   – representations_total: lignes actives dans sav_representations_marques_societes
     *   – est_principale_count : représentations où cette marque est principale
     */
    public function marqueStats(int $companyId): array
    {
        if ($companyId <= 0) {
            return $this->emptyTypeStats();
        }

        // 1 — Collaborateurs rattachés à cette société marque
        $utilisateurs = (int) $this->fetchColumn(
            "SELECT COUNT(DISTINCT aus.aus_utilisateur_id)
               FROM sav_adhesions_utilisateurs_societes aus
               JOIN sav_utilisateurs u ON u.uti_id = aus.aus_utilisateur_id
              WHERE aus.aus_societe_id   = :cid
                AND aus.aus_supprime_le IS NULL
                AND aus.aus_archive_le  IS NULL
                AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= CURDATE())
                AND u.uti_supprime_le   IS NULL",
            ['cid' => $companyId]
        );

        // 2 — Concessions représentant cette marque
        $concessions = (int) $this->fetchColumn(
            "SELECT COUNT(DISTINCT rma.rma_concession_societe_id)
               FROM sav_representations_marques_societes rma
              WHERE rma.rma_marque_societe_id = :cid
                AND rma.rma_supprime_le IS NULL
                AND rma.rma_archive_le  IS NULL
                AND (rma.rma_termine_le IS NULL OR rma.rma_termine_le >= CURDATE())",
            ['cid' => $companyId]
        );

        // 3 — Représentations actives totales
        $representations = (int) $this->fetchColumn(
            "SELECT COUNT(*)
               FROM sav_representations_marques_societes rma
              WHERE rma.rma_marque_societe_id = :cid
                AND rma.rma_supprime_le IS NULL
                AND rma.rma_archive_le  IS NULL
                AND (rma.rma_termine_le IS NULL OR rma.rma_termine_le >= CURDATE())",
            ['cid' => $companyId]
        );

        // 4 — Représentations où cette marque est la marque principale du point de vente
        $principale = (int) $this->fetchColumn(
            "SELECT COUNT(*)
               FROM sav_representations_marques_societes rma
              WHERE rma.rma_marque_societe_id = :cid
                AND rma.rma_est_principale     = 1
                AND rma.rma_supprime_le IS NULL
                AND rma.rma_archive_le  IS NULL
                AND (rma.rma_termine_le IS NULL OR rma.rma_termine_le >= CURDATE())",
            ['cid' => $companyId]
        );

        return [
            'utilisateurs_total'    => $utilisateurs,
            'concessions_total'     => $concessions,
            'representations_total' => $representations,
            'principale_count'      => $principale,
        ];
    }

    /** Tableau vide cohérent pour les stats de profil type. */
    private function emptyTypeStats(): array
    {
        return [
            'utilisateurs_total'    => 0,
            'marques_total'         => 0,
            'marques_importees'     => 0,
            'concessions_total'     => 0,
            'concessions_reseau'    => 0,
            'representations_total' => 0,
            'principale_count'      => 0,
        ];
    }

    private function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $this->bind($stmt, $params);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    private function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $this->bind($stmt, $params);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function bind(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $placeholder = is_int($key) ? $key + 1 : (str_starts_with((string) $key, ':') ? (string) $key : ':' . $key);
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default => PDO::PARAM_STR,
            };
            $stmt->bindValue($placeholder, $value, $type);
        }
    }
}
