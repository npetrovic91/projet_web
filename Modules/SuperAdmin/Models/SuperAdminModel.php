<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\SuperAdmin\Models;

use Nenad\Autosav\Core\Database\Database;

/**
 * Modèle SuperAdmin — centre de contrôle global de l'application.
 * Toutes les requêtes restent en lecture pour éviter les effets de bord.
 */
final class SuperAdminModel
{
    private Database $db;

    public function __construct(?Database $database = null)
    {
        $this->db = $database ?? Database::getInstance();
    }

    public function indicateurs(): array
    {
        return [
            'societes_total'       => $this->compter('sav_societes', 'soc_supprime_le IS NULL'),
            'societes_abonnees'    => $this->compter('sav_abonnements_societes', 'abo_supprime_le IS NULL AND abo_archive_le IS NULL'),
            'espaces_actifs'       => $this->compter('sav_espaces_applicatifs', 'eap_supprime_le IS NULL'),
            'modules'              => $this->compter('sav_modules', 'mod_supprime_le IS NULL AND mod_archive_le IS NULL'),
            'modules_societes'     => $this->compter('sav_modules_societes', 'mos_supprime_le IS NULL AND mos_archive_le IS NULL'),
            'utilisateurs'         => $this->compter('sav_utilisateurs', 'uti_supprime_le IS NULL'),
            'sessions_actives'     => $this->compter('sav_sessions_utilisateurs', 'seu_supprime_le IS NULL AND seu_revoquee_le IS NULL AND seu_expire_le >= NOW()'),
            'tentatives_connexion' => $this->compter('sav_tentatives_connexion', 'tcn_cree_le >= DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
            'blocages_securite'    => $this->compter('sav_blocages_securite', 'bse_supprime_le IS NULL'),
            'connecteurs'          => $this->compter('sav_connecteurs', 'con_supprime_le IS NULL'),
            'cles_api_actives'     => $this->compter('sav_cles_api', 'cap_revoque_le IS NULL'),
            'logs_systeme_24h'     => $this->compter('sav_journaux_systeme', 'jsy_cree_le >= DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
            'logs_audit_24h'       => $this->compter('sav_journaux_audit', 'jau_cree_le >= DATE_SUB(NOW(), INTERVAL 24 HOUR)'),
        ];
    }

    public function parametresCritiques(): array
    {
        return $this->db->fetchAll(
            "SELECT pap_id, pap_domaine, pap_cle, pap_valeur_json, pap_est_secret, pap_est_systeme, pap_modifie_le
             FROM sav_parametres_application
             WHERE pap_supprime_le IS NULL
               AND (pap_est_systeme = 1 OR pap_domaine IN ('application','securite','maintenance','api','evenements'))
             ORDER BY pap_domaine, pap_cle
             LIMIT 200"
        );
    }

    public function modulesInstalles(): array
    {
        return $this->db->fetchAll(
            "SELECT m.mod_id, m.mod_code, m.mod_nom, m.mod_version, m.mod_est_noyau, m.mod_statut_id,
                    COUNT(ms.mos_id) AS societes_activees
             FROM sav_modules m
             LEFT JOIN sav_modules_societes ms
               ON ms.mos_module_id = m.mod_id
              AND ms.mos_supprime_le IS NULL
              AND ms.mos_archive_le IS NULL
             WHERE m.mod_supprime_le IS NULL AND m.mod_archive_le IS NULL
             GROUP BY m.mod_id, m.mod_code, m.mod_nom, m.mod_version, m.mod_est_noyau, m.mod_statut_id
             ORDER BY m.mod_est_noyau DESC, m.mod_code"
        );
    }

    public function dernieresAlertes(): array
    {
        return $this->db->fetchAll(
            "SELECT 'systeme' AS source, jsy_id AS id, jsy_niveau AS niveau, jsy_categorie AS categorie, jsy_message AS message, jsy_cree_le AS cree_le
             FROM sav_journaux_systeme
             WHERE jsy_cree_le >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             UNION ALL
             SELECT 'audit' AS source, jau_id AS id, 'info' AS niveau, jau_action AS categorie, COALESCE(jau_raison, jau_action) AS message, jau_cree_le AS cree_le
             FROM sav_journaux_audit
             WHERE jau_cree_le >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY cree_le DESC
             LIMIT 50"
        );
    }

    public function etatMaintenance(): array
    {
        $param = $this->db->fetch(
            "SELECT pap_valeur_json, pap_modifie_le
             FROM sav_parametres_application
             WHERE pap_domaine = 'application' AND pap_cle = 'mode_maintenance' AND pap_supprime_le IS NULL
             LIMIT 1"
        );
        $valeur = false;
        if ($param && !empty($param['pap_valeur_json'])) {
            $json = json_decode((string)$param['pap_valeur_json'], true);
            $valeur = (bool)($json['valeur'] ?? false);
        }
        return [
            'actif' => $valeur,
            'modifie_le' => $param['pap_modifie_le'] ?? null,
        ];
    }

    private function compter(string $table, string $where = '1=1'): int
    {
        try {
            return (int)$this->db->fetchColumn("SELECT COUNT(*) FROM {$table} WHERE {$where}");
        } catch (\Throwable) {
            return 0;
        }
    }
}
