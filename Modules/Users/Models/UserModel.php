<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Models;

use Nenad\Autosav\Core\Database\Database;
use PDO;

/**
 * Module Users — modèle aligné sur la base SQL actuelle.
 *
 * Tables de référence :
 * - sav_utilisateurs
 * - sav_profils_utilisateurs
 * - sav_parametres_securite_utilisateurs
 * - sav_adhesions_utilisateurs_societes
 * - sav_roles_contextuels_utilisateurs
 * - sav_fonctions_utilisateurs
 * - sav_hierarchie_utilisateurs
 */
class UserModel
{
    private Database $db;
    private PDO $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
        $this->pdo = $this->db->getPdo();
    }

    /**
     * AUDIT 2026-06-21 — correctif point 4.5 :
     * sav_journaux_audit existait et était déjà utilisé par 19 autres modules
     * (Notes, Organisation, Validation, etc.) mais jamais par Users/Roles/Society,
     * alors que le cahier des charges impose explicitement la journalisation de
     * "création utilisateur, modification utilisateur, désactivation utilisateur,
     * changement de rôle, changement de fonction". Même convention que les
     * modules existants (ex. Modules/Notes/Models/NotesModel.php::audit()).
     *
     * @param array<string,mixed> $meta Ne jamais y placer de mot de passe, hash,
     *                                  jeton ou autre donnée sensible — seulement
     *                                  des identifiants et noms de champs modifiés.
     */
    private function audit(?int $userId, string $action, int $targetId, array $meta = [], ?string $reason = null): void
    {
        try {
            $this->db->execute(
                "INSERT INTO sav_journaux_audit
                    (jau_utilisateur_id, jau_societe_id, jau_action, jau_table_cible, jau_id_cible,
                     jau_raison, jau_adresse_ip, jau_user_agent, jau_metadata_json, jau_cree_le)
                 VALUES
                    (:user_id, :societe_id, :action, 'sav_utilisateurs', :id_cible,
                     :raison, INET6_ATON(:ip), :user_agent, :metadata, NOW())",
                [
                    'user_id' => $userId ?: null,
                    'societe_id' => $_SESSION['active_company_id'] ?? ($_SESSION['user']['actual_society_id'] ?? null),
                    'action' => $action,
                    'id_cible' => $targetId,
                    'raison' => $reason,
                    'ip' => function_exists('client_ip') ? client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'),
                    'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                    'metadata' => json_encode(['module' => 'Users'] + $meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable $e) {
            // L'audit ne doit jamais faire échouer l'opération métier elle-même.
            // CORRECTIF (section 3 du roadmap, "échecs d'audit avalés
            // silencieusement") : un échec d'écriture dans sav_journaux_audit
            // est un incident d'intégrité (perte de traçabilité), pas un
            // évènement d'audit comme un autre — il était noyé dans le canal
            // 'audit' parmi des milliers d'entrées de succès routinières. Il
            // est désormais aussi journalisé en 'critical' sur le canal
            // 'error', celui que surveillent RUNBOOK_DEPLOIEMENT.md
            // ("make logs-error") et l'équipe en priorité.
            if (function_exists('logger')) {
                $context = ['action' => $action, 'target_id' => $targetId, 'error' => $e->getMessage()];
                logger('audit')->error('Échec écriture sav_journaux_audit (Users)', $context);
                logger('error')->critical('Échec écriture sav_journaux_audit (Users) — intégrité de la piste d\'audit compromise', $context);
            }
        }
    }

    /** Retire les clés sensibles avant d'écrire des noms de champs dans jau_metadata_json. */
    private function auditableKeys(array $data): array
    {
        return array_values(array_filter(array_keys($data), static function (string $key): bool {
            $lower = mb_strtolower($key);
            return !str_contains($lower, 'mot_de_passe')
                && !str_contains($lower, 'password')
                && !str_contains($lower, 'hash')
                && !str_contains($lower, 'token')
                && !str_contains($lower, 'jeton')
                && !str_contains($lower, 'secret');
        }));
    }

    public function paginer(array $filtres = [], int $page = 1, int $parPage = 25, ?array $societesAutorisees = null): array
    {
        $page = max(1, $page);
        $parPage = max(1, min(100, $parPage));
        $offset = ($page - 1) * $parPage;

        [$where, $params] = $this->buildWhere($filtres, $societesAutorisees);

        $sqlCount = "SELECT COUNT(DISTINCT u.uti_id)
                     FROM sav_utilisateurs u
                     LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
                     LEFT JOIN sav_statuts st ON st.sta_id = u.uti_statut_id
                     WHERE {$where}";
        $stmt = $this->pdo->prepare($sqlCount);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        $total = (int) $stmt->fetchColumn();

        $sql = "SELECT
                    u.uti_id,
                    u.uti_uuid,
                    u.uti_identifiant,
                    u.uti_email,
                    u.uti_email_normalise,
                    u.uti_email_verifie_le,
                    u.uti_doit_changer_mot_de_passe,
                    u.uti_est_systeme,
                    u.uti_est_verrouille,
                    u.uti_societe_active_id,
                    u.uti_marque_active_id,
                    u.uti_derniere_connexion_le,
                    u.uti_echecs_connexion,
                    u.uti_cree_le,
                    u.uti_modifie_le,
                    st.sta_code AS statut_code,
                    st.sta_libelle AS statut_libelle,
                    p.pui_prenom,
                    p.pui_nom,
                    p.pui_telephone,
                    p.pui_mobile,
                    s.soc_nom AS societe_active_nom,
                    s.soc_code AS societe_active_code,
                    (SELECT GROUP_CONCAT(DISTINCT so.soc_nom ORDER BY so.soc_nom SEPARATOR ', ')
                       FROM sav_adhesions_utilisateurs_societes aus
                       INNER JOIN sav_societes so ON so.soc_id = aus.aus_societe_id
                      WHERE aus.aus_utilisateur_id = u.uti_id
                        AND aus.aus_supprime_le IS NULL
                        AND aus.aus_archive_le IS NULL
                        AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= CURDATE())) AS societes_noms,
                    (SELECT GROUP_CONCAT(DISTINCT r.rol_nom ORDER BY r.rol_nom SEPARATOR ', ')
                       FROM sav_roles_contextuels_utilisateurs rcu
                       INNER JOIN sav_roles r ON r.rol_id = rcu.rcu_role_id
                      WHERE rcu.rcu_utilisateur_id = u.uti_id
                        AND rcu.rcu_supprime_le IS NULL
                        AND rcu.rcu_archive_le IS NULL
                        AND (rcu.rcu_termine_le IS NULL OR rcu.rcu_termine_le >= CURDATE())) AS roles_noms
                FROM sav_utilisateurs u
                LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
                LEFT JOIN sav_statuts st ON st.sta_id = u.uti_statut_id
                LEFT JOIN sav_societes s ON s.soc_id = u.uti_societe_active_id
                WHERE {$where}
                ORDER BY p.pui_nom ASC, p.pui_prenom ASC, u.uti_email ASC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->bindValue(':limit', $parPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = array_map([$this, 'avecAliasCompatibilite'], $stmt->fetchAll(PDO::FETCH_ASSOC));

        return [
            'users' => $rows,
            'utilisateurs' => $rows,
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $parPage,
            'pages' => (int) max(1, ceil($total / $parPage)),
        ];
    }

    public function trouver(int $id): ?array
    {
        $sql = "SELECT
                    u.*,
                    st.sta_code AS statut_code,
                    st.sta_libelle AS statut_libelle,
                    p.pui_id,
                    p.pui_nom,
                    p.pui_prenom,
                    p.pui_civilite,
                    p.pui_telephone,
                    p.pui_mobile,
                    p.pui_photo_fichier_id,
                    p.pui_couleur_avatar,
                    p.pui_adresse_rue,
                    p.pui_adresse_ville,
                    p.pui_adresse_code_postal,
                    p.pui_pays_id,
                    p.pui_date_naissance,
                    p.pui_lieu_naissance,
                    dsu.dsu_numero_employe,
                    dsu.dsu_date_embauche,
                    dsu.dsu_date_anciennete,
                    dsu.dsu_type_contrat,
                    psu.psu_2fa_active,
                    psu.psu_2fa_methode
                FROM sav_utilisateurs u
                LEFT JOIN sav_statuts st ON st.sta_id = u.uti_statut_id
                LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
                LEFT JOIN sav_donnees_sensibles_utilisateurs dsu ON dsu.dsu_utilisateur_id = u.uti_id AND dsu.dsu_supprime_le IS NULL
                LEFT JOIN sav_parametres_securite_utilisateurs psu ON psu.psu_utilisateur_id = u.uti_id AND psu.psu_supprime_le IS NULL
                WHERE u.uti_id = :id AND u.uti_supprime_le IS NULL
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->avecAliasCompatibilite($row) : null;
    }



    /** Compatibilité avec les anciens services Profile/GDPR. */
    public function findById(int $id): ?array
    {
        return $this->trouver($id);
    }

    /** Compatibilité avec les anciens services Profile/GDPR. */
    public function existsByEmail(string $email, ?int $exceptId = null): bool
    {
        return $this->emailExiste(mb_strtolower(trim($email)), $exceptId);
    }

    /**
     * Mise à jour de profil issue des anciens écrans Profile.
     * Accepte les clés historiques use_* et les répartit sur sav_utilisateurs + sav_profils_utilisateurs.
     */
    public function updateUser(int $id, array $data, ?int $updatedBy = null): bool
    {
        $compte = [];
        $profil = [];
        if (array_key_exists('use_email', $data)) {
            $email = trim((string) $data['use_email']);
            $compte['uti_email'] = $email;
            $compte['uti_email_normalise'] = mb_strtolower($email);
        }
        if (array_key_exists('use_username', $data)) $compte['uti_identifiant'] = trim((string) $data['use_username']) ?: null;
        if (array_key_exists('use_locale', $data)) $compte['uti_langue'] = trim((string) $data['use_locale']) ?: 'fr';
        if (array_key_exists('use_active_company_id', $data)) $compte['uti_societe_active_id'] = $data['use_active_company_id'] ? (int) $data['use_active_company_id'] : null;
        if (array_key_exists('use_active_brand_id', $data)) $compte['uti_marque_active_id'] = $data['use_active_brand_id'] ? (int) $data['use_active_brand_id'] : null;

        if (array_key_exists('use_firstname', $data)) $profil['pui_prenom'] = trim((string) $data['use_firstname']) ?: null;
        if (array_key_exists('use_lastname', $data)) $profil['pui_nom'] = trim((string) $data['use_lastname']) ?: null;
        if (array_key_exists('use_civility', $data)) $profil['pui_civilite'] = trim((string) $data['use_civility']) ?: null;
        if (array_key_exists('use_phone', $data)) $profil['pui_telephone'] = trim((string) $data['use_phone']) ?: null;
        if (array_key_exists('use_mobile', $data)) $profil['pui_mobile'] = trim((string) $data['use_mobile']) ?: null;
        if (array_key_exists('use_address_street', $data)) $profil['pui_adresse_rue'] = trim((string) $data['use_address_street']) ?: null;
        if (array_key_exists('use_address_city', $data)) $profil['pui_adresse_ville'] = trim((string) $data['use_address_city']) ?: null;
        if (array_key_exists('use_address_zipcode', $data)) $profil['pui_adresse_code_postal'] = trim((string) $data['use_address_zipcode']) ?: null;
        if (array_key_exists('use_birthdate', $data)) $profil['pui_date_naissance'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $data['use_birthdate']) ? $data['use_birthdate'] : null;
        if (array_key_exists('use_birthplace', $data)) $profil['pui_lieu_naissance'] = trim((string) $data['use_birthplace']) ?: null;

        return $this->modifier($id, $compte, $profil, [], [
            'societe_ids' => $this->idsSocietesUtilisateur($id),
            'role_ids' => array_column($this->rolesUtilisateur($id), 'rcu_role_id'),
            'fonction_ids' => array_column($this->fonctionsUtilisateur($id), 'fut_fonction_id'),
            'departement_ids' => array_column($this->departementsUtilisateur($id), 'udp_departement_id'),
            'service_ids' => array_column($this->servicesUtilisateur($id), 'usv_service_id'),
            'equipe_ids' => array_column($this->equipesUtilisateur($id), 'ueq_equipe_id'),
            'competence_assignments' => $this->assignmentsCompetencesUtilisateur($id),
            'certification_assignments' => $this->assignmentsCertificationsUtilisateur($id),
            'societe_principale_id' => (int) ($compte['uti_societe_active_id'] ?? ($this->trouver($id)['uti_societe_active_id'] ?? 0)),
            'manager_user_id' => (int) ($this->managersUtilisateur($id)[0]['hiu_superieur_utilisateur_id'] ?? 0),
            'utilisateur_action_id' => $updatedBy ?? 0,
        ]);
    }

    public function updatePassword(int $id, string $hash): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sav_utilisateurs
             SET uti_mot_de_passe_hash = :hash,
                 uti_mot_de_passe_modifie_le = NOW(),
                 uti_doit_changer_mot_de_passe = 0
             WHERE uti_id = :id AND uti_supprime_le IS NULL'
        );
        $stmt->bindValue(':hash', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function ficheComplete(int $id): ?array
    {
        $utilisateur = $this->trouver($id);
        if (!$utilisateur) {
            return null;
        }
        $societes = $this->societesUtilisateur($id);
        $roles = $this->rolesUtilisateur($id);
        $fonctions = $this->fonctionsUtilisateur($id);
        $departements = $this->departementsUtilisateur($id);
        $services = $this->servicesUtilisateur($id);
        $equipes = $this->equipesUtilisateur($id);
        $competences = $this->competencesUtilisateur($id);
        $certifications = $this->certificationsUtilisateur($id);

        return [
            'user' => $utilisateur,
            'utilisateur' => $utilisateur,
            'companies' => $societes,
            'societes' => $societes,
            'roles' => $roles,
            'functions' => $fonctions,
            'fonctions' => $fonctions,
            'departments' => $departements,
            'departements' => $departements,
            'services' => $services,
            'teams' => $equipes,
            'equipes' => $equipes,
            'skills' => $competences,
            'competences' => $competences,
            'qualifications' => $certifications,
            'certifications' => $certifications,
            'managers' => $this->managersUtilisateur($id),
            'subordinates' => $this->subordonnesUtilisateur($id),
        ];
    }

    public function emailExiste(string $emailNormalise, ?int $saufId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM sav_utilisateurs
                WHERE uti_email_normalise = :email
                  AND uti_supprime_le IS NULL
                  AND uti_anonymise_le IS NULL";
        $params = [':email' => $emailNormalise];
        if ($saufId !== null) {
            $sql .= " AND uti_id <> :id";
            $params[':id'] = $saufId;
        }
        $stmt = $this->pdo->prepare($sql);
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    public function creer(array $compte, array $profil, array $donneesSensibles, array $relations): int
    {
        return $this->transaction(function () use ($compte, $profil, $donneesSensibles, $relations): int {
            $id = $this->insertUtilisateur($compte);
            $this->upsertProfil($id, $profil, (int) ($compte['uti_cree_par_utilisateur_id'] ?? 0));
            $this->upsertParametresSecurite($id, (int) ($compte['uti_cree_par_utilisateur_id'] ?? 0));
            if ($donneesSensibles !== []) {
                $this->upsertDonneesSensibles($id, $donneesSensibles, (int) ($compte['uti_cree_par_utilisateur_id'] ?? 0));
            }
            $this->syncRelations($id, $relations, (int) ($compte['uti_cree_par_utilisateur_id'] ?? 0));

            $this->audit(
                (int) ($compte['uti_cree_par_utilisateur_id'] ?? 0) ?: null,
                'utilisateur.creation',
                $id,
                ['email' => $compte['uti_email'] ?? null, 'champs' => $this->auditableKeys($compte)]
            );

            return $id;
        });
    }

    public function modifier(int $id, array $compte, array $profil, array $donneesSensibles, array $relations): bool
    {
        return $this->transaction(function () use ($id, $compte, $profil, $donneesSensibles, $relations): bool {
            $champsModifies = $this->auditableKeys($compte);
            if ($compte !== []) {
                $sets = [];
                foreach (array_keys($compte) as $key) {
                    $sets[] = "`{$key}` = :{$key}";
                }
                $compte['uti_modifie_par_utilisateur_id'] = $compte['uti_modifie_par_utilisateur_id'] ?? ($relations['utilisateur_action_id'] ?? null);
                if (!isset($compte['uti_modifie_par_utilisateur_id'])) {
                    unset($compte['uti_modifie_par_utilisateur_id']);
                } else {
                    $sets[] = '`uti_modifie_par_utilisateur_id` = :uti_modifie_par_utilisateur_id';
                }
                $compte['id'] = $id;
                $stmt = $this->pdo->prepare('UPDATE sav_utilisateurs SET ' . implode(', ', array_unique($sets)) . ' WHERE uti_id = :id');
                $this->bindParams($stmt, $compte);
                $stmt->execute();
            }
            $this->upsertProfil($id, $profil, (int) ($relations['utilisateur_action_id'] ?? 0));
            if ($donneesSensibles !== []) {
                $this->upsertDonneesSensibles($id, $donneesSensibles, (int) ($relations['utilisateur_action_id'] ?? 0));
            }
            $this->syncRelations($id, $relations, (int) ($relations['utilisateur_action_id'] ?? 0));

            $this->audit(
                (int) ($relations['utilisateur_action_id'] ?? 0) ?: null,
                'utilisateur.modification',
                $id,
                ['champs' => array_values(array_unique(array_merge($champsModifies, array_keys($profil))))]
            );

            return true;
        });
    }

    public function changerStatut(int $id, string $codeStatut, int $utilisateurAction): bool
    {
        $statutId = $this->statutId('utilisateur', $codeStatut);
        $stmt = $this->pdo->prepare(
            "UPDATE sav_utilisateurs
             SET uti_statut_id = :statut,
                 uti_est_verrouille = :verrouille,
                 uti_modifie_par_utilisateur_id = :uid
             WHERE uti_id = :id AND uti_supprime_le IS NULL"
        );
        $stmt->bindValue(':statut', $statutId, PDO::PARAM_INT);
        $stmt->bindValue(':verrouille', in_array($codeStatut, ['suspendu', 'bloque_securite'], true) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $result = $stmt->execute();

        if ($result) {
            // Un blocage de compte doit être pris en compte au plus vite, pas seulement
            // au prochain sondage de AuthMiddleware (voir audit point 4.6).
            $this->pdo->prepare('UPDATE sav_utilisateurs SET uti_acl_version = uti_acl_version + 1 WHERE uti_id = :id')
                ->execute(['id' => $id]);

            $this->audit(
                $utilisateurAction ?: null,
                'utilisateur.changement_statut',
                $id,
                ['nouveau_statut' => $codeStatut]
            );
        }

        return $result;
    }

    public function supprimerLogiquement(int $id, int $utilisateurAction): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE sav_utilisateurs
             SET uti_supprime_le = NOW(),
                 uti_supprime_par_utilisateur_id = :uid,
                 uti_statut_id = :statut
             WHERE uti_id = :id AND uti_supprime_le IS NULL"
        );
        $stmt->bindValue(':uid', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':statut', $this->statutId('utilisateur', 'supprime'), PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $result = $stmt->execute();

        if ($result) {
            $this->audit(
                $utilisateurAction ?: null,
                'utilisateur.desactivation',
                $id,
                [],
                'Suppression logique (uti_supprime_le)'
            );
        }

        return $result;
    }

    public function societesUtilisateur(int $id): array
    {
        return $this->db->fetchAll(
            "SELECT aus.*, s.soc_id, s.soc_code, s.soc_nom, st.sta_code AS statut_code
             FROM sav_adhesions_utilisateurs_societes aus
             INNER JOIN sav_societes s ON s.soc_id = aus.aus_societe_id
             LEFT JOIN sav_statuts st ON st.sta_id = aus.aus_statut_id
             WHERE aus.aus_utilisateur_id = :id
               AND aus.aus_supprime_le IS NULL
               AND aus.aus_archive_le IS NULL
             ORDER BY s.soc_nom ASC",
            ['id' => $id]
        );
    }

    public function rolesUtilisateur(int $id): array
    {
        return $this->db->fetchAll(
            "SELECT rcu.*, r.rol_code, r.rol_nom, s.soc_nom AS societe_nom, m.mod_code
             FROM sav_roles_contextuels_utilisateurs rcu
             INNER JOIN sav_roles r ON r.rol_id = rcu.rcu_role_id
             LEFT JOIN sav_societes s ON s.soc_id = rcu.rcu_societe_id
             LEFT JOIN sav_modules m ON m.mod_id = rcu.rcu_module_id
             WHERE rcu.rcu_utilisateur_id = :id
               AND rcu.rcu_supprime_le IS NULL
               AND rcu.rcu_archive_le IS NULL
             ORDER BY r.rol_nom ASC",
            ['id' => $id]
        );
    }

    public function fonctionsUtilisateur(int $id): array
    {
        return $this->db->fetchAll(
            "SELECT fut.*, f.fon_code, f.fon_nom, s.soc_nom AS societe_nom
             FROM sav_fonctions_utilisateurs fut
             INNER JOIN sav_fonctions f ON f.fon_id = fut.fut_fonction_id
             LEFT JOIN sav_societes s ON s.soc_id = fut.fut_societe_id
             WHERE fut.fut_utilisateur_id = :id
               AND fut.fut_supprime_le IS NULL
               AND fut.fut_archive_le IS NULL
             ORDER BY f.fon_nom ASC",
            ['id' => $id]
        );
    }


    public function departementsUtilisateur(int $id): array
    {
        return $this->db->fetchAll(
            "SELECT udp.*, d.dep_code, d.dep_nom, s.soc_nom AS societe_nom
             FROM sav_utilisateurs_departements udp
             INNER JOIN sav_departements d ON d.dep_id = udp.udp_departement_id
             LEFT JOIN sav_societes s ON s.soc_id = udp.udp_societe_id
             WHERE udp.udp_utilisateur_id = :id
               AND udp.udp_supprime_le IS NULL
               AND udp.udp_archive_le IS NULL
               AND d.dep_supprime_le IS NULL
               AND d.dep_archive_le IS NULL
             ORDER BY d.dep_nom ASC",
            ['id' => $id]
        );
    }

    public function servicesUtilisateur(int $id): array
    {
        return $this->db->fetchAll(
            "SELECT usv.*, srv.srv_code, srv.srv_nom, s.soc_nom AS societe_nom
             FROM sav_utilisateurs_services usv
             INNER JOIN sav_services srv ON srv.srv_id = usv.usv_service_id
             LEFT JOIN sav_societes s ON s.soc_id = usv.usv_societe_id
             WHERE usv.usv_utilisateur_id = :id
               AND usv.usv_supprime_le IS NULL
               AND usv.usv_archive_le IS NULL
               AND srv.srv_supprime_le IS NULL
               AND srv.srv_archive_le IS NULL
             ORDER BY srv.srv_nom ASC",
            ['id' => $id]
        );
    }

    public function equipesUtilisateur(int $id): array
    {
        return $this->db->fetchAll(
            "SELECT ueq.*, equ.equ_code, equ.equ_nom, s.soc_nom AS societe_nom
             FROM sav_utilisateurs_equipes ueq
             INNER JOIN sav_equipes equ ON equ.equ_id = ueq.ueq_equipe_id
             LEFT JOIN sav_societes s ON s.soc_id = ueq.ueq_societe_id
             WHERE ueq.ueq_utilisateur_id = :id
               AND ueq.ueq_supprime_le IS NULL
               AND ueq.ueq_archive_le IS NULL
               AND equ.equ_supprime_le IS NULL
               AND equ.equ_archive_le IS NULL
             ORDER BY equ.equ_nom ASC",
            ['id' => $id]
        );
    }

    public function competencesUtilisateur(int $id): array
    {
        return $this->db->fetchAll(
            "SELECT cut.*, c.cmp_code, c.cmp_nom, n.nco_id, n.nco_code, n.nco_nom, n.nco_rang, s.soc_nom AS societe_nom
             FROM sav_competences_utilisateurs cut
             INNER JOIN sav_competences c ON c.cmp_id = cut.cut_competence_id
             LEFT JOIN sav_niveaux_competences n ON n.nco_id = cut.cut_niveau_competence_id
             LEFT JOIN sav_societes s ON s.soc_id = cut.cut_societe_id
             WHERE cut.cut_utilisateur_id = :id
               AND cut.cut_supprime_le IS NULL
               AND cut.cut_archive_le IS NULL
               AND c.cmp_supprime_le IS NULL
               AND c.cmp_archive_le IS NULL
             ORDER BY c.cmp_nom ASC",
            ['id' => $id]
        );
    }

    public function certificationsUtilisateur(int $id): array
    {
        return $this->db->fetchAll(
            "SELECT ceu.*, c.cer_code, c.cer_nom, s.soc_nom AS societe_nom, i.soc_nom AS delivree_par_nom
             FROM sav_certifications_utilisateurs ceu
             INNER JOIN sav_certifications c ON c.cer_id = ceu.ceu_certification_id
             LEFT JOIN sav_societes s ON s.soc_id = ceu.ceu_societe_id
             LEFT JOIN sav_societes i ON i.soc_id = ceu.ceu_delivree_par_societe_id
             WHERE ceu.ceu_utilisateur_id = :id
               AND ceu.ceu_supprime_le IS NULL
               AND ceu.ceu_archive_le IS NULL
               AND c.cer_supprime_le IS NULL
               AND c.cer_archive_le IS NULL
             ORDER BY ceu.ceu_expire_le IS NULL ASC, ceu.ceu_expire_le ASC, c.cer_nom ASC",
            ['id' => $id]
        );
    }

    private function assignmentsCompetencesUtilisateur(int $id): array
    {
        $assignments = [];
        foreach ($this->competencesUtilisateur($id) as $row) {
            $assignments[(int) $row['cut_competence_id']] = [
                'level_id' => (int) $row['cut_niveau_competence_id'],
                'started_at' => $row['cut_debute_le'] ?? null,
                'ended_at' => $row['cut_termine_le'] ?? null,
            ];
        }
        return $assignments;
    }

    private function assignmentsCertificationsUtilisateur(int $id): array
    {
        $assignments = [];
        foreach ($this->certificationsUtilisateur($id) as $row) {
            $assignments[(int) $row['ceu_certification_id']] = [
                'issued_at' => $row['ceu_delivree_le'] ?? null,
                'expires_at' => $row['ceu_expire_le'] ?? null,
            ];
        }
        return $assignments;
    }

    public function managersUtilisateur(int $id): array
    {
        return $this->db->fetchAll(
            "SELECT h.*, p.pui_nom, p.pui_prenom, u.uti_email, s.soc_nom AS societe_nom
             FROM sav_hierarchie_utilisateurs h
             INNER JOIN sav_utilisateurs u ON u.uti_id = h.hiu_superieur_utilisateur_id
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
             LEFT JOIN sav_societes s ON s.soc_id = h.hiu_societe_id
             WHERE h.hiu_utilisateur_id = :id
               AND h.hiu_supprime_le IS NULL
               AND h.hiu_archive_le IS NULL
             ORDER BY p.pui_nom ASC, p.pui_prenom ASC",
            ['id' => $id]
        );
    }

    public function subordonnesUtilisateur(int $id, ?int $societeId = null): array
    {
        $sql = "SELECT h.*, p.pui_nom, p.pui_prenom, u.uti_email, s.soc_nom AS societe_nom
                FROM sav_hierarchie_utilisateurs h
                INNER JOIN sav_utilisateurs u ON u.uti_id = h.hiu_utilisateur_id
                LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
                LEFT JOIN sav_societes s ON s.soc_id = h.hiu_societe_id
                WHERE h.hiu_superieur_utilisateur_id = :id
                  AND h.hiu_supprime_le IS NULL
                  AND h.hiu_archive_le IS NULL";
        $params = ['id' => $id];
        if ($societeId !== null) {
            $sql .= " AND h.hiu_societe_id = :sid";
            $params['sid'] = $societeId;
        }
        $sql .= " ORDER BY p.pui_nom ASC, p.pui_prenom ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function societesActives(): array
    {
        return $this->db->fetchAll(
            "SELECT soc_id, soc_code, soc_nom
             FROM sav_societes
             WHERE soc_supprime_le IS NULL AND soc_archive_le IS NULL
             ORDER BY soc_nom ASC"
        );
    }

    public function rolesActifs(?array $societesAutorisees = null): array
    {
        $sql = "SELECT rol_id, rol_code, rol_nom, rol_societe_proprietaire_id
             FROM sav_roles
             WHERE rol_supprime_le IS NULL AND rol_archive_le IS NULL";
        $params = [];
        if ($societesAutorisees !== null && $societesAutorisees !== []) {
            $ph = [];
            foreach (array_values($societesAutorisees) as $i => $sid) {
                $ph[] = ':sid' . $i;
                $params['sid' . $i] = (int) $sid;
            }
            $sql .= ' AND (rol_societe_proprietaire_id IS NULL OR rol_societe_proprietaire_id IN (' . implode(',', $ph) . '))';
        } elseif ($societesAutorisees === []) {
            $sql .= ' AND rol_societe_proprietaire_id IS NULL';
        }
        $sql .= ' ORDER BY rol_nom ASC';
        return $this->db->fetchAll(
            $sql,
            $params
        );
    }

    public function fonctionsActives(?array $societesAutorisees = null): array
    {
        $sql = "SELECT fon_id, fon_code, fon_nom, fon_societe_proprietaire_id
             FROM sav_fonctions
             WHERE fon_supprime_le IS NULL AND fon_archive_le IS NULL";
        $params = [];
        if ($societesAutorisees !== null && $societesAutorisees !== []) {
            $ph = [];
            foreach (array_values($societesAutorisees) as $i => $sid) {
                $ph[] = ':sid' . $i;
                $params['sid' . $i] = (int) $sid;
            }
            $sql .= ' AND (fon_societe_proprietaire_id IS NULL OR fon_societe_proprietaire_id IN (' . implode(',', $ph) . '))';
        } elseif ($societesAutorisees === []) {
            $sql .= ' AND fon_societe_proprietaire_id IS NULL';
        }
        $sql .= ' ORDER BY fon_nom ASC';
        return $this->db->fetchAll(
            $sql,
            $params
        );
    }


    public function departementsActifs(?array $societesAutorisees = null): array
    {
        $sql = "SELECT dep_id, dep_code, dep_nom, dep_societe_id FROM sav_departements WHERE dep_supprime_le IS NULL AND dep_archive_le IS NULL";
        $params = [];
        if ($societesAutorisees !== null) {
            if ($societesAutorisees === []) {
                return [];
            }
            $ph = [];
            foreach (array_values($societesAutorisees) as $i => $sid) {
                $ph[] = ':sid' . $i;
                $params['sid' . $i] = (int) $sid;
            }
            $sql .= ' AND dep_societe_id IN (' . implode(',', $ph) . ')';
        }
        $sql .= ' ORDER BY dep_nom ASC';
        return $this->db->fetchAll($sql, $params);
    }

    public function servicesActifs(?array $societesAutorisees = null): array
    {
        $sql = "SELECT srv_id, srv_code, srv_nom, srv_societe_id FROM sav_services WHERE srv_supprime_le IS NULL AND srv_archive_le IS NULL";
        $params = [];
        if ($societesAutorisees !== null) {
            if ($societesAutorisees === []) {
                return [];
            }
            $ph = [];
            foreach (array_values($societesAutorisees) as $i => $sid) {
                $ph[] = ':sid' . $i;
                $params['sid' . $i] = (int) $sid;
            }
            $sql .= ' AND srv_societe_id IN (' . implode(',', $ph) . ')';
        }
        $sql .= ' ORDER BY srv_nom ASC';
        return $this->db->fetchAll($sql, $params);
    }

    public function equipesActives(?array $societesAutorisees = null): array
    {
        $sql = "SELECT equ_id, equ_code, equ_nom, equ_societe_id FROM sav_equipes WHERE equ_supprime_le IS NULL AND equ_archive_le IS NULL";
        $params = [];
        if ($societesAutorisees !== null) {
            if ($societesAutorisees === []) {
                return [];
            }
            $ph = [];
            foreach (array_values($societesAutorisees) as $i => $sid) {
                $ph[] = ':sid' . $i;
                $params['sid' . $i] = (int) $sid;
            }
            $sql .= ' AND equ_societe_id IN (' . implode(',', $ph) . ')';
        }
        $sql .= ' ORDER BY equ_nom ASC';
        return $this->db->fetchAll($sql, $params);
    }

    public function competencesActives(?array $societesAutorisees = null): array
    {
        $sql = "SELECT cmp_id, cmp_code, cmp_nom, cmp_societe_proprietaire_id FROM sav_competences WHERE cmp_supprime_le IS NULL AND cmp_archive_le IS NULL";
        $params = [];
        if ($societesAutorisees !== null && $societesAutorisees !== []) {
            $ph = [];
            foreach (array_values($societesAutorisees) as $i => $sid) {
                $ph[] = ':sid' . $i;
                $params['sid' . $i] = (int) $sid;
            }
            $sql .= ' AND (cmp_societe_proprietaire_id IS NULL OR cmp_societe_proprietaire_id IN (' . implode(',', $ph) . '))';
        } elseif ($societesAutorisees === []) {
            $sql .= ' AND cmp_societe_proprietaire_id IS NULL';
        }
        $sql .= ' ORDER BY cmp_nom ASC';
        return $this->db->fetchAll($sql, $params);
    }

    public function niveauxCompetences(): array
    {
        return $this->db->fetchAll(
            "SELECT nco_id, nco_code, nco_nom, nco_rang
             FROM sav_niveaux_competences
             WHERE nco_supprime_le IS NULL
             ORDER BY nco_rang ASC, nco_nom ASC"
        );
    }

    public function certificationsActives(?array $societesAutorisees = null): array
    {
        $sql = "SELECT cer_id, cer_code, cer_nom, cer_societe_proprietaire_id FROM sav_certifications WHERE cer_supprime_le IS NULL AND cer_archive_le IS NULL";
        $params = [];
        if ($societesAutorisees !== null && $societesAutorisees !== []) {
            $ph = [];
            foreach (array_values($societesAutorisees) as $i => $sid) {
                $ph[] = ':sid' . $i;
                $params['sid' . $i] = (int) $sid;
            }
            $sql .= ' AND (cer_societe_proprietaire_id IS NULL OR cer_societe_proprietaire_id IN (' . implode(',', $ph) . '))';
        } elseif ($societesAutorisees === []) {
            $sql .= ' AND cer_societe_proprietaire_id IS NULL';
        }
        $sql .= ' ORDER BY cer_nom ASC';
        return $this->db->fetchAll($sql, $params);
    }

    public function utilisateursActifs(?array $societesAutorisees = null, ?int $exclureId = null): array
    {
        $where = ['u.uti_supprime_le IS NULL', 'u.uti_anonymise_le IS NULL'];
        $params = [];
        if ($exclureId !== null) {
            $where[] = 'u.uti_id <> :exclude';
            $params['exclude'] = $exclureId;
        }
        if ($societesAutorisees !== null) {
            if ($societesAutorisees === []) {
                return [];
            }
            $ph = [];
            foreach (array_values($societesAutorisees) as $i => $sid) {
                $ph[] = ':sid' . $i;
                $params['sid' . $i] = (int) $sid;
            }
            $where[] = "EXISTS (SELECT 1 FROM sav_adhesions_utilisateurs_societes aus
                         WHERE aus.aus_utilisateur_id = u.uti_id
                           AND aus.aus_societe_id IN (" . implode(',', $ph) . ")
                           AND aus.aus_supprime_le IS NULL
                           AND aus.aus_archive_le IS NULL)";
        }
        $stmt = $this->pdo->prepare(
            "SELECT u.uti_id, u.uti_email, p.pui_nom, p.pui_prenom
             FROM sav_utilisateurs u
             LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.pui_nom ASC, p.pui_prenom ASC, u.uti_email ASC"
        );
        $this->bindParams($stmt, $params);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function idsSocietesUtilisateur(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT aus.aus_societe_id
             FROM sav_adhesions_utilisateurs_societes aus
             INNER JOIN sav_statuts st_aus ON st_aus.sta_id = aus.aus_statut_id
                  AND st_aus.sta_domaine = 'general' AND st_aus.sta_code = 'actif'
             INNER JOIN sav_societes s ON s.soc_id = aus.aus_societe_id
                  AND s.soc_supprime_le IS NULL AND s.soc_archive_le IS NULL
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
             WHERE aus.aus_utilisateur_id = :id
               AND aus.aus_supprime_le IS NULL
               AND aus.aus_archive_le IS NULL
               AND (aus.aus_debute_le IS NULL OR aus.aus_debute_le <= NOW())
               AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= NOW())",
            ['id' => $userId]
        );
        return array_values(array_unique(array_map('intval', array_column($rows, 'aus_societe_id'))));
    }

    public function rechercher(string $query, ?array $societesAutorisees = null, int $limit = 10): array
    {
        $result = $this->paginer(['search' => $query, 'statut' => 'actif'], 1, $limit, $societesAutorisees);
        return $result['users'];
    }

    public function statutId(string $domaine, string $code, int $fallback = 1): int
    {
        $row = $this->db->fetch(
            "SELECT sta_id FROM sav_statuts
             WHERE sta_domaine = :domaine AND sta_code = :code AND sta_supprime_le IS NULL
             LIMIT 1",
            ['domaine' => $domaine, 'code' => $code]
        );
        return (int) ($row['sta_id'] ?? $fallback);
    }

    private function buildWhere(array $filtres, ?array $societesAutorisees): array
    {
        $where = ['u.uti_supprime_le IS NULL', 'u.uti_anonymise_le IS NULL'];
        $params = [];

        $search = trim((string) ($filtres['search'] ?? $filtres['recherche'] ?? ''));
        if ($search !== '') {
            $where[] = '(u.uti_email LIKE :q1 OR u.uti_identifiant LIKE :q2 OR p.pui_nom LIKE :q3 OR p.pui_prenom LIKE :q4)';
            $like = '%' . $search . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }

        $statut = trim((string) ($filtres['statut'] ?? $filtres['status'] ?? ''));
        if ($statut !== '') {
            $where[] = 'st.sta_code = :statut';
            $params['statut'] = $statut === 'active' ? 'actif' : ($statut === 'inactive' ? 'inactif' : $statut);
        }

        $societeId = (int) ($filtres['societe_id'] ?? $filtres['company_id'] ?? 0);
        if ($societeId > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM sav_adhesions_utilisateurs_societes aus_filtre
                         WHERE aus_filtre.aus_utilisateur_id = u.uti_id
                           AND aus_filtre.aus_societe_id = :societe_id
                           AND aus_filtre.aus_supprime_le IS NULL
                           AND aus_filtre.aus_archive_le IS NULL)';
            $params['societe_id'] = $societeId;
        }

        if ($societesAutorisees !== null) {
            if ($societesAutorisees === []) {
                $where[] = '1 = 0';
            } else {
                $ph = [];
                foreach (array_values($societesAutorisees) as $i => $sid) {
                    $ph[] = ':scope' . $i;
                    $params['scope' . $i] = (int) $sid;
                }
                $where[] = 'EXISTS (SELECT 1 FROM sav_adhesions_utilisateurs_societes aus_scope
                             WHERE aus_scope.aus_utilisateur_id = u.uti_id
                               AND aus_scope.aus_societe_id IN (' . implode(',', $ph) . ')
                               AND aus_scope.aus_supprime_le IS NULL
                               AND aus_scope.aus_archive_le IS NULL)';
            }
        }

        return [implode(' AND ', $where), $params];
    }

    private function insertUtilisateur(array $data): int
    {
        $cols = array_keys($data);
        $sql = 'INSERT INTO sav_utilisateurs (`' . implode('`, `', $cols) . '`) VALUES (:' . implode(', :', $cols) . ')';
        $stmt = $this->pdo->prepare($sql);
        $this->bindParams($stmt, $data);
        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    private function upsertProfil(int $id, array $profil, int $utilisateurAction): void
    {
        if ($profil === []) {
            return;
        }
        $exists = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM sav_profils_utilisateurs WHERE pui_utilisateur_id = :id AND pui_supprime_le IS NULL',
            ['id' => $id]
        ) > 0;
        $profil['pui_utilisateur_id'] = $id;
        if ($exists) {
            $profil['pui_modifie_par_utilisateur_id'] = $utilisateurAction ?: null;
            $sets = [];
            foreach (array_keys($profil) as $key) {
                if ($key !== 'pui_utilisateur_id') {
                    $sets[] = "`{$key}` = :{$key}";
                }
            }
            $stmt = $this->pdo->prepare('UPDATE sav_profils_utilisateurs SET ' . implode(', ', $sets) . ' WHERE pui_utilisateur_id = :pui_utilisateur_id AND pui_supprime_le IS NULL');
            $this->bindParams($stmt, $profil);
            $stmt->execute();
        } else {
            $profil['pui_cree_par_utilisateur_id'] = $utilisateurAction ?: null;
            $cols = array_keys($profil);
            $stmt = $this->pdo->prepare('INSERT INTO sav_profils_utilisateurs (`' . implode('`, `', $cols) . '`) VALUES (:' . implode(', :', $cols) . ')');
            $this->bindParams($stmt, $profil);
            $stmt->execute();
        }
    }

    private function upsertParametresSecurite(int $id, int $utilisateurAction): void
    {
        $exists = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM sav_parametres_securite_utilisateurs WHERE psu_utilisateur_id = :id AND psu_supprime_le IS NULL',
            ['id' => $id]
        ) > 0;
        if ($exists) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO sav_parametres_securite_utilisateurs (psu_utilisateur_id, psu_2fa_active, psu_cree_par_utilisateur_id) VALUES (:id, 0, :uid)'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':uid', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
    }

    private function upsertDonneesSensibles(int $id, array $data, int $utilisateurAction): void
    {
        $exists = (int) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM sav_donnees_sensibles_utilisateurs WHERE dsu_utilisateur_id = :id AND dsu_supprime_le IS NULL',
            ['id' => $id]
        ) > 0;
        $data['dsu_utilisateur_id'] = $id;
        if ($exists) {
            $data['dsu_modifie_par_utilisateur_id'] = $utilisateurAction ?: null;
            $sets = [];
            foreach (array_keys($data) as $key) {
                if ($key !== 'dsu_utilisateur_id') {
                    $sets[] = "`{$key}` = :{$key}";
                }
            }
            $stmt = $this->pdo->prepare('UPDATE sav_donnees_sensibles_utilisateurs SET ' . implode(', ', $sets) . ' WHERE dsu_utilisateur_id = :dsu_utilisateur_id AND dsu_supprime_le IS NULL');
            $this->bindParams($stmt, $data);
            $stmt->execute();
        } else {
            $data['dsu_cree_par_utilisateur_id'] = $utilisateurAction ?: null;
            $cols = array_keys($data);
            $stmt = $this->pdo->prepare('INSERT INTO sav_donnees_sensibles_utilisateurs (`' . implode('`, `', $cols) . '`) VALUES (:' . implode(', :', $cols) . ')');
            $this->bindParams($stmt, $data);
            $stmt->execute();
        }
    }

    private function syncRelations(int $id, array $relations, int $utilisateurAction): void
    {
        $societeIds = array_values(array_unique(array_filter(array_map('intval', $relations['societe_ids'] ?? []))));
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $relations['role_ids'] ?? []))));
        $fonctionIds = array_values(array_unique(array_filter(array_map('intval', $relations['fonction_ids'] ?? []))));
        $departementIds = array_values(array_unique(array_filter(array_map('intval', $relations['departement_ids'] ?? []))));
        $serviceIds = array_values(array_unique(array_filter(array_map('intval', $relations['service_ids'] ?? []))));
        $equipeIds = array_values(array_unique(array_filter(array_map('intval', $relations['equipe_ids'] ?? []))));
        $competenceAssignments = is_array($relations['competence_assignments'] ?? null) ? $relations['competence_assignments'] : [];
        $certificationAssignments = is_array($relations['certification_assignments'] ?? null) ? $relations['certification_assignments'] : [];
        $societePrincipale = (int) ($relations['societe_principale_id'] ?? ($societeIds[0] ?? 0));
        $managerId = (int) ($relations['manager_user_id'] ?? 0);

        $this->syncSocietes($id, $societeIds, $societePrincipale, $utilisateurAction);
        $this->syncRoles($id, $roleIds, $societePrincipale, $utilisateurAction);
        $this->syncFonctions($id, $fonctionIds, $societePrincipale, $utilisateurAction);
        $this->syncDepartements($id, $departementIds, $societePrincipale, $utilisateurAction);
        $this->syncServices($id, $serviceIds, $societePrincipale, $utilisateurAction);
        $this->syncEquipes($id, $equipeIds, $societePrincipale, $utilisateurAction);
        $this->syncCompetences($id, $competenceAssignments, $societePrincipale, $utilisateurAction);
        $this->syncCertifications($id, $certificationAssignments, $societePrincipale, $utilisateurAction);
        $this->syncManager($id, $managerId, $societePrincipale, $utilisateurAction);

        if ($societePrincipale > 0) {
            $stmt = $this->pdo->prepare('UPDATE sav_utilisateurs SET uti_societe_active_id = :sid WHERE uti_id = :id');
            $stmt->bindValue(':sid', $societePrincipale, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    private function syncSocietes(int $id, array $societeIds, int $societePrincipale, int $utilisateurAction): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sav_adhesions_utilisateurs_societes
             SET aus_archive_le = NOW(), aus_archive_par_utilisateur_id = :uid
             WHERE aus_utilisateur_id = :id AND aus_archive_le IS NULL AND aus_supprime_le IS NULL'
        );
        $stmt->bindValue(':uid', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $insert = $this->pdo->prepare(
            'INSERT INTO sav_adhesions_utilisateurs_societes
             (aus_utilisateur_id, aus_societe_id, aus_debute_le, aus_statut_id, aus_cree_par_utilisateur_id)
             VALUES (:uid_user, :sid, CURDATE(), :statut, :uid_action)'
        );
        $statut = $this->statutId('general', 'actif');
        foreach ($societeIds as $sid) {
            $insert->bindValue(':uid_user', $id, PDO::PARAM_INT);
            $insert->bindValue(':sid', $sid, PDO::PARAM_INT);
            $insert->bindValue(':statut', $statut, PDO::PARAM_INT);
            $insert->bindValue(':uid_action', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $insert->execute();
        }
    }

    private function syncRoles(int $id, array $roleIds, int $societeId, int $utilisateurAction): void
    {
        $avant = $this->db->fetchAll(
            'SELECT rcu_role_id FROM sav_roles_contextuels_utilisateurs
             WHERE rcu_utilisateur_id = :id AND rcu_archive_le IS NULL AND rcu_supprime_le IS NULL',
            ['id' => $id]
        );
        $avantIds = array_map(static fn(array $r): int => (int) $r['rcu_role_id'], $avant);

        $stmt = $this->pdo->prepare(
            'UPDATE sav_roles_contextuels_utilisateurs
             SET rcu_archive_le = NOW(), rcu_archive_par_utilisateur_id = :uid
             WHERE rcu_utilisateur_id = :id AND rcu_archive_le IS NULL AND rcu_supprime_le IS NULL'
        );
        $stmt->bindValue(':uid', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $insert = $this->pdo->prepare(
            'INSERT INTO sav_roles_contextuels_utilisateurs
             (rcu_utilisateur_id, rcu_role_id, rcu_societe_id, rcu_debute_le, rcu_statut_id, rcu_cree_par_utilisateur_id)
             VALUES (:user_id, :role_id, :societe_id, CURDATE(), :statut, :uid_action)'
        );
        $statut = $this->statutId('general', 'actif');
        foreach ($roleIds as $roleId) {
            $insert->bindValue(':user_id', $id, PDO::PARAM_INT);
            $insert->bindValue(':role_id', $roleId, PDO::PARAM_INT);
            $insert->bindValue(':societe_id', $societeId > 0 ? $societeId : null, $societeId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $insert->bindValue(':statut', $statut, PDO::PARAM_INT);
            $insert->bindValue(':uid_action', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $insert->execute();
        }

        // AUDIT 2026-06-21 — correctifs points 4.5 (journalisation) et 4.6 (fraîcheur du cache de droits) :
        // - "changement de rôle" fait partie des actions explicitement listées comme
        //   obligatoires à journaliser par le cahier des charges.
        // - uti_acl_version n'était jusqu'ici JAMAIS incrémenté nulle part dans le code :
        //   le mécanisme de rafraîchissement périodique de AuthMiddleware::refreshAclIfNeeded()
        //   existait mais ne se déclenchait donc jamais. Cet incrément le rend enfin actif :
        //   un changement de rôle est repris en session sous 60s au lieu de jamais.
        sort($avantIds);
        $apresIds = $roleIds;
        sort($apresIds);
        if ($avantIds !== $apresIds) {
            $this->pdo->prepare('UPDATE sav_utilisateurs SET uti_acl_version = uti_acl_version + 1 WHERE uti_id = :id')
                ->execute(['id' => $id]);

            $this->audit(
                $utilisateurAction ?: null,
                'utilisateur.changement_role',
                $id,
                ['roles_avant' => $avantIds, 'roles_apres' => $apresIds]
            );
        }
    }

    private function syncFonctions(int $id, array $fonctionIds, int $societeId, int $utilisateurAction): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sav_fonctions_utilisateurs
             SET fut_archive_le = NOW(), fut_archive_par_utilisateur_id = :uid
             WHERE fut_utilisateur_id = :id AND fut_archive_le IS NULL AND fut_supprime_le IS NULL'
        );
        $stmt->bindValue(':uid', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($societeId <= 0) {
            return;
        }
        $insert = $this->pdo->prepare(
            'INSERT INTO sav_fonctions_utilisateurs
             (fut_utilisateur_id, fut_societe_id, fut_fonction_id, fut_debute_le, fut_statut_id, fut_cree_par_utilisateur_id)
             VALUES (:user_id, :societe_id, :fonction_id, CURDATE(), :statut, :uid_action)'
        );
        $statut = $this->statutId('general', 'actif');
        foreach ($fonctionIds as $fonctionId) {
            $insert->bindValue(':user_id', $id, PDO::PARAM_INT);
            $insert->bindValue(':societe_id', $societeId, PDO::PARAM_INT);
            $insert->bindValue(':fonction_id', $fonctionId, PDO::PARAM_INT);
            $insert->bindValue(':statut', $statut, PDO::PARAM_INT);
            $insert->bindValue(':uid_action', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $insert->execute();
        }
    }


    private function syncDepartements(int $id, array $departementIds, int $societeId, int $utilisateurAction): void
    {
        $this->archiveAffectations('sav_utilisateurs_departements', 'udp', 'udp_utilisateur_id', $id, $utilisateurAction);
        if ($societeId <= 0) {
            return;
        }
        $insert = $this->pdo->prepare(
            'INSERT INTO sav_utilisateurs_departements
             (udp_utilisateur_id, udp_societe_id, udp_departement_id, udp_debute_le, udp_statut_id, udp_cree_par_utilisateur_id)
             VALUES (:user_id, :societe_id, :item_id, CURDATE(), :statut, :uid_action)'
        );
        $this->insertSimpleAffectations($insert, $id, $societeId, $departementIds, $utilisateurAction);
    }

    private function syncServices(int $id, array $serviceIds, int $societeId, int $utilisateurAction): void
    {
        $this->archiveAffectations('sav_utilisateurs_services', 'usv', 'usv_utilisateur_id', $id, $utilisateurAction);
        if ($societeId <= 0) {
            return;
        }
        $insert = $this->pdo->prepare(
            'INSERT INTO sav_utilisateurs_services
             (usv_utilisateur_id, usv_societe_id, usv_service_id, usv_debute_le, usv_statut_id, usv_cree_par_utilisateur_id)
             VALUES (:user_id, :societe_id, :item_id, CURDATE(), :statut, :uid_action)'
        );
        $this->insertSimpleAffectations($insert, $id, $societeId, $serviceIds, $utilisateurAction);
    }

    private function syncEquipes(int $id, array $equipeIds, int $societeId, int $utilisateurAction): void
    {
        $this->archiveAffectations('sav_utilisateurs_equipes', 'ueq', 'ueq_utilisateur_id', $id, $utilisateurAction);
        if ($societeId <= 0) {
            return;
        }
        $insert = $this->pdo->prepare(
            'INSERT INTO sav_utilisateurs_equipes
             (ueq_utilisateur_id, ueq_societe_id, ueq_equipe_id, ueq_debute_le, ueq_statut_id, ueq_cree_par_utilisateur_id)
             VALUES (:user_id, :societe_id, :item_id, CURDATE(), :statut, :uid_action)'
        );
        $this->insertSimpleAffectations($insert, $id, $societeId, $equipeIds, $utilisateurAction);
    }

    private function syncCompetences(int $id, array $assignments, int $societeId, int $utilisateurAction): void
    {
        $this->archiveAffectations('sav_competences_utilisateurs', 'cut', 'cut_utilisateur_id', $id, $utilisateurAction);
        if ($societeId <= 0) {
            return;
        }
        $defaultLevel = (int) ($this->niveauxCompetences()[0]['nco_id'] ?? 1);
        $insert = $this->pdo->prepare(
            'INSERT INTO sav_competences_utilisateurs
             (cut_utilisateur_id, cut_competence_id, cut_niveau_competence_id, cut_societe_id, cut_debute_le, cut_statut_id, cut_cree_par_utilisateur_id)
             VALUES (:user_id, :competence_id, :niveau_id, :societe_id, CURDATE(), :statut, :uid_action)'
        );
        $statut = $this->statutId('general', 'actif');
        foreach ($assignments as $competenceId => $assignment) {
            $competenceId = (int) $competenceId;
            if ($competenceId <= 0) {
                continue;
            }
            $levelId = (int) ($assignment['level_id'] ?? $defaultLevel);
            $insert->bindValue(':user_id', $id, PDO::PARAM_INT);
            $insert->bindValue(':competence_id', $competenceId, PDO::PARAM_INT);
            $insert->bindValue(':niveau_id', $levelId > 0 ? $levelId : $defaultLevel, PDO::PARAM_INT);
            $insert->bindValue(':societe_id', $societeId, PDO::PARAM_INT);
            $insert->bindValue(':statut', $statut, PDO::PARAM_INT);
            $insert->bindValue(':uid_action', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $insert->execute();
        }
    }

    private function syncCertifications(int $id, array $assignments, int $societeId, int $utilisateurAction): void
    {
        $this->archiveAffectations('sav_certifications_utilisateurs', 'ceu', 'ceu_utilisateur_id', $id, $utilisateurAction);
        if ($societeId <= 0) {
            return;
        }
        $insert = $this->pdo->prepare(
            'INSERT INTO sav_certifications_utilisateurs
             (ceu_utilisateur_id, ceu_certification_id, ceu_societe_id, ceu_delivree_le, ceu_expire_le, ceu_statut_id, ceu_cree_par_utilisateur_id)
             VALUES (:user_id, :certification_id, :societe_id, :issued_at, :expires_at, :statut, :uid_action)'
        );
        $statut = $this->statutId('general', 'actif');
        foreach ($assignments as $certificationId => $assignment) {
            $certificationId = (int) $certificationId;
            if ($certificationId <= 0) {
                continue;
            }
            $issuedAt = $assignment['issued_at'] ?? null;
            $expiresAt = $assignment['expires_at'] ?? null;
            $insert->bindValue(':user_id', $id, PDO::PARAM_INT);
            $insert->bindValue(':certification_id', $certificationId, PDO::PARAM_INT);
            $insert->bindValue(':societe_id', $societeId, PDO::PARAM_INT);
            $insert->bindValue(':issued_at', $issuedAt ?: null, $issuedAt ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $insert->bindValue(':expires_at', $expiresAt ?: null, $expiresAt ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $insert->bindValue(':statut', $statut, PDO::PARAM_INT);
            $insert->bindValue(':uid_action', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $insert->execute();
        }
    }

    private function archiveAffectations(string $table, string $prefix, string $userColumn, int $userId, int $utilisateurAction): void
    {
        $sql = "UPDATE {$table}
                SET {$prefix}_archive_le = NOW(), {$prefix}_archive_par_utilisateur_id = :uid
                WHERE {$userColumn} = :id AND {$prefix}_archive_le IS NULL AND {$prefix}_supprime_le IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':uid', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function insertSimpleAffectations(\PDOStatement $insert, int $userId, int $societeId, array $ids, int $utilisateurAction): void
    {
        $statut = $this->statutId('general', 'actif');
        foreach ($ids as $itemId) {
            $itemId = (int) $itemId;
            if ($itemId <= 0) {
                continue;
            }
            $insert->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $insert->bindValue(':societe_id', $societeId, PDO::PARAM_INT);
            $insert->bindValue(':item_id', $itemId, PDO::PARAM_INT);
            $insert->bindValue(':statut', $statut, PDO::PARAM_INT);
            $insert->bindValue(':uid_action', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $insert->execute();
        }
    }

    private function syncManager(int $id, int $managerId, int $societeId, int $utilisateurAction): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sav_hierarchie_utilisateurs
             SET hiu_archive_le = NOW(), hiu_archive_par_utilisateur_id = :uid
             WHERE hiu_utilisateur_id = :id AND hiu_archive_le IS NULL AND hiu_supprime_le IS NULL'
        );
        $stmt->bindValue(':uid', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        if ($managerId <= 0 || $managerId === $id || $societeId <= 0) {
            return;
        }
        $stmt = $this->pdo->prepare(
            'INSERT INTO sav_hierarchie_utilisateurs
             (hiu_societe_id, hiu_utilisateur_id, hiu_superieur_utilisateur_id, hiu_debute_le, hiu_statut_id, hiu_cree_par_utilisateur_id)
             VALUES (:societe_id, :user_id, :manager_id, CURDATE(), :statut, :uid_action)'
        );
        $stmt->bindValue(':societe_id', $societeId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':manager_id', $managerId, PDO::PARAM_INT);
        $stmt->bindValue(':statut', $this->statutId('general', 'actif'), PDO::PARAM_INT);
        $stmt->bindValue(':uid_action', $utilisateurAction ?: null, $utilisateurAction ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->execute();
    }

    private function avecAliasCompatibilite(array $row): array
    {
        $row['use_id'] = $row['uti_id'] ?? $row['use_id'] ?? null;
        $row['use_uuid'] = $row['uti_uuid'] ?? $row['use_uuid'] ?? null;
        $row['use_username'] = $row['uti_identifiant'] ?? $row['use_username'] ?? null;
        $row['use_password_hash'] = $row['uti_mot_de_passe_hash'] ?? $row['use_password_hash'] ?? null;
        $row['use_email'] = $row['uti_email'] ?? $row['use_email'] ?? null;
        $row['use_firstname'] = $row['pui_prenom'] ?? $row['use_firstname'] ?? null;
        $row['use_lastname'] = $row['pui_nom'] ?? $row['use_lastname'] ?? null;
        $row['use_phone'] = $row['pui_telephone'] ?? $row['use_phone'] ?? null;
        $row['use_mobile'] = $row['pui_mobile'] ?? $row['use_mobile'] ?? null;
        $row['use_is_active'] = (($row['statut_code'] ?? '') === 'actif') ? 1 : 0;
        $row['use_is_locked'] = (int) ($row['uti_est_verrouille'] ?? 0);
        $row['use_locale'] = $row['uti_langue'] ?? 'fr';
        $row['use_timezone'] = 'Europe/Paris';
        $row['use_active_company_id'] = $row['uti_societe_active_id'] ?? null;
        $row['use_active_brand_id'] = $row['uti_marque_active_id'] ?? null;
        $row['primary_role_label'] = $row['roles_noms'] ?? null;
        return $row;
    }

    private function transaction(callable $callback): mixed
    {
        $own = !$this->pdo->inTransaction();
        if ($own) {
            $this->pdo->beginTransaction();
        }
        try {
            $result = $callback();
            if ($own) {
                $this->pdo->commit();
            }
            return $result;
        } catch (\Throwable $e) {
            if ($own && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function bindParams(\PDOStatement $stmt, array $params): void
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