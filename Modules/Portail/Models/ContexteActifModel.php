<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Portail\Models;

use Nenad\Autosav\Core\Middleware\TenantEntitlementMiddleware;
use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Portail société — contexte actif.
 *
 * Références SQL françaises :
 * - sav_utilisateurs, sav_adhesions_utilisateurs_societes, sav_societes ;
 * - sav_affectations_types_societes, sav_types_societes ;
 * - sav_representations_marques_societes ;
 * - sav_utilisateurs_services, sav_utilisateurs_equipes ;
 * - sav_services, sav_equipes, sav_sessions_utilisateurs, sav_historique_contextes_utilisateurs.
 */
class ContexteActifModel extends BaseModel
{
    protected string $table = 'sav_historique_contextes_utilisateurs';
    protected string $colPrefix = 'hcu_';

    public function utilisateur(int $utilisateurId): ?array
    {
        return $this->db->fetch(
            "SELECT u.*, p.pui_prenom, p.pui_nom
               FROM sav_utilisateurs u
               LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
              WHERE u.uti_id = :id AND u.uti_supprime_le IS NULL
              LIMIT 1",
            ['id' => $utilisateurId]
        );
    }

    public function societesAccessibles(int $utilisateurId): array
    {
        return $this->db->fetchAll(
            "SELECT s.soc_id, s.soc_nom, s.soc_code, s.soc_nom_court, s.soc_est_holding,
                    aus.aus_statut_id, eap.eap_id AS espace_applicatif_id,
                    CASE WHEN eap.eap_id IS NULL THEN 0 ELSE 1 END AS est_espace_applicatif,
                    GROUP_CONCAT(DISTINCT tso.tso_code ORDER BY tso.tso_code SEPARATOR ',') AS types_codes
               FROM sav_adhesions_utilisateurs_societes aus
               INNER JOIN sav_societes s ON s.soc_id = aus.aus_societe_id
               INNER JOIN sav_statuts st_aus ON st_aus.sta_id = aus.aus_statut_id
                    AND st_aus.sta_domaine = 'general' AND st_aus.sta_code = 'actif'
               INNER JOIN sav_statuts st_soc ON st_soc.sta_id = s.soc_statut_id
                    AND st_soc.sta_domaine = 'general' AND st_soc.sta_code = 'actif'
               INNER JOIN sav_espaces_applicatifs eap ON eap.eap_societe_id = s.soc_id
                    AND eap.eap_supprime_le IS NULL AND eap.eap_bloque_le IS NULL
               INNER JOIN sav_statuts st_eap ON st_eap.sta_id = eap.eap_statut_id
                    AND st_eap.sta_domaine = 'general' AND st_eap.sta_code = 'actif'
               INNER JOIN sav_abonnements_societes abo ON abo.abo_id = eap.eap_abonnement_societe_id
                    AND abo.abo_societe_id = s.soc_id AND abo.abo_supprime_le IS NULL AND abo.abo_archive_le IS NULL
                    AND (abo.abo_debute_le IS NULL OR abo.abo_debute_le <= NOW())
                    AND (abo.abo_termine_le IS NULL OR abo.abo_termine_le >= NOW())
               INNER JOIN sav_statuts st_abo ON st_abo.sta_id = abo.abo_statut_abonnement_id
                    AND st_abo.sta_domaine = 'abonnement' AND st_abo.sta_code IN ('actif', 'essai')
               INNER JOIN sav_statuts st_pay ON st_pay.sta_id = abo.abo_statut_paiement_id
                    AND st_pay.sta_domaine = 'paiement' AND st_pay.sta_code = 'a_jour'
               LEFT JOIN sav_affectations_types_societes ats ON ats.ats_societe_id = s.soc_id
                    AND ats.ats_supprime_le IS NULL AND ats.ats_archive_le IS NULL
                    AND (ats.ats_termine_le IS NULL OR ats.ats_termine_le >= CURDATE())
               LEFT JOIN sav_types_societes tso ON tso.tso_id = ats.ats_type_societe_id AND tso.tso_supprime_le IS NULL
              WHERE aus.aus_utilisateur_id = :user_id
                AND aus.aus_supprime_le IS NULL
                AND aus.aus_archive_le IS NULL
                AND (aus.aus_debute_le IS NULL OR aus.aus_debute_le <= NOW())
                AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= NOW())
                AND s.soc_supprime_le IS NULL
                AND s.soc_archive_le IS NULL
              GROUP BY s.soc_id, s.soc_nom, s.soc_code, s.soc_nom_court, s.soc_est_holding, aus.aus_statut_id, eap.eap_id
              ORDER BY s.soc_nom ASC",
            ['user_id' => $utilisateurId]
        );
    }

    public function societesConcessionsAccessibles(int $utilisateurId, ?int $societeId = null): array
    {
        $params = ['user_id' => $utilisateurId];
        $whereSociete = '';
        if ($societeId) {
            // Une concession peut être la société active elle-même, ou une société accessible au même utilisateur.
            $whereSociete = ' AND s.soc_id = :societe_id';
            $params['societe_id'] = $societeId;
        }
        return $this->db->fetchAll(
            "SELECT s.soc_id, s.soc_nom, s.soc_code, s.soc_nom_court
               FROM sav_adhesions_utilisateurs_societes aus
               INNER JOIN sav_societes s ON s.soc_id = aus.aus_societe_id
               INNER JOIN sav_statuts st_aus ON st_aus.sta_id = aus.aus_statut_id
                    AND st_aus.sta_domaine = 'general' AND st_aus.sta_code = 'actif'
               INNER JOIN sav_statuts st_soc ON st_soc.sta_id = s.soc_statut_id
                    AND st_soc.sta_domaine = 'general' AND st_soc.sta_code = 'actif'
               INNER JOIN sav_espaces_applicatifs eap ON eap.eap_societe_id = s.soc_id
                    AND eap.eap_supprime_le IS NULL AND eap.eap_bloque_le IS NULL
               INNER JOIN sav_statuts st_eap ON st_eap.sta_id = eap.eap_statut_id
                    AND st_eap.sta_domaine = 'general' AND st_eap.sta_code = 'actif'
               INNER JOIN sav_abonnements_societes abo ON abo.abo_id = eap.eap_abonnement_societe_id
                    AND abo.abo_societe_id = s.soc_id AND abo.abo_supprime_le IS NULL AND abo.abo_archive_le IS NULL
                    AND (abo.abo_debute_le IS NULL OR abo.abo_debute_le <= NOW())
                    AND (abo.abo_termine_le IS NULL OR abo.abo_termine_le >= NOW())
               INNER JOIN sav_statuts st_abo ON st_abo.sta_id = abo.abo_statut_abonnement_id
                    AND st_abo.sta_domaine = 'abonnement' AND st_abo.sta_code IN ('actif', 'essai')
               INNER JOIN sav_statuts st_pay ON st_pay.sta_id = abo.abo_statut_paiement_id
                    AND st_pay.sta_domaine = 'paiement' AND st_pay.sta_code = 'a_jour'
               INNER JOIN sav_affectations_types_societes ats ON ats.ats_societe_id = s.soc_id
                    AND ats.ats_supprime_le IS NULL AND ats.ats_archive_le IS NULL
                    AND (ats.ats_termine_le IS NULL OR ats.ats_termine_le >= CURDATE())
               INNER JOIN sav_types_societes tso ON tso.tso_id = ats.ats_type_societe_id
              WHERE aus.aus_utilisateur_id = :user_id
                AND aus.aus_supprime_le IS NULL
                AND aus.aus_archive_le IS NULL
                AND (aus.aus_debute_le IS NULL OR aus.aus_debute_le <= NOW())
                AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= NOW())
                AND s.soc_supprime_le IS NULL
                AND s.soc_archive_le IS NULL
                AND LOWER(tso.tso_code) IN ('concession', 'groupe_concessions', 'garage', 'mra')
                {$whereSociete}
              GROUP BY s.soc_id, s.soc_nom, s.soc_code, s.soc_nom_court
              ORDER BY s.soc_nom ASC",
            $params
        );
    }

    public function marquesPourConcession(int $concessionSocieteId): array
    {
        return $this->db->fetchAll(
            "SELECT m.soc_id, m.soc_nom, m.soc_code, r.rma_id,
                    i.soc_nom AS importateur_nom, c.soc_nom AS constructeur_nom
               FROM sav_representations_marques_societes r
               INNER JOIN sav_societes m ON m.soc_id = r.rma_marque_societe_id
               LEFT JOIN sav_societes i ON i.soc_id = r.rma_importateur_societe_id
               LEFT JOIN sav_societes c ON c.soc_id = r.rma_constructeur_societe_id
              WHERE r.rma_concession_societe_id = :concession_id
                AND r.rma_supprime_le IS NULL
                AND r.rma_archive_le IS NULL
                AND (r.rma_termine_le IS NULL OR r.rma_termine_le >= CURDATE())
                AND m.soc_supprime_le IS NULL
              ORDER BY m.soc_nom ASC",
            ['concession_id' => $concessionSocieteId]
        );
    }

    public function servicesUtilisateur(int $utilisateurId, int $societeId): array
    {
        return $this->db->fetchAll(
            "SELECT srv.srv_id, srv.srv_nom, srv.srv_code, usv.usv_id
               FROM sav_utilisateurs_services usv
               INNER JOIN sav_services srv ON srv.srv_id = usv.usv_service_id
              WHERE usv.usv_utilisateur_id = :user_id
                AND usv.usv_societe_id = :societe_id
                AND usv.usv_supprime_le IS NULL
                AND usv.usv_archive_le IS NULL
                AND (usv.usv_termine_le IS NULL OR usv.usv_termine_le >= CURDATE())
                AND srv.srv_supprime_le IS NULL
                AND srv.srv_archive_le IS NULL
              ORDER BY srv.srv_nom ASC",
            ['user_id' => $utilisateurId, 'societe_id' => $societeId]
        );
    }

    public function equipesUtilisateur(int $utilisateurId, int $societeId, ?int $serviceId = null): array
    {
        $params = ['user_id' => $utilisateurId, 'societe_id' => $societeId];
        $joinService = '';
        $whereService = '';
        if ($serviceId) {
            $joinService = " INNER JOIN sav_equipes_services eqs ON eqs.eqs_equipe_id = equ.equ_id
                AND eqs.eqs_societe_id = ueq.ueq_societe_id
                AND eqs.eqs_supprime_le IS NULL AND eqs.eqs_archive_le IS NULL
                AND (eqs.eqs_termine_le IS NULL OR eqs.eqs_termine_le >= CURDATE())";
            $whereService = ' AND eqs.eqs_service_id = :service_id';
            $params['service_id'] = $serviceId;
        }
        return $this->db->fetchAll(
            "SELECT equ.equ_id, equ.equ_nom, equ.equ_code, ueq.ueq_id
               FROM sav_utilisateurs_equipes ueq
               INNER JOIN sav_equipes equ ON equ.equ_id = ueq.ueq_equipe_id
               {$joinService}
              WHERE ueq.ueq_utilisateur_id = :user_id
                AND ueq.ueq_societe_id = :societe_id
                AND ueq.ueq_supprime_le IS NULL
                AND ueq.ueq_archive_le IS NULL
                AND (ueq.ueq_termine_le IS NULL OR ueq.ueq_termine_le >= CURDATE())
                AND equ.equ_supprime_le IS NULL
                AND equ.equ_archive_le IS NULL
                {$whereService}
              ORDER BY equ.equ_nom ASC",
            $params
        );
    }

    public function validerAdhesionSociete(int $utilisateurId, int $societeId): bool
    {
        return TenantEntitlementMiddleware::companyIsAccessible($utilisateurId, $societeId);
    }

    public function validerRepresentationMarque(int $concessionId, int $marqueId): bool
    {
        if ($concessionId <= 0 || $marqueId <= 0) {
            return false;
        }
        return (bool)$this->db->fetchColumn(
            "SELECT 1
               FROM sav_representations_marques_societes
              WHERE rma_concession_societe_id = :concession_id
                AND rma_marque_societe_id = :marque_id
                AND rma_supprime_le IS NULL
                AND rma_archive_le IS NULL
                AND (rma_termine_le IS NULL OR rma_termine_le >= CURDATE())
              LIMIT 1",
            ['concession_id' => $concessionId, 'marque_id' => $marqueId]
        );
    }

    public function validerService(int $utilisateurId, int $societeId, int $serviceId): bool
    {
        return (bool)$this->db->fetchColumn(
            "SELECT 1 FROM sav_utilisateurs_services
              WHERE usv_utilisateur_id = :user_id AND usv_societe_id = :societe_id AND usv_service_id = :service_id
                AND usv_supprime_le IS NULL AND usv_archive_le IS NULL
                AND (usv_termine_le IS NULL OR usv_termine_le >= CURDATE())
              LIMIT 1",
            ['user_id' => $utilisateurId, 'societe_id' => $societeId, 'service_id' => $serviceId]
        );
    }

    public function validerEquipe(int $utilisateurId, int $societeId, int $equipeId, ?int $serviceId = null): bool
    {
        $params = ['user_id' => $utilisateurId, 'societe_id' => $societeId, 'equipe_id' => $equipeId];
        $join = '';
        $where = '';
        if ($serviceId) {
            $join = " INNER JOIN sav_equipes_services eqs ON eqs.eqs_equipe_id = ueq.ueq_equipe_id
                AND eqs.eqs_societe_id = ueq.ueq_societe_id
                AND eqs.eqs_supprime_le IS NULL AND eqs.eqs_archive_le IS NULL
                AND (eqs.eqs_termine_le IS NULL OR eqs.eqs_termine_le >= CURDATE())";
            $where = ' AND eqs.eqs_service_id = :service_id';
            $params['service_id'] = $serviceId;
        }
        return (bool)$this->db->fetchColumn(
            "SELECT 1 FROM sav_utilisateurs_equipes ueq {$join}
              WHERE ueq.ueq_utilisateur_id = :user_id AND ueq.ueq_societe_id = :societe_id AND ueq.ueq_equipe_id = :equipe_id
                AND ueq.ueq_supprime_le IS NULL AND ueq.ueq_archive_le IS NULL
                AND (ueq.ueq_termine_le IS NULL OR ueq.ueq_termine_le >= CURDATE())
                {$where}
              LIMIT 1",
            $params
        );
    }

    public function mettreAJourUtilisateur(int $utilisateurId, ?int $societeId, ?int $marqueId): void
    {
        $this->db->execute(
            "UPDATE sav_utilisateurs
                SET uti_societe_active_id = :societe_id,
                    uti_marque_active_id = :marque_id,
                    uti_modifie_le = NOW()
              WHERE uti_id = :user_id",
            ['user_id' => $utilisateurId, 'societe_id' => $societeId, 'marque_id' => $marqueId]
        );
    }

    public function mettreAJourSessionPersistante(?int $sessionId, array $contexte): void
    {
        if (!$sessionId) {
            return;
        }
        $this->db->execute(
            "UPDATE sav_sessions_utilisateurs
                SET seu_societe_active_id = :societe_id,
                    seu_concession_active_id = :concession_id,
                    seu_marque_active_id = :marque_id,
                    seu_service_actif_id = :service_id,
                    seu_equipe_active_id = :equipe_id,
                    seu_derniere_activite_le = NOW(),
                    seu_modifie_le = NOW()
              WHERE seu_id = :session_id
                AND seu_revoquee_le IS NULL
                AND seu_supprime_le IS NULL",
            [
                'session_id' => $sessionId,
                'societe_id' => $contexte['societe_id'] ?? null,
                'concession_id' => $contexte['concession_id'] ?? null,
                'marque_id' => $contexte['marque_id'] ?? null,
                'service_id' => $contexte['service_id'] ?? null,
                'equipe_id' => $contexte['equipe_id'] ?? null,
            ]
        );
    }

    public function journaliserContexte(int $utilisateurId, array $contexte, string $action = 'contexte.modifie'): void
    {
        $this->db->execute(
            "INSERT INTO sav_historique_contextes_utilisateurs
                (hcu_utilisateur_id, hcu_session_utilisateur_id, hcu_societe_id, hcu_concession_id, hcu_marque_id,
                 hcu_service_id, hcu_equipe_id, hcu_action, hcu_metadata_json, hcu_adresse_ip, hcu_user_agent, hcu_cree_le)
             VALUES
                (:user_id, :session_id, :societe_id, :concession_id, :marque_id,
                 :service_id, :equipe_id, :action, :metadata, INET6_ATON(:ip), :ua, NOW())",
            [
                'user_id' => $utilisateurId,
                'session_id' => $contexte['session_id'] ?? null,
                'societe_id' => $contexte['societe_id'] ?? null,
                'concession_id' => $contexte['concession_id'] ?? null,
                'marque_id' => $contexte['marque_id'] ?? null,
                'service_id' => $contexte['service_id'] ?? null,
                'equipe_id' => $contexte['equipe_id'] ?? null,
                'action' => $action,
                'metadata' => json_encode($contexte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ip' => function_exists('client_ip') ? client_ip() : '0.0.0.0',
                'ua' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]
        );
    }
}
