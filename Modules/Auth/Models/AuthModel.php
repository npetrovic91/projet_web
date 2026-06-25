<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Models;

use Nenad\Autosav\Core\Database\Database;
use PDO;

/**
 * Module Auth — modèle aligné sur la base SQL actuelle.
 *
 * Tables utilisées :
 * - sav_utilisateurs
 * - sav_profils_utilisateurs
 * - sav_parametres_securite_utilisateurs
 * - sav_adhesions_utilisateurs_societes
 * - sav_societes
 * - sav_espaces_applicatifs
 * - sav_abonnements_societes
 * - sav_roles_contextuels_utilisateurs
 * - sav_roles
 * - sav_permissions
 * - sav_roles_permissions
 * - sav_sessions_utilisateurs
 * - sav_tentatives_connexion
 * - sav_historique_contextes_utilisateurs
 * - sav_acceptations_documents_juridiques_utilisateurs
 */
class AuthModel
{
    private Database $db;
    private PDO $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->pdo = $this->db->getPdo();
    }

    public function trouverUtilisateurParIdentifiant(string $identifiant): ?array
    {
        $identifiant = trim($identifiant);
        $emailNormalise = self::normaliserEmail($identifiant);

        $sql = "SELECT
                    u.*,
                    p.pui_prenom,
                    p.pui_nom,
                    p.pui_mobile,
                    p.pui_telephone,
                    psu.psu_2fa_active,
                    psu.psu_2fa_methode,
                    st.sta_code AS statut_code,
                    st.sta_libelle AS statut_libelle,
                    s.soc_nom AS societe_active_nom,
                    s.soc_code AS societe_active_code
                FROM sav_utilisateurs u
                LEFT JOIN sav_profils_utilisateurs p
                       ON p.pui_utilisateur_id = u.uti_id
                      AND p.pui_supprime_le IS NULL
                LEFT JOIN sav_parametres_securite_utilisateurs psu
                       ON psu.psu_utilisateur_id = u.uti_id
                      AND psu.psu_supprime_le IS NULL
                LEFT JOIN sav_statuts st ON st.sta_id = u.uti_statut_id
                LEFT JOIN sav_societes s ON s.soc_id = u.uti_societe_active_id
                WHERE u.uti_supprime_le IS NULL
                  AND u.uti_anonymise_le IS NULL
                  AND (
                        u.uti_email_normalise = :email
                     OR u.uti_email = :identifiant_email
                     OR u.uti_identifiant = :identifiant_login
                  )
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':email', $emailNormalise);
        $stmt->bindValue(':identifiant_email', $identifiant);
        $stmt->bindValue(':identifiant_login', $identifiant);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function trouverUtilisateurParId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.*, p.pui_prenom, p.pui_nom, p.pui_mobile, p.pui_telephone,
                    psu.psu_2fa_active, psu.psu_2fa_methode,
                    st.sta_code AS statut_code, st.sta_libelle AS statut_libelle
               FROM sav_utilisateurs u
          LEFT JOIN sav_profils_utilisateurs p
                 ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
          LEFT JOIN sav_parametres_securite_utilisateurs psu
                 ON psu.psu_utilisateur_id = u.uti_id AND psu.psu_supprime_le IS NULL
          LEFT JOIN sav_statuts st ON st.sta_id = u.uti_statut_id
              WHERE u.uti_id = :id AND u.uti_supprime_le IS NULL
              LIMIT 1"
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listerSocietesAccessibles(int $utilisateurId): array
    {
        $sql = "SELECT
                    s.soc_id,
                    s.soc_uuid,
                    s.soc_code,
                    s.soc_nom,
                    s.soc_nom_court,
                    s.soc_email,
                    s.soc_est_holding,
                    s.soc_societe_parente_id,
                    s.soc_holding_id,
                    aus.aus_debute_le,
                    aus.aus_termine_le,
                    aus.aus_statut_id,
                    eap.eap_id AS espace_applicatif_id,
                    eap.eap_statut_id AS espace_statut_id,
                    eap.eap_bloque_le,
                    abo.abo_id AS abonnement_societe_id,
                    abo.abo_statut_abonnement_id,
                    abo.abo_statut_paiement_id
                FROM sav_adhesions_utilisateurs_societes aus
                INNER JOIN sav_societes s ON s.soc_id = aus.aus_societe_id
                INNER JOIN sav_statuts st_aus
                        ON st_aus.sta_id = aus.aus_statut_id
                       AND st_aus.sta_domaine = 'general'
                       AND st_aus.sta_code = 'actif'
                INNER JOIN sav_statuts st_soc
                        ON st_soc.sta_id = s.soc_statut_id
                       AND st_soc.sta_domaine = 'general'
                       AND st_soc.sta_code = 'actif'
                INNER JOIN sav_espaces_applicatifs eap
                       ON eap.eap_societe_id = s.soc_id
                      AND eap.eap_supprime_le IS NULL
                      AND eap.eap_bloque_le IS NULL
                INNER JOIN sav_statuts st_eap
                        ON st_eap.sta_id = eap.eap_statut_id
                       AND st_eap.sta_domaine = 'general'
                       AND st_eap.sta_code = 'actif'
                INNER JOIN sav_abonnements_societes abo
                       ON abo.abo_id = eap.eap_abonnement_societe_id
                      AND abo.abo_societe_id = s.soc_id
                      AND abo.abo_supprime_le IS NULL
                      AND abo.abo_archive_le IS NULL
                      AND (abo.abo_debute_le IS NULL OR abo.abo_debute_le <= NOW())
                      AND (abo.abo_termine_le IS NULL OR abo.abo_termine_le >= NOW())
                INNER JOIN sav_statuts st_abo
                        ON st_abo.sta_id = abo.abo_statut_abonnement_id
                       AND st_abo.sta_domaine = 'abonnement'
                       AND st_abo.sta_code IN ('actif', 'essai')
                INNER JOIN sav_statuts st_pay
                        ON st_pay.sta_id = abo.abo_statut_paiement_id
                       AND st_pay.sta_domaine = 'paiement'
                       AND st_pay.sta_code = 'a_jour'
                WHERE aus.aus_utilisateur_id = :id
                  AND aus.aus_supprime_le IS NULL
                  AND aus.aus_archive_le IS NULL
                  AND (aus.aus_debute_le IS NULL OR aus.aus_debute_le <= NOW())
                  AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= NOW())
                  AND s.soc_supprime_le IS NULL
                  AND s.soc_archive_le IS NULL
                ORDER BY s.soc_nom ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $utilisateurId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function trouverSociete(int $societeId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM sav_societes
              WHERE soc_id = :id AND soc_supprime_le IS NULL AND soc_archive_le IS NULL
              LIMIT 1"
        );
        $stmt->bindValue(':id', $societeId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listerMarquesRepresentees(?int $concessionSocieteId): array
    {
        if (!$concessionSocieteId) {
            return [];
        }

        $sql = "SELECT DISTINCT
                    m.soc_id,
                    m.soc_code,
                    m.soc_nom,
                    r.rma_importateur_societe_id,
                    r.rma_constructeur_societe_id
                FROM sav_representations_marques_societes r
                INNER JOIN sav_societes m ON m.soc_id = r.rma_marque_societe_id
                WHERE r.rma_concession_societe_id = :concession_id
                  AND r.rma_supprime_le IS NULL
                  AND r.rma_archive_le IS NULL
                  AND (r.rma_termine_le IS NULL OR r.rma_termine_le >= CURDATE())
                  AND m.soc_supprime_le IS NULL
                  AND m.soc_archive_le IS NULL
                ORDER BY m.soc_nom ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':concession_id', $concessionSocieteId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listerRoles(int $utilisateurId, ?int $societeId = null, ?int $moduleId = null, ?int $marqueId = null): array
    {
        [$scopeSql, $params] = $this->scopeRolesSql($utilisateurId, $societeId, $moduleId, $marqueId);
        $sql = "SELECT DISTINCT
                    rcu.rcu_id,
                    rcu.rcu_societe_id,
                    rcu.rcu_marque_societe_id,
                    rcu.rcu_concession_societe_id,
                    rcu.rcu_departement_id,
                    rcu.rcu_secteur_id,
                    rcu.rcu_service_id,
                    rcu.rcu_equipe_id,
                    rcu.rcu_module_id,
                    r.rol_id,
                    r.rol_code,
                    r.rol_nom
                FROM sav_roles_contextuels_utilisateurs rcu
                INNER JOIN sav_roles r ON r.rol_id = rcu.rcu_role_id
                WHERE {$scopeSql}
                  AND rcu.rcu_supprime_le IS NULL
                  AND rcu.rcu_archive_le IS NULL
                  AND (rcu.rcu_termine_le IS NULL OR rcu.rcu_termine_le >= CURDATE())
                  AND r.rol_supprime_le IS NULL
                  AND r.rol_archive_le IS NULL
                ORDER BY r.rol_nom ASC";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listerPermissions(int $utilisateurId, ?int $societeId = null, ?int $moduleId = null, ?int $marqueId = null): array
    {
        [$scopeSql, $params] = $this->scopeRolesSql($utilisateurId, $societeId, $moduleId, $marqueId);

        $sql = "SELECT DISTINCT
                    p.per_code,
                    rpe.rpe_effet
                FROM sav_roles_contextuels_utilisateurs rcu
                INNER JOIN sav_roles r ON r.rol_id = rcu.rcu_role_id
                INNER JOIN sav_roles_permissions rpe ON rpe.rpe_role_id = r.rol_id
                INNER JOIN sav_permissions p ON p.per_id = rpe.rpe_permission_id
                WHERE {$scopeSql}
                  AND rcu.rcu_supprime_le IS NULL
                  AND rcu.rcu_archive_le IS NULL
                  AND (rcu.rcu_termine_le IS NULL OR rcu.rcu_termine_le >= CURDATE())
                  AND r.rol_supprime_le IS NULL
                  AND r.rol_archive_le IS NULL
                  AND rpe.rpe_supprime_le IS NULL
                  AND p.per_supprime_le IS NULL
                  AND p.per_archive_le IS NULL";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $allowed = [];
        $denied = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $code = (string) ($row['per_code'] ?? '');
            if ($code === '') {
                continue;
            }
            if (($row['rpe_effet'] ?? '') === 'refuser') {
                $denied[$code] = true;
                unset($allowed[$code]);
                continue;
            }
            if (!isset($denied[$code])) {
                $allowed[$code] = true;
            }
        }

        return array_values(array_keys($allowed));
    }

    public function enregistrerTentativeConnexion(?int $utilisateurId, string $email, bool $succes, ?string $raisonEchec = null): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO sav_tentatives_connexion
                (tcn_utilisateur_id, tcn_email_tente, tcn_email_normalise, tcn_adresse_ip, tcn_user_agent, tcn_succes, tcn_raison_echec, tcn_cree_le)
             VALUES
                (:user_id, :email, :email_norm, :ip, :ua, :succes, :raison, NOW())"
        );
        $stmt->bindValue(':user_id', $utilisateurId, $utilisateurId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':email', $email !== '' ? $email : null);
        $stmt->bindValue(':email_norm', $email !== '' ? self::normaliserEmail($email) : null);
        $stmt->bindValue(':ip', $this->ipBinaire(), PDO::PARAM_LOB);
        $stmt->bindValue(':ua', substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255));
        $stmt->bindValue(':succes', $succes ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':raison', $raisonEchec);
        $stmt->execute();
    }

    public function enregistrerEchecUtilisateur(int $utilisateurId, string $raison = 'identifiants_invalides'): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE sav_utilisateurs
                SET uti_echecs_connexion = uti_echecs_connexion + 1,
                    uti_modifie_le = NOW(),
                    uti_est_verrouille = CASE WHEN uti_echecs_connexion + 1 >= :max_lock THEN 1 ELSE uti_est_verrouille END,
                    uti_verrouille_jusqua = CASE WHEN uti_echecs_connexion + 1 >= :max_until THEN DATE_ADD(NOW(), INTERVAL :minutes MINUTE) ELSE uti_verrouille_jusqua END,
                    uti_motif_verrouillage = CASE WHEN uti_echecs_connexion + 1 >= :max_reason THEN :raison ELSE uti_motif_verrouillage END
              WHERE uti_id = :id"
        );
        $maxAttempts = defined('AUTH_MAX_EMAIL_ATTEMPTS') ? (int) AUTH_MAX_EMAIL_ATTEMPTS : 10;
        $stmt->bindValue(':max_lock', $maxAttempts, PDO::PARAM_INT);
        $stmt->bindValue(':max_until', $maxAttempts, PDO::PARAM_INT);
        $stmt->bindValue(':max_reason', $maxAttempts, PDO::PARAM_INT);
        $stmt->bindValue(':minutes', defined('AUTH_EMAIL_BLOCK_DURATION_MINUTES') ? (int) AUTH_EMAIL_BLOCK_DURATION_MINUTES : 120, PDO::PARAM_INT);
        $stmt->bindValue(':raison', $raison);
        $stmt->bindValue(':id', $utilisateurId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function enregistrerSuccesUtilisateur(int $utilisateurId, ?int $societeId, ?int $marqueId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE sav_utilisateurs
                SET uti_derniere_connexion_le = NOW(),
                    uti_derniere_connexion_ip = :ip,
                    uti_dernier_user_agent = :ua,
                    uti_echecs_connexion = 0,
                    uti_est_verrouille = 0,
                    uti_verrouille_jusqua = NULL,
                    uti_motif_verrouillage = NULL,
                    uti_societe_active_id = :societe_id,
                    uti_marque_active_id = :marque_id,
                    uti_modifie_le = NOW()
              WHERE uti_id = :id"
        );
        $stmt->bindValue(':ip', $this->ipBinaire(), PDO::PARAM_LOB);
        $stmt->bindValue(':ua', substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255));
        $stmt->bindValue(':societe_id', $societeId, $societeId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':marque_id', $marqueId, $marqueId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $utilisateurId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function creerSessionPersistante(int $utilisateurId, array $contexte): int
    {
        $sessionHash = hash('sha256', session_id() ?: bin2hex(random_bytes(16)));
        $expireLe = (new \DateTimeImmutable('now'))->modify('+' . (defined('SESSION_LIFETIME_MINUTES') ? (int) SESSION_LIFETIME_MINUTES : 30) . ' minutes');

        $stmt = $this->pdo->prepare(
            "INSERT INTO sav_sessions_utilisateurs
                (seu_utilisateur_id, seu_identifiant_session_hash, seu_societe_active_id, seu_concession_active_id, seu_marque_active_id,
                 seu_service_actif_id, seu_equipe_active_id, seu_adresse_ip, seu_user_agent, seu_derniere_activite_le, seu_expire_le, seu_cree_le)
             VALUES
                (:user_id, :hash, :societe_id, :concession_id, :marque_id, :service_id, :equipe_id, :ip, :ua, NOW(), :expire_le, NOW())"
        );
        $stmt->bindValue(':user_id', $utilisateurId, PDO::PARAM_INT);
        $stmt->bindValue(':hash', $sessionHash);
        $stmt->bindValue(':societe_id', $contexte['societe_id'] ?? null, !empty($contexte['societe_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':concession_id', $contexte['concession_id'] ?? null, !empty($contexte['concession_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':marque_id', $contexte['marque_id'] ?? null, !empty($contexte['marque_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':service_id', $contexte['service_id'] ?? null, !empty($contexte['service_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':equipe_id', $contexte['equipe_id'] ?? null, !empty($contexte['equipe_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':ip', $this->ipBinaire(), PDO::PARAM_LOB);
        $stmt->bindValue(':ua', substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255));
        $stmt->bindValue(':expire_le', $expireLe->format('Y-m-d H:i:s'));
        $stmt->execute();

        return (int) $this->pdo->lastInsertId();
    }

    public function actualiserSessionPersistante(?int $sessionId, array $contexte = []): void
    {
        if (!$sessionId) {
            return;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE sav_sessions_utilisateurs
                SET seu_derniere_activite_le = NOW(),
                    seu_expire_le = DATE_ADD(NOW(), INTERVAL :minutes MINUTE),
                    seu_societe_active_id = COALESCE(:societe_id, seu_societe_active_id),
                    seu_concession_active_id = COALESCE(:concession_id, seu_concession_active_id),
                    seu_marque_active_id = COALESCE(:marque_id, seu_marque_active_id),
                    seu_service_actif_id = COALESCE(:service_id, seu_service_actif_id),
                    seu_equipe_active_id = COALESCE(:equipe_id, seu_equipe_active_id)
              WHERE seu_id = :session_id
                AND seu_revoquee_le IS NULL
                AND seu_supprime_le IS NULL"
        );
        $stmt->bindValue(':minutes', defined('SESSION_LIFETIME_MINUTES') ? (int) SESSION_LIFETIME_MINUTES : 30, PDO::PARAM_INT);
        $stmt->bindValue(':societe_id', $contexte['societe_id'] ?? null, !empty($contexte['societe_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':concession_id', $contexte['concession_id'] ?? null, !empty($contexte['concession_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':marque_id', $contexte['marque_id'] ?? null, !empty($contexte['marque_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':service_id', $contexte['service_id'] ?? null, !empty($contexte['service_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':equipe_id', $contexte['equipe_id'] ?? null, !empty($contexte['equipe_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function revoquerSessionPersistante(?int $sessionId, string $motif = 'logout'): void
    {
        if (!$sessionId) {
            return;
        }

        $stmt = $this->pdo->prepare(
            "UPDATE sav_sessions_utilisateurs
                SET seu_revoquee_le = NOW(), seu_motif_revocation = :motif, seu_modifie_le = NOW()
              WHERE seu_id = :session_id AND seu_revoquee_le IS NULL"
        );
        $stmt->bindValue(':motif', $motif);
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function journaliserContexte(int $utilisateurId, array $contexte, string $action): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO sav_historique_contextes_utilisateurs
                (hcu_utilisateur_id, hcu_session_utilisateur_id, hcu_societe_id, hcu_concession_id, hcu_marque_id,
                 hcu_service_id, hcu_equipe_id, hcu_action, hcu_metadata_json, hcu_adresse_ip, hcu_user_agent, hcu_cree_le)
             VALUES
                (:user_id, :session_id, :societe_id, :concession_id, :marque_id, :service_id, :equipe_id, :action, :meta, :ip, :ua, NOW())"
        );
        $stmt->bindValue(':user_id', $utilisateurId, PDO::PARAM_INT);
        $stmt->bindValue(':session_id', $contexte['session_id'] ?? null, !empty($contexte['session_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':societe_id', $contexte['societe_id'] ?? null, !empty($contexte['societe_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':concession_id', $contexte['concession_id'] ?? null, !empty($contexte['concession_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':marque_id', $contexte['marque_id'] ?? null, !empty($contexte['marque_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':service_id', $contexte['service_id'] ?? null, !empty($contexte['service_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':equipe_id', $contexte['equipe_id'] ?? null, !empty($contexte['equipe_id']) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':meta', json_encode($contexte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $stmt->bindValue(':ip', $this->ipBinaire(), PDO::PARAM_LOB);
        $stmt->bindValue(':ua', substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255));
        $stmt->execute();
    }

    public function trouverTokenSecurite(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $stmt = $this->pdo->prepare(
            "SELECT psu.*, u.uti_id, u.uti_email, u.uti_email_normalise
               FROM sav_parametres_securite_utilisateurs psu
               INNER JOIN sav_utilisateurs u ON u.uti_id = psu.psu_utilisateur_id
              WHERE psu.psu_jeton_verification_email_hash = :hash
                AND psu.psu_supprime_le IS NULL
                AND u.uti_supprime_le IS NULL
              LIMIT 1"
        );
        $stmt->bindValue(':hash', $hash);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function stockerTokenSecurite(int $utilisateurId, string $token): void
    {
        $hash = hash('sha256', $token);
        $stmt = $this->pdo->prepare(
            "INSERT INTO sav_parametres_securite_utilisateurs
                (psu_utilisateur_id, psu_jeton_verification_email_hash, psu_verification_email_envoyee_le, psu_cree_le)
             VALUES (:id, :hash, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                psu_jeton_verification_email_hash = VALUES(psu_jeton_verification_email_hash),
                psu_verification_email_envoyee_le = NOW(),
                psu_modifie_le = NOW()"
        );
        $stmt->bindValue(':id', $utilisateurId, PDO::PARAM_INT);
        $stmt->bindValue(':hash', $hash);
        $stmt->execute();
    }

    public function marquerEmailVerifie(int $utilisateurId): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE sav_utilisateurs SET uti_email_verifie_le = NOW(), uti_modifie_le = NOW() WHERE uti_id = :id");
            $stmt->bindValue(':id', $utilisateurId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare("UPDATE sav_parametres_securite_utilisateurs SET psu_jeton_verification_email_hash = NULL, psu_modifie_le = NOW() WHERE psu_utilisateur_id = :id");
            $stmt->bindValue(':id', $utilisateurId, PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function mettreAJourMotDePasse(int $utilisateurId, string $hash): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE sav_utilisateurs
                    SET uti_mot_de_passe_hash = :hash,
                        uti_mot_de_passe_modifie_le = NOW(),
                        uti_doit_changer_mot_de_passe = 0,
                        uti_echecs_connexion = 0,
                        uti_est_verrouille = 0,
                        uti_verrouille_jusqua = NULL,
                        uti_motif_verrouillage = NULL,
                        uti_modifie_le = NOW()
                  WHERE uti_id = :id"
            );
            $stmt->bindValue(':hash', $hash);
            $stmt->bindValue(':id', $utilisateurId, PDO::PARAM_INT);
            $stmt->execute();

            $stmt = $this->pdo->prepare(
                "UPDATE sav_parametres_securite_utilisateurs
                    SET psu_jeton_verification_email_hash = NULL,
                        psu_modifie_le = NOW()
                  WHERE psu_utilisateur_id = :id"
            );
            $stmt->bindValue(':id', $utilisateurId, PDO::PARAM_INT);
            $stmt->execute();

            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function trouverDernierDocumentJuridique(string $type = 'cgu'): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
               FROM sav_documents_juridiques
              WHERE dju_supprime_le IS NULL
                AND dju_archive_le IS NULL
                AND LOWER(dju_type_document) = LOWER(:type)
              ORDER BY dju_valide_du DESC, dju_cree_le DESC
              LIMIT 1"
        );
        $stmt->bindValue(':type', $type);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function enregistrerAcceptationDocument(int $utilisateurId, int $documentId, string $version): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO sav_acceptations_documents_juridiques_utilisateurs
                (adj_utilisateur_id, adj_document_juridique_id, adj_version, adj_accepte_le, adj_adresse_ip, adj_user_agent, adj_cree_le)
             VALUES
                (:user_id, :document_id, :version, NOW(), :ip, :ua, NOW())"
        );
        $stmt->bindValue(':user_id', $utilisateurId, PDO::PARAM_INT);
        $stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
        $stmt->bindValue(':version', $version);
        $stmt->bindValue(':ip', $this->ipBinaire(), PDO::PARAM_LOB);
        $stmt->bindValue(':ua', substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255));
        $stmt->execute();
    }

    public function aAccepteDocument(int $utilisateurId, int $documentId, string $version): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1
               FROM sav_acceptations_documents_juridiques_utilisateurs
              WHERE adj_utilisateur_id = :user_id
                AND adj_document_juridique_id = :document_id
                AND adj_version = :version
              LIMIT 1"
        );
        $stmt->bindValue(':user_id', $utilisateurId, PDO::PARAM_INT);
        $stmt->bindValue(':document_id', $documentId, PDO::PARAM_INT);
        $stmt->bindValue(':version', $version);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    private function scopeRolesSql(int $utilisateurId, ?int $societeId, ?int $moduleId, ?int $marqueId = null): array
    {
        $params = ['user_id' => $utilisateurId];
        $scope = ['rcu.rcu_utilisateur_id = :user_id'];

        if ($societeId !== null && $societeId > 0) {
            $scope[] = '(rcu.rcu_societe_id = :societe_id OR rcu.rcu_societe_id IS NULL)';
            $params['societe_id'] = $societeId;
        }
        if ($moduleId !== null && $moduleId > 0) {
            $scope[] = '(rcu.rcu_module_id = :module_id OR rcu.rcu_module_id IS NULL)';
            $params['module_id'] = $moduleId;
        }
        if ($marqueId !== null && $marqueId > 0) {
            $scope[] = '(rcu.rcu_marque_societe_id = :marque_id OR rcu.rcu_marque_societe_id IS NULL)';
            $params['marque_id'] = $marqueId;
        } else {
            $scope[] = 'rcu.rcu_marque_societe_id IS NULL';
        }

        return [implode(' AND ', $scope), $params];
    }

    private function ipBinaire(): ?string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if ($ip === '') {
            return null;
        }
        $packed = @inet_pton($ip);
        return $packed === false ? null : $packed;
    }

    public static function normaliserEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
