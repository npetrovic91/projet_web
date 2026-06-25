<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Invitations\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class InvitationModel extends BaseModel
{
    protected string $table = 'sav_invitations_utilisateurs';
    protected string $colPrefix = 'inv_';

    public function statistiques(?int $societeId = null): array
    {
        $where = ['inv.inv_supprime_le IS NULL'];
        $params = [];
        if ($societeId) {
            $where[] = 'inv.inv_societe_id = :societe_id';
            $params['societe_id'] = $societeId;
        }
        $sqlWhere = implode(' AND ', $where);
        $row = $this->fetchOne("SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN inv.inv_acceptee_le IS NOT NULL THEN 1 ELSE 0 END) AS acceptees,
                SUM(CASE WHEN inv.inv_annulee_le IS NOT NULL THEN 1 ELSE 0 END) AS annulees,
                SUM(CASE WHEN inv.inv_acceptee_le IS NULL AND inv.inv_annulee_le IS NULL AND inv.inv_expire_le < NOW() THEN 1 ELSE 0 END) AS expirees,
                SUM(CASE WHEN inv.inv_acceptee_le IS NULL AND inv.inv_annulee_le IS NULL AND inv.inv_expire_le >= NOW() THEN 1 ELSE 0 END) AS en_attente
            FROM sav_invitations_utilisateurs inv
            WHERE {$sqlWhere}", $params) ?? [];
        return array_map('intval', [
            'total' => $row['total'] ?? 0,
            'acceptees' => $row['acceptees'] ?? 0,
            'annulees' => $row['annulees'] ?? 0,
            'expirees' => $row['expirees'] ?? 0,
            'en_attente' => $row['en_attente'] ?? 0,
        ]);
    }

    public function lister(array $filters = []): array
    {
        $where = ['inv.inv_supprime_le IS NULL'];
        $params = [];
        if (!empty($filters['societe_id'])) {
            $where[] = 'inv.inv_societe_id = :societe_id';
            $params['societe_id'] = (int)$filters['societe_id'];
        }
        if (!empty($filters['statut_id'])) {
            $where[] = 'inv.inv_statut_id = :statut_id';
            $params['statut_id'] = (int)$filters['statut_id'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(inv.inv_email LIKE :q OR soc.soc_nom LIKE :q OR rol.rol_nom LIKE :q OR fon.fon_nom LIKE :q)';
            $params['q'] = '%' . trim((string)$filters['q']) . '%';
        }
        if (($filters['etat'] ?? '') === 'en_attente') {
            $where[] = 'inv.inv_acceptee_le IS NULL AND inv.inv_annulee_le IS NULL AND inv.inv_expire_le >= NOW()';
        } elseif (($filters['etat'] ?? '') === 'acceptee') {
            $where[] = 'inv.inv_acceptee_le IS NOT NULL';
        } elseif (($filters['etat'] ?? '') === 'annulee') {
            $where[] = 'inv.inv_annulee_le IS NOT NULL';
        } elseif (($filters['etat'] ?? '') === 'expiree') {
            $where[] = 'inv.inv_acceptee_le IS NULL AND inv.inv_annulee_le IS NULL AND inv.inv_expire_le < NOW()';
        }
        $sql = "SELECT inv.*, soc.soc_nom, rol.rol_nom, fon.fon_nom, sta.sta_libelle,
                       CASE
                         WHEN inv.inv_acceptee_le IS NOT NULL THEN 'acceptée'
                         WHEN inv.inv_annulee_le IS NOT NULL THEN 'annulée'
                         WHEN inv.inv_expire_le < NOW() THEN 'expirée'
                         ELSE 'en attente'
                       END AS etat_calcule
                FROM sav_invitations_utilisateurs inv
                INNER JOIN sav_societes soc ON soc.soc_id = inv.inv_societe_id
                LEFT JOIN sav_roles rol ON rol.rol_id = inv.inv_role_prevu_id
                LEFT JOIN sav_fonctions fon ON fon.fon_id = inv.inv_fonction_prevue_id
                LEFT JOIN sav_statuts sta ON sta.sta_id = inv.inv_statut_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY soc.soc_nom, inv.inv_cree_le DESC, inv.inv_email";
        return $this->fetchAll($sql, $params);
    }

    public function trouver(int $id): ?array
    {
        return $this->fetchOne("SELECT inv.*, soc.soc_nom, rol.rol_nom, fon.fon_nom, sta.sta_libelle
            FROM sav_invitations_utilisateurs inv
            INNER JOIN sav_societes soc ON soc.soc_id = inv.inv_societe_id
            LEFT JOIN sav_roles rol ON rol.rol_id = inv.inv_role_prevu_id
            LEFT JOIN sav_fonctions fon ON fon.fon_id = inv.inv_fonction_prevue_id
            LEFT JOIN sav_statuts sta ON sta.sta_id = inv.inv_statut_id
            WHERE inv.inv_id = :id AND inv.inv_supprime_le IS NULL", ['id' => $id]);
    }

    public function trouverParHash(string $hash): ?array
    {
        return $this->fetchOne("SELECT inv.*, soc.soc_nom, rol.rol_nom, fon.fon_nom
            FROM sav_invitations_utilisateurs inv
            INNER JOIN sav_societes soc ON soc.soc_id = inv.inv_societe_id
            LEFT JOIN sav_roles rol ON rol.rol_id = inv.inv_role_prevu_id
            LEFT JOIN sav_fonctions fon ON fon.fon_id = inv.inv_fonction_prevue_id
            WHERE inv.inv_jeton_hash = :hash AND inv.inv_supprime_le IS NULL
            LIMIT 1", ['hash' => $hash]);
    }

    public function creer(array $data): int { return $this->insert($data); }
    public function modifier(int $id, array $data): void { $this->update($id, $data, 'id'); }

    public function references(): array
    {
        return [
            'societes' => $this->fetchAll("SELECT soc_id, soc_nom FROM sav_societes WHERE soc_supprime_le IS NULL ORDER BY soc_nom"),
            'roles' => $this->fetchAll("SELECT rol_id, rol_nom, rol_code FROM sav_roles WHERE rol_supprime_le IS NULL ORDER BY rol_nom"),
            'fonctions' => $this->fetchAll("SELECT fon_id, fon_nom, fon_code FROM sav_fonctions WHERE fon_supprime_le IS NULL ORDER BY fon_nom"),
            'statuts' => $this->fetchAll("SELECT sta_id, sta_libelle, sta_code FROM sav_statuts WHERE sta_supprime_le IS NULL AND sta_domaine = 'invitation' ORDER BY sta_ordre, sta_libelle"),
            'statut_invitation_envoyee' => $this->statutId('invitation', 'envoyee'),
            'statut_invitation_acceptee' => $this->statutId('invitation', 'acceptee'),
            'statut_invitation_annulee' => $this->statutId('invitation', 'annulee'),
            'statut_utilisateur_actif' => $this->statutId('utilisateur', 'actif'),
            'statut_general_actif' => $this->statutId('general', 'actif'),
        ];
    }

    public function statutId(string $domaine, string $code): ?int
    {
        $row = $this->fetchOne("SELECT sta_id FROM sav_statuts WHERE sta_domaine = :domaine AND sta_code = :code AND sta_supprime_le IS NULL LIMIT 1", ['domaine' => $domaine, 'code' => $code]);
        return $row ? (int)$row['sta_id'] : null;
    }

    public function creerUtilisateurDepuisInvitation(array $invitation, array $data, ?string $ip, ?string $userAgent): int
    {
        $this->beginTransaction();
        try {
            $email = strtolower(trim((string)$invitation['inv_email_normalise']));
            $existant = $this->fetchOne("SELECT uti_id FROM sav_utilisateurs WHERE uti_email_normalise = :email AND uti_supprime_le IS NULL LIMIT 1", ['email' => $email]);
            $userId = $existant ? (int)$existant['uti_id'] : 0;
            $statutUtilisateur = $this->statutId('utilisateur', 'actif');
            $statutGeneral = $this->statutId('general', 'actif');
            if ($userId <= 0) {
                $userId = $this->insertUtilisateur($email, (string)$data['password'], $statutUtilisateur, $ip, $userAgent);
                $this->insertProfil($userId, $data);
            }
            $this->assurerAdhesion($userId, (int)$invitation['inv_societe_id'], $statutGeneral);
            if (!empty($invitation['inv_role_prevu_id'])) {
                $this->assurerRoleContextuel($userId, (int)$invitation['inv_societe_id'], (int)$invitation['inv_role_prevu_id'], $statutGeneral);
            }
            if (!empty($invitation['inv_fonction_prevue_id'])) {
                $this->assurerFonction($userId, (int)$invitation['inv_societe_id'], (int)$invitation['inv_fonction_prevue_id'], $statutGeneral);
            }
            $this->query("UPDATE sav_invitations_utilisateurs SET inv_acceptee_le = NOW(), inv_statut_id = :statut WHERE inv_id = :id", [
                'statut' => $this->statutId('invitation', 'acceptee'),
                'id' => (int)$invitation['inv_id'],
            ]);
            $this->commit();
            return $userId;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    private function insertUtilisateur(string $email, string $password, ?int $statutId, ?string $ip, ?string $userAgent): int
    {
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $uuid = $this->uuid4();
        $this->query("INSERT INTO sav_utilisateurs
            (uti_uuid, uti_identifiant, uti_email, uti_email_normalise, uti_mot_de_passe_hash, uti_email_verifie_le, uti_doit_changer_mot_de_passe, uti_statut_id, uti_langue, uti_derniere_connexion_ip, uti_dernier_user_agent, uti_cree_le)
            VALUES (:uuid, :identifiant, :email, :email_norm, :hash, NOW(), 0, :statut, 'fr', INET6_ATON(:ip), :ua, NOW())", [
            'uuid' => $uuid,
            'identifiant' => strstr($email, '@', true) ?: $email,
            'email' => $email,
            'email_norm' => $email,
            'hash' => $hash,
            'statut' => $statutId,
            'ip' => $ip ?: '127.0.0.1',
            'ua' => $userAgent,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    private function insertProfil(int $userId, array $data): void
    {
        $this->query("INSERT INTO sav_profils_utilisateurs (pui_utilisateur_id, pui_nom, pui_prenom, pui_cree_le)
            VALUES (:id, :nom, :prenom, NOW())", [
            'id' => $userId,
            'nom' => trim((string)($data['nom'] ?? '')) ?: null,
            'prenom' => trim((string)($data['prenom'] ?? '')) ?: null,
        ]);
    }

    private function assurerAdhesion(int $userId, int $societeId, ?int $statutId): void
    {
        $row = $this->fetchOne("SELECT aus_id FROM sav_adhesions_utilisateurs_societes WHERE aus_utilisateur_id = :u AND aus_societe_id = :s AND aus_supprime_le IS NULL AND aus_termine_le IS NULL", ['u' => $userId, 's' => $societeId]);
        if (!$row) {
            $this->query("INSERT INTO sav_adhesions_utilisateurs_societes (aus_utilisateur_id, aus_societe_id, aus_debute_le, aus_statut_id, aus_cree_le) VALUES (:u, :s, CURDATE(), :statut, NOW())", ['u' => $userId, 's' => $societeId, 'statut' => $statutId]);
        }
        $this->query("UPDATE sav_utilisateurs SET uti_societe_active_id = :s WHERE uti_id = :u", ['s' => $societeId, 'u' => $userId]);
    }

    private function assurerRoleContextuel(int $userId, int $societeId, int $roleId, ?int $statutId): void
    {
        $row = $this->fetchOne("SELECT rcu_id FROM sav_roles_contextuels_utilisateurs WHERE rcu_utilisateur_id = :u AND rcu_societe_id = :s AND rcu_role_id = :r AND rcu_supprime_le IS NULL AND rcu_termine_le IS NULL", ['u' => $userId, 's' => $societeId, 'r' => $roleId]);
        if (!$row) {
            $this->query("INSERT INTO sav_roles_contextuels_utilisateurs (rcu_utilisateur_id, rcu_role_id, rcu_societe_id, rcu_debute_le, rcu_statut_id, rcu_cree_le) VALUES (:u, :r, :s, CURDATE(), :statut, NOW())", ['u' => $userId, 'r' => $roleId, 's' => $societeId, 'statut' => $statutId]);
        }
    }

    private function assurerFonction(int $userId, int $societeId, int $fonctionId, ?int $statutId): void
    {
        $row = $this->fetchOne("SELECT fut_id FROM sav_fonctions_utilisateurs WHERE fut_utilisateur_id = :u AND fut_societe_id = :s AND fut_fonction_id = :f AND fut_supprime_le IS NULL AND fut_termine_le IS NULL", ['u' => $userId, 's' => $societeId, 'f' => $fonctionId]);
        if (!$row) {
            $this->query("INSERT INTO sav_fonctions_utilisateurs (fut_utilisateur_id, fut_societe_id, fut_fonction_id, fut_debute_le, fut_statut_id, fut_cree_le) VALUES (:u, :s, :f, CURDATE(), :statut, NOW())", ['u' => $userId, 's' => $societeId, 'f' => $fonctionId, 'statut' => $statutId]);
        }
    }

    public function audit(?int $userId, ?int $societeId, string $action, string $table, ?int $cibleId, array $meta = [], ?string $ip = null): void
    {
        $this->query("INSERT INTO sav_journaux_audit (jau_utilisateur_id, jau_societe_id, jau_action, jau_table_cible, jau_id_cible, jau_adresse_ip, jau_metadata_json, jau_cree_le)
            VALUES (:user_id, :societe_id, :action, :table_cible, :id_cible, INET6_ATON(:ip), :meta, NOW())", [
            'user_id' => $userId,
            'societe_id' => $societeId,
            'action' => $action,
            'table_cible' => $table,
            'id_cible' => $cibleId,
            'ip' => $ip ?: '127.0.0.1',
            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
