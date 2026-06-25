<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Emails\Models;

use Nenad\Autosav\Core\Database\Database;

class EmailModel
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function listerModeles(array $filtres = []): array
    {
        $where = ['m.mel_supprime_le IS NULL'];
        $params = [];

        if (!empty($filtres['search'])) {
            $where[] = '(m.mel_code LIKE :q OR m.mel_sujet LIKE :q OR m.mel_corps LIKE :q)';
            $params[':q'] = '%' . trim((string) $filtres['search']) . '%';
        }

        if (!empty($filtres['statut_id'])) {
            $where[] = 'm.mel_statut_id = :statut_id';
            $params[':statut_id'] = (int) $filtres['statut_id'];
        }

        $sql = "SELECT m.*, st.sta_code AS statut_code, st.sta_libelle AS statut_libelle
                FROM sav_modeles_emails m
                LEFT JOIN sav_statuts st ON st.sta_id = m.mel_statut_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY m.mel_code ASC, m.mel_id DESC";
        return $this->db->fetchAll($sql, $params);
    }

    public function trouverModele(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT m.*, st.sta_code AS statut_code, st.sta_libelle AS statut_libelle
             FROM sav_modeles_emails m
             LEFT JOIN sav_statuts st ON st.sta_id = m.mel_statut_id
             WHERE m.mel_id = :id AND m.mel_supprime_le IS NULL",
            [':id' => $id]
        );
    }

    public function trouverModeleParCode(string $code): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM sav_modeles_emails
             WHERE mel_code = :code AND mel_supprime_le IS NULL
             LIMIT 1",
            [':code' => trim($code)]
        );
    }

    public function creerModele(array $data): int
    {
        $this->db->execute(
            "INSERT INTO sav_modeles_emails
             (mel_code, mel_sujet, mel_corps, mel_statut_id, mel_cree_le)
             VALUES (:code, :sujet, :corps, :statut_id, NOW())",
            [
                ':code' => $this->normaliserCode((string) ($data['mel_code'] ?? $data['code'] ?? '')),
                ':sujet' => trim((string) ($data['mel_sujet'] ?? $data['subject'] ?? '')),
                ':corps' => (string) ($data['mel_corps'] ?? $data['body'] ?? $data['body_html'] ?? ''),
                ':statut_id' => $data['mel_statut_id'] ?? $data['statut_id'] ?? $this->statutId('general', 'actif'),
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function modifierModele(int $id, array $data): bool
    {
        return $this->db->execute(
            "UPDATE sav_modeles_emails
             SET mel_code = :code,
                 mel_sujet = :sujet,
                 mel_corps = :corps,
                 mel_statut_id = :statut_id,
                 mel_modifie_le = NOW()
             WHERE mel_id = :id AND mel_supprime_le IS NULL",
            [
                ':id' => $id,
                ':code' => $this->normaliserCode((string) ($data['mel_code'] ?? $data['code'] ?? '')),
                ':sujet' => trim((string) ($data['mel_sujet'] ?? $data['subject'] ?? '')),
                ':corps' => (string) ($data['mel_corps'] ?? $data['body'] ?? $data['body_html'] ?? ''),
                ':statut_id' => $data['mel_statut_id'] ?? $data['statut_id'] ?? $this->statutId('general', 'actif'),
            ]
        );
    }

    public function supprimerModele(int $id): bool
    {
        return $this->db->execute(
            "UPDATE sav_modeles_emails
             SET mel_supprime_le = NOW()
             WHERE mel_id = :id",
            [':id' => $id]
        );
    }

    public function listerJournaux(array $filtres = [], int $limit = 200): array
    {
        $where = [];
        $params = [];

        if (!empty($filtres['societe_id'])) {
            $where[] = '(j.jme_societe_expediteur_id = :societe_id OR j.jme_societe_destinataire_id = :societe_id)';
            $params[':societe_id'] = (int) $filtres['societe_id'];
        }
        if (!empty($filtres['utilisateur_id'])) {
            $where[] = 'j.jme_utilisateur_destinataire_id = :utilisateur_id';
            $params[':utilisateur_id'] = (int) $filtres['utilisateur_id'];
        }
        if (!empty($filtres['type_evenement'])) {
            $where[] = 'j.jme_type_evenement LIKE :type_evenement';
            $params[':type_evenement'] = trim((string) $filtres['type_evenement']) . '%';
        }
        if (!empty($filtres['email'])) {
            $where[] = 'j.jme_email_destinataire LIKE :email';
            $params[':email'] = '%' . trim((string) $filtres['email']) . '%';
        }

        $sql = "SELECT j.*, m.mel_code, st.sta_code AS statut_code, st.sta_libelle AS statut_libelle,
                       se.soc_nom AS societe_expediteur_nom,
                       sd.soc_nom AS societe_destinataire_nom,
                       p.pui_prenom, p.pui_nom
                FROM sav_journaux_emails j
                LEFT JOIN sav_modeles_emails m ON m.mel_id = j.jme_modele_email_id
                LEFT JOIN sav_statuts st ON st.sta_id = j.jme_statut_id
                LEFT JOIN sav_societes se ON se.soc_id = j.jme_societe_expediteur_id
                LEFT JOIN sav_societes sd ON sd.soc_id = j.jme_societe_destinataire_id
                LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = j.jme_utilisateur_destinataire_id
                " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
                ORDER BY j.jme_cree_le DESC, j.jme_id DESC
                LIMIT " . max(1, min(1000, $limit));
        return $this->db->fetchAll($sql, $params);
    }

    public function journaliser(array $data): int
    {
        $this->db->execute(
            "INSERT INTO sav_journaux_emails
             (jme_modele_email_id, jme_societe_expediteur_id, jme_societe_destinataire_id,
              jme_utilisateur_destinataire_id, jme_email_destinataire, jme_type_evenement,
              jme_sujet, jme_corps, jme_envoye_le, jme_statut_id, jme_cree_le)
             VALUES
             (:modele_id, :societe_expediteur_id, :societe_destinataire_id,
              :utilisateur_destinataire_id, :email_destinataire, :type_evenement,
              :sujet, :corps, :envoye_le, :statut_id, NOW())",
            [
                ':modele_id' => $data['modele_id'] ?? null,
                ':societe_expediteur_id' => $data['societe_expediteur_id'] ?? null,
                ':societe_destinataire_id' => $data['societe_destinataire_id'] ?? null,
                ':utilisateur_destinataire_id' => $data['utilisateur_destinataire_id'] ?? null,
                ':email_destinataire' => $data['email_destinataire'] ?? null,
                ':type_evenement' => $data['type_evenement'] ?? 'email.manuel',
                ':sujet' => $data['sujet'] ?? '',
                ':corps' => $data['corps'] ?? null,
                ':envoye_le' => !empty($data['envoye']) ? date('Y-m-d H:i:s') : null,
                ':statut_id' => $data['statut_id'] ?? null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function destinatairesUtilisateurs(array $filtres = []): array
    {
        $sql = "SELECT DISTINCT
                       u.uti_id,
                       u.uti_email,
                       u.uti_email_normalise,
                       p.pui_prenom,
                       p.pui_nom,
                       aus.aus_societe_id AS societe_id,
                       s.soc_nom AS societe_nom
                FROM sav_utilisateurs u
                LEFT JOIN sav_profils_utilisateurs p
                       ON p.pui_utilisateur_id = u.uti_id
                      AND p.pui_supprime_le IS NULL
                INNER JOIN sav_adhesions_utilisateurs_societes aus
                       ON aus.aus_utilisateur_id = u.uti_id
                      AND aus.aus_supprime_le IS NULL
                      AND aus.aus_archive_le IS NULL
                      AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= CURDATE())
                INNER JOIN sav_societes s
                       ON s.soc_id = aus.aus_societe_id
                      AND s.soc_supprime_le IS NULL
                      AND s.soc_archive_le IS NULL
                LEFT JOIN sav_statuts stu ON stu.sta_id = u.uti_statut_id";

        $params = [];
        $where = [
            'u.uti_supprime_le IS NULL',
            'u.uti_anonymise_le IS NULL',
            "(stu.sta_code IS NULL OR stu.sta_code = 'actif')",
            "u.uti_email IS NOT NULL",
            "u.uti_email <> ''",
        ];

        if (!empty($filtres['company_id'])) {
            $where[] = 'aus.aus_societe_id = :company_id';
            $params[':company_id'] = (int) $filtres['company_id'];
        }

        if (!empty($filtres['role_id'])) {
            $sql .= " INNER JOIN sav_roles_contextuels_utilisateurs rcu
                           ON rcu.rcu_utilisateur_id = u.uti_id
                          AND rcu.rcu_role_id = :role_id
                          AND rcu.rcu_supprime_le IS NULL
                          AND rcu.rcu_archive_le IS NULL
                          AND (rcu.rcu_termine_le IS NULL OR rcu.rcu_termine_le >= CURDATE())";
            $params[':role_id'] = (int) $filtres['role_id'];
        }

        $sql .= ' WHERE ' . implode(' AND ', $where) . '
                  ORDER BY s.soc_nom ASC, p.pui_nom ASC, p.pui_prenom ASC, u.uti_email ASC';

        return $this->db->fetchAll($sql, $params);
    }

    public function listerRoles(): array
    {
        return $this->db->fetchAll(
            "SELECT r.rol_id, r.rol_code, r.rol_nom
             FROM sav_roles r
             LEFT JOIN sav_statuts st ON st.sta_id = r.rol_statut_id
             WHERE r.rol_supprime_le IS NULL
               AND r.rol_archive_le IS NULL
               AND (st.sta_code IS NULL OR st.sta_code = 'actif')
             ORDER BY r.rol_nom ASC"
        );
    }

    public function listerSocietes(?int $societeId = null): array
    {
        $where = ['soc_supprime_le IS NULL', 'soc_archive_le IS NULL'];
        $params = [];
        if ($societeId) {
            $where[] = 'soc_id = :societe_id';
            $params[':societe_id'] = $societeId;
        }
        return $this->db->fetchAll(
            "SELECT soc_id, soc_nom, soc_code
             FROM sav_societes
             WHERE " . implode(' AND ', $where) . "
             ORDER BY soc_nom ASC",
            $params
        );
    }

    public function listerStatuts(string $domaine = 'general'): array
    {
        return $this->db->fetchAll(
            "SELECT sta_id, sta_code, sta_libelle
             FROM sav_statuts
             WHERE sta_domaine = :domaine
               AND sta_est_actif = 1
               AND sta_supprime_le IS NULL
             ORDER BY sta_ordre ASC, sta_libelle ASC",
            [':domaine' => $domaine]
        );
    }

    public function statutId(string $domaine, string $code): ?int
    {
        $row = $this->db->fetch(
            "SELECT sta_id FROM sav_statuts
             WHERE sta_domaine = :domaine
               AND sta_code = :code
               AND sta_supprime_le IS NULL
             LIMIT 1",
            [':domaine' => $domaine, ':code' => $code]
        );
        return $row ? (int) $row['sta_id'] : null;
    }

    public function canalEmailId(): ?int
    {
        $row = $this->db->fetch(
            "SELECT cno_id FROM sav_canaux_notifications
             WHERE cno_code = 'email'
               AND cno_supprime_le IS NULL
             LIMIT 1"
        );
        return $row ? (int) $row['cno_id'] : null;
    }

    public function desactiverPreferenceEmail(int $userId, string $type = 'bulk_mail'): void
    {
        $canalId = $this->canalEmailId();
        if (!$canalId) {
            return;
        }

        $existing = $this->db->fetch(
            "SELECT pno_id FROM sav_preferences_notifications
             WHERE pno_utilisateur_id = :user_id
               AND pno_canal_notification_id = :canal_id
               AND pno_type_notification = :type
               AND pno_supprime_le IS NULL
             LIMIT 1",
            [':user_id' => $userId, ':canal_id' => $canalId, ':type' => $type]
        );

        if ($existing) {
            $this->db->execute(
                "UPDATE sav_preferences_notifications
                 SET pno_est_active = 0,
                     pno_modifie_le = NOW()
                 WHERE pno_id = :id",
                [':id' => (int) $existing['pno_id']]
            );
            return;
        }

        $this->db->execute(
            "INSERT INTO sav_preferences_notifications
             (pno_utilisateur_id, pno_canal_notification_id, pno_type_notification,
              pno_est_active, pno_parametres_json, pno_cree_le)
             VALUES (:user_id, :canal_id, :type, 0, :params, NOW())",
            [
                ':user_id' => $userId,
                ':canal_id' => $canalId,
                ':type' => $type,
                ':params' => json_encode(['source' => 'lien_desabonnement'], JSON_UNESCAPED_UNICODE),
            ]
        );
    }

    public function emailAutorisePourUtilisateur(int $userId, string $type = 'bulk_mail'): bool
    {
        $canalId = $this->canalEmailId();
        if (!$canalId) {
            return true;
        }

        $row = $this->db->fetch(
            "SELECT pno_est_active
             FROM sav_preferences_notifications
             WHERE pno_utilisateur_id = :user_id
               AND pno_canal_notification_id = :canal_id
               AND pno_type_notification = :type
               AND pno_supprime_le IS NULL
             ORDER BY pno_id DESC
             LIMIT 1",
            [':user_id' => $userId, ':canal_id' => $canalId, ':type' => $type]
        );
        return !$row || (int) $row['pno_est_active'] === 1;
    }

    private function normaliserCode(string $code): string
    {
        $code = trim(mb_strtolower($code));
        $code = preg_replace('/[^a-z0-9_.-]+/u', '_', $code) ?: '';
        return trim($code, '_') ?: 'modele_email_' . date('YmdHis');
    }
}
