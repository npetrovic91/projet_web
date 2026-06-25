<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Connectors\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Connecteurs, clés API et webhooks alignés sur le dump SQL actuel.
 * Tables utilisées :
 * - sav_connecteurs
 * - sav_connecteurs_modules
 * - sav_cles_api
 * - sav_journaux_webhooks
 * - sav_evenements_application
 * - sav_abonnements_evenements
 * - sav_modules / sav_societes / sav_statuts
 */
class ConnectorModel extends BaseModel
{
    protected string $table = 'sav_connecteurs';
    protected string $colPrefix = 'con_';

    public function statistiques(): array
    {
        return [
            'connecteurs' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_connecteurs WHERE con_supprime_le IS NULL'),
            'cles_api' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_cles_api WHERE cap_revoque_le IS NULL'),
            'webhooks_24h' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_journaux_webhooks WHERE jwh_cree_le >= DATE_SUB(NOW(), INTERVAL 1 DAY)'),
            'evenements_24h' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_evenements_application WHERE eva_cree_le >= DATE_SUB(NOW(), INTERVAL 1 DAY) AND eva_supprime_le IS NULL'),
        ];
    }

    public function listerConnecteurs(array $filters = [], int $limit = 300): array
    {
        $where = ['c.con_supprime_le IS NULL'];
        $params = [];

        if (!empty($filters['type'])) {
            $where[] = 'c.con_type = :type';
            $params['type'] = (string) $filters['type'];
        }
        if (!empty($filters['societe_id'])) {
            $where[] = 'c.con_societe_id = :societe_id';
            $params['societe_id'] = (int) $filters['societe_id'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(c.con_code LIKE :q OR c.con_nom LIKE :q OR c.con_type LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }

        $limit = max(1, min(1000, $limit));
        return $this->db->fetchAll(
            'SELECT c.*, m.mod_code, m.mod_nom, s.soc_nom, st.sta_code AS statut_code, st.sta_libelle AS statut_libelle,
                    (SELECT COUNT(*) FROM sav_cles_api k WHERE k.cap_connecteur_id = c.con_id AND k.cap_revoque_le IS NULL) AS total_cles_api,
                    (SELECT MAX(j.jwh_cree_le) FROM sav_journaux_webhooks j WHERE j.jwh_webhook_id = c.con_id) AS dernier_webhook_le
               FROM sav_connecteurs c
          LEFT JOIN sav_modules m ON m.mod_id = c.con_module_id
          LEFT JOIN sav_societes s ON s.soc_id = c.con_societe_id
          LEFT JOIN sav_statuts st ON st.sta_id = c.con_statut_id
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY s.soc_nom ASC, c.con_type ASC, c.con_nom ASC
              LIMIT ' . $limit,
            $params
        );
    }

    public function trouverConnecteur(int $id): ?array
    {
        $row = $this->db->fetch(
            'SELECT c.*, m.mod_code, m.mod_nom, s.soc_nom, st.sta_code AS statut_code, st.sta_libelle AS statut_libelle
               FROM sav_connecteurs c
          LEFT JOIN sav_modules m ON m.mod_id = c.con_module_id
          LEFT JOIN sav_societes s ON s.soc_id = c.con_societe_id
          LEFT JOIN sav_statuts st ON st.sta_id = c.con_statut_id
              WHERE c.con_id = :id AND c.con_supprime_le IS NULL
              LIMIT 1',
            ['id' => $id]
        );
        if (!$row) {
            return null;
        }
        $row['configuration_decodee'] = $this->decodeJson($row['con_configuration_json'] ?? null, []);
        $row['secret_masque'] = !empty($row['con_secret_chiffre']) ? '********' : '';
        return $row;
    }

    public function creerConnecteur(array $input, int $userId = 0): int
    {
        $data = $this->normaliserConnecteur($input);
        $this->db->execute(
            'INSERT INTO sav_connecteurs
                (con_uuid, con_code, con_nom, con_type, con_module_id, con_societe_id, con_configuration_json,
                 con_secret_chiffre, con_statut_id, con_cree_le, con_modifie_le, con_cree_par_utilisateur_id)
             VALUES
                (:uuid, :code, :nom, :type, :module_id, :societe_id, :configuration_json,
                 :secret_chiffre, :statut_id, NOW(), NOW(), :user_id)',
            [
                'uuid' => $this->uuid(),
                'code' => $data['code'],
                'nom' => $data['nom'],
                'type' => $data['type'],
                'module_id' => $data['module_id'],
                'societe_id' => $data['societe_id'],
                'configuration_json' => $data['configuration_json'],
                'secret_chiffre' => $data['secret_chiffre'],
                'statut_id' => $data['statut_id'],
                'user_id' => $userId ?: null,
            ]
        );
        $id = (int) $this->pdo->lastInsertId();
        $this->journaliser('connecteur.creer', 'sav_connecteurs', $id, $userId, ['code' => $data['code'], 'type' => $data['type']]);
        return $id;
    }

    public function modifierConnecteur(int $id, array $input, int $userId = 0): bool
    {
        $current = $this->trouverConnecteur($id);
        if (!$current) {
            throw new \InvalidArgumentException('Connecteur introuvable.');
        }
        $data = $this->normaliserConnecteur($input, $current);
        $this->db->execute(
            'UPDATE sav_connecteurs
                SET con_code = :code,
                    con_nom = :nom,
                    con_type = :type,
                    con_module_id = :module_id,
                    con_societe_id = :societe_id,
                    con_configuration_json = :configuration_json,
                    con_secret_chiffre = :secret_chiffre,
                    con_statut_id = :statut_id,
                    con_modifie_le = NOW(),
                    con_modifie_par_utilisateur_id = :user_id
              WHERE con_id = :id AND con_supprime_le IS NULL',
            [
                'id' => $id,
                'code' => $data['code'],
                'nom' => $data['nom'],
                'type' => $data['type'],
                'module_id' => $data['module_id'],
                'societe_id' => $data['societe_id'],
                'configuration_json' => $data['configuration_json'],
                'secret_chiffre' => $data['secret_chiffre'],
                'statut_id' => $data['statut_id'],
                'user_id' => $userId ?: null,
            ]
        );
        $this->journaliser('connecteur.modifier', 'sav_connecteurs', $id, $userId, ['code' => $data['code']]);
        return true;
    }

    public function supprimerConnecteur(int $id, int $userId = 0): bool
    {
        $this->db->execute(
            'UPDATE sav_connecteurs
                SET con_supprime_le = NOW(), con_modifie_le = NOW(), con_supprime_par_utilisateur_id = :user_id
              WHERE con_id = :id AND con_supprime_le IS NULL',
            ['id' => $id, 'user_id' => $userId ?: null]
        );
        $this->journaliser('connecteur.supprimer', 'sav_connecteurs', $id, $userId, []);
        return true;
    }

    public function listerClesApi(array $filters = [], int $limit = 300): array
    {
        $where = ['1=1'];
        $params = [];
        if (empty($filters['inclure_revoquees'])) {
            $where[] = 'k.cap_revoque_le IS NULL';
        }
        if (!empty($filters['connecteur_id'])) {
            $where[] = 'k.cap_connecteur_id = :connecteur_id';
            $params['connecteur_id'] = (int) $filters['connecteur_id'];
        }
        $limit = max(1, min(1000, $limit));
        return $this->db->fetchAll(
            'SELECT k.*, c.con_code, c.con_nom, s.soc_nom, u.uti_email,
                    CONCAT(COALESCE(p.pui_prenom, \'\'), \' \', COALESCE(p.pui_nom, \'\')) AS utilisateur_nom
               FROM sav_cles_api k
          LEFT JOIN sav_connecteurs c ON c.con_id = k.cap_connecteur_id
          LEFT JOIN sav_societes s ON s.soc_id = k.cap_societe_id
          LEFT JOIN sav_utilisateurs u ON u.uti_id = k.cap_utilisateur_id
          LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY k.cap_revoque_le ASC, k.cap_expire_le ASC, k.cap_cree_le DESC
              LIMIT ' . $limit,
            $params
        );
    }

    /**
     * Crée une clé API : le secret complet est retourné une seule fois.
     */
    public function creerCleApi(array $input, int $userId = 0): array
    {
        $plain = $this->genererSecret();
        $prefix = substr($plain, 0, 12);
        $scopes = $this->jsonFromTextarea((string) ($input['portees_json'] ?? ''), []);
        $this->db->execute(
            'INSERT INTO sav_cles_api
                (cap_uuid, cap_societe_id, cap_utilisateur_id, cap_connecteur_id, cap_nom, cap_prefixe_public,
                 cap_hash_secret, cap_portees_json, cap_expire_le, cap_statut_id, cap_cree_le, cap_modifie_le,
                 cap_cree_par_utilisateur_id)
             VALUES
                (:uuid, :societe_id, :utilisateur_id, :connecteur_id, :nom, :prefixe_public,
                 :hash_secret, :portees_json, :expire_le, :statut_id, NOW(), NOW(), :user_id)',
            [
                'uuid' => $this->uuid(),
                'societe_id' => $this->nullableInt($input['societe_id'] ?? null),
                'utilisateur_id' => $this->nullableInt($input['utilisateur_id'] ?? null),
                'connecteur_id' => $this->nullableInt($input['connecteur_id'] ?? null),
                'nom' => trim((string) ($input['nom'] ?? 'Clé API')),
                'prefixe_public' => $prefix,
                'hash_secret' => hash('sha256', $plain),
                'portees_json' => json_encode($scopes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'expire_le' => $this->dateTimeOrNull($input['expire_le'] ?? null),
                'statut_id' => $this->nullableInt($input['statut_id'] ?? 1),
                'user_id' => $userId ?: null,
            ]
        );
        $id = (int) $this->pdo->lastInsertId();
        $this->journaliser('cle_api.creer', 'sav_cles_api', $id, $userId, ['prefixe_public' => $prefix]);
        return ['id' => $id, 'secret' => $plain, 'prefixe_public' => $prefix];
    }

    public function verifierCleApi(string $secret): ?array
    {
        $hash = hash('sha256', $secret);
        $row = $this->db->fetch(
            'SELECT k.*, c.con_code, c.con_type
               FROM sav_cles_api k
          LEFT JOIN sav_connecteurs c ON c.con_id = k.cap_connecteur_id
              WHERE k.cap_hash_secret = :hash
                AND k.cap_revoque_le IS NULL
                AND (k.cap_expire_le IS NULL OR k.cap_expire_le > NOW())
              LIMIT 1',
            ['hash' => $hash]
        );
        if (!$row) {
            return null;
        }
        $this->db->execute('UPDATE sav_cles_api SET cap_derniere_utilisation_le = NOW() WHERE cap_id = :id', ['id' => (int) $row['cap_id']]);
        $row['portees_decodees'] = $this->decodeJson($row['cap_portees_json'] ?? null, []);
        return $row;
    }

    public function revoquerCleApi(int $id, int $userId = 0): bool
    {
        $this->db->execute(
            'UPDATE sav_cles_api
                SET cap_revoque_le = NOW(), cap_modifie_le = NOW(), cap_revoque_par_utilisateur_id = :user_id
              WHERE cap_id = :id AND cap_revoque_le IS NULL',
            ['id' => $id, 'user_id' => $userId ?: null]
        );
        $this->journaliser('cle_api.revoquer', 'sav_cles_api', $id, $userId, []);
        return true;
    }

    public function listerWebhooks(array $filters = [], int $limit = 300): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['connecteur_id'])) {
            $where[] = 'j.jwh_webhook_id = :connecteur_id';
            $params['connecteur_id'] = (int) $filters['connecteur_id'];
        }
        if (!empty($filters['success']) && $filters['success'] !== 'all') {
            $where[] = 'j.jwh_succes = :success';
            $params['success'] = $filters['success'] === '1' ? 1 : 0;
        }
        $limit = max(1, min(1000, $limit));
        return $this->db->fetchAll(
            'SELECT j.*, c.con_code, c.con_nom, e.eva_code, e.eva_nom
               FROM sav_journaux_webhooks j
          LEFT JOIN sav_connecteurs c ON c.con_id = j.jwh_webhook_id
          LEFT JOIN sav_evenements_application e ON e.eva_id = j.jwh_evenement_application_id
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY j.jwh_cree_le DESC
              LIMIT ' . $limit,
            $params
        );
    }

    public function journaliserWebhook(array $data): int
    {
        $this->db->execute(
            'INSERT INTO sav_journaux_webhooks
                (jwh_webhook_id, jwh_evenement_application_id, jwh_url, jwh_methode, jwh_code_http,
                 jwh_requete_json, jwh_reponse_json, jwh_succes, jwh_message_erreur, jwh_duree_ms, jwh_cree_le)
             VALUES
                (:webhook_id, :evenement_id, :url, :methode, :code_http,
                 :requete_json, :reponse_json, :succes, :message_erreur, :duree_ms, NOW())',
            [
                'webhook_id' => $this->nullableInt($data['webhook_id'] ?? null),
                'evenement_id' => $this->nullableInt($data['evenement_id'] ?? null),
                'url' => (string) ($data['url'] ?? ''),
                'methode' => strtoupper((string) ($data['methode'] ?? 'POST')),
                'code_http' => $this->nullableInt($data['code_http'] ?? null),
                'requete_json' => $this->jsonValue($data['requete'] ?? null),
                'reponse_json' => $this->jsonValue($data['reponse'] ?? null),
                'succes' => isset($data['succes']) ? (int) (bool) $data['succes'] : null,
                'message_erreur' => $data['message_erreur'] ?? null,
                'duree_ms' => $this->nullableInt($data['duree_ms'] ?? null),
            ]
        );
        return (int) $this->pdo->lastInsertId();
    }

    public function listerConnecteursModules(int $limit = 300): array
    {
        return $this->db->fetchAll(
            'SELECT cm.*, ms.mod_code AS module_source_code, ms.mod_nom AS module_source_nom,
                    mc.mod_code AS module_cible_code, mc.mod_nom AS module_cible_nom,
                    st.sta_code AS statut_code, st.sta_libelle AS statut_libelle
               FROM sav_connecteurs_modules cm
          LEFT JOIN sav_modules ms ON ms.mod_id = cm.cmo_module_source_id
          LEFT JOIN sav_modules mc ON mc.mod_id = cm.cmo_module_cible_id
          LEFT JOIN sav_statuts st ON st.sta_id = cm.cmo_statut_id
              WHERE cm.cmo_supprime_le IS NULL
           ORDER BY ms.mod_nom ASC, cm.cmo_nom ASC
              LIMIT ' . max(1, min(1000, $limit))
        );
    }

    public function listerEvenements(int $limit = 300): array
    {
        return $this->db->fetchAll(
            'SELECT e.*, m.mod_code, m.mod_nom, s.soc_nom, st.sta_code AS statut_code, st.sta_libelle AS statut_libelle,
                    (SELECT COUNT(*) FROM sav_file_evenements f WHERE f.fev_evenement_id = e.eva_id) AS total_file
               FROM sav_evenements_application e
          LEFT JOIN sav_modules m ON m.mod_id = e.eva_module_id
          LEFT JOIN sav_societes s ON s.soc_id = e.eva_societe_id
          LEFT JOIN sav_statuts st ON st.sta_id = e.eva_statut_id
              WHERE e.eva_supprime_le IS NULL
           ORDER BY e.eva_cree_le DESC
              LIMIT ' . max(1, min(1000, $limit))
        );
    }

    public function creerEvenement(array $input, int $userId = 0): int
    {
        $data = $this->normaliserEvenement($input, $userId);
        $this->db->execute(
            'INSERT INTO sav_evenements_application
                (eva_uuid, eva_code, eva_nom, eva_module_id, eva_societe_id, eva_emetteur_utilisateur_id,
                 eva_cible_type, eva_cible_id, eva_donnees_json, eva_statut_id, eva_cree_le, eva_modifie_le)
             VALUES
                (:uuid, :code, :nom, :module_id, :societe_id, :emetteur_id,
                 :cible_type, :cible_id, :donnees_json, :statut_id, NOW(), NOW())',
            [
                'uuid' => $this->uuid(),
                'code' => $data['code'],
                'nom' => $data['nom'],
                'module_id' => $data['module_id'],
                'societe_id' => $data['societe_id'],
                'emetteur_id' => $data['emetteur_id'],
                'cible_type' => $data['cible_type'],
                'cible_id' => $data['cible_id'],
                'donnees_json' => $data['donnees_json'],
                'statut_id' => $data['statut_id'],
            ]
        );
        $id = (int) $this->pdo->lastInsertId();
        $this->journaliser('evenement.creer', 'sav_evenements_application', $id, $userId, ['code' => $data['code']]);
        return $id;
    }

    public function referentiels(): array
    {
        return [
            'modules' => $this->db->fetchAll('SELECT mod_id, mod_code, mod_nom FROM sav_modules WHERE mod_supprime_le IS NULL ORDER BY mod_nom ASC'),
            'societes' => $this->db->fetchAll('SELECT soc_id, soc_nom FROM sav_societes WHERE soc_supprime_le IS NULL ORDER BY soc_nom ASC LIMIT 500'),
            'statuts' => $this->db->fetchAll('SELECT sta_id, sta_domaine, sta_code, sta_libelle FROM sav_statuts WHERE sta_supprime_le IS NULL ORDER BY sta_domaine ASC, sta_ordre ASC'),
            'connecteurs' => $this->db->fetchAll('SELECT con_id, con_code, con_nom FROM sav_connecteurs WHERE con_supprime_le IS NULL ORDER BY con_nom ASC'),
        ];
    }

    private function normaliserConnecteur(array $input, ?array $current = null): array
    {
        $code = trim((string) ($input['code'] ?? $input['con_code'] ?? $current['con_code'] ?? ''));
        $nom = trim((string) ($input['nom'] ?? $input['con_nom'] ?? $current['con_nom'] ?? ''));
        $type = trim((string) ($input['type'] ?? $input['con_type'] ?? $current['con_type'] ?? 'webhook'));
        if ($code === '' || $nom === '') {
            throw new \InvalidArgumentException('Le code et le nom du connecteur sont obligatoires.');
        }
        $configuration = $this->jsonFromTextarea((string) ($input['configuration_json'] ?? $input['con_configuration_json'] ?? ''), $current['configuration_decodee'] ?? []);
        $secretInput = (string) ($input['secret'] ?? '');
        $secret = $secretInput !== '' ? hash('sha256', $secretInput) : ($current['con_secret_chiffre'] ?? null);
        return [
            'code' => $this->slug($code),
            'nom' => $nom,
            'type' => $this->slug($type),
            'module_id' => $this->nullableInt($input['module_id'] ?? $input['con_module_id'] ?? $current['con_module_id'] ?? null),
            'societe_id' => $this->nullableInt($input['societe_id'] ?? $input['con_societe_id'] ?? $current['con_societe_id'] ?? null),
            'configuration_json' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'secret_chiffre' => $secret,
            'statut_id' => $this->nullableInt($input['statut_id'] ?? $input['con_statut_id'] ?? $current['con_statut_id'] ?? 1),
        ];
    }

    private function normaliserEvenement(array $input, int $userId): array
    {
        $code = trim((string) ($input['code'] ?? ''));
        $nom = trim((string) ($input['nom'] ?? ''));
        if ($code === '' || $nom === '') {
            throw new \InvalidArgumentException('Le code et le nom de l’événement sont obligatoires.');
        }
        return [
            'code' => $this->slug($code),
            'nom' => $nom,
            'module_id' => $this->nullableInt($input['module_id'] ?? null),
            'societe_id' => $this->nullableInt($input['societe_id'] ?? null),
            'emetteur_id' => $this->nullableInt($input['emetteur_id'] ?? $userId ?: null),
            'cible_type' => trim((string) ($input['cible_type'] ?? '')) ?: null,
            'cible_id' => $this->nullableInt($input['cible_id'] ?? null),
            'donnees_json' => json_encode($this->jsonFromTextarea((string) ($input['donnees_json'] ?? ''), []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'statut_id' => $this->nullableInt($input['statut_id'] ?? 1),
        ];
    }

    private function journaliser(string $action, string $table, int $id, int $userId, array $metadata): void
    {
        try {
            $this->db->execute(
                'INSERT INTO sav_journaux_audit
                    (jau_utilisateur_id, jau_action, jau_table_cible, jau_id_cible, jau_adresse_ip, jau_metadata_json, jau_cree_le)
                 VALUES
                    (:user_id, :action, :table_cible, :id_cible, :ip, :metadata, NOW())',
                [
                    'user_id' => $userId ?: null,
                    'action' => $action,
                    'table_cible' => $table,
                    'id_cible' => $id ?: null,
                    'ip' => function_exists('client_ip') ? @inet_pton((string) client_ip()) : null,
                    'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable) {
            // L'audit ne doit jamais bloquer une opération métier.
        }
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function genererSecret(): string
    {
        return 'asv_' . bin2hex(random_bytes(24));
    }

    private function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_.-]+/i', '_', $value) ?: $value;
        return trim($value, '_') ?: 'connecteur';
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }
        return (int) $value;
    }

    private function dateTimeOrNull(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        return str_replace('T', ' ', $value);
    }

    private function decodeJson(?string $json, mixed $default): mixed
    {
        if (!$json) {
            return $default;
        }
        $decoded = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }

    private function jsonFromTextarea(string $json, mixed $default): mixed
    {
        $json = trim($json);
        if ($json === '') {
            return $default;
        }
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('JSON invalide : ' . json_last_error_msg());
        }
        return $decoded;
    }

    private function jsonValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
