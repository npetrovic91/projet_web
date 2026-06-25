<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Settings\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Paramètres applicatifs alignés sur sav_parametres_application.
 *
 * Règles du dump SQL :
 * - valeur métier standard dans pap_valeur_json sous la forme {"valeur": ...};
 * - secret applicatif dans pap_valeur_chiffree avec métadonnées d'algorithme;
 * - suppression logique via pap_supprime_le;
 * - aucun fichier de configuration n'est modifié par ce module.
 */
class ApplicationSettingModel extends BaseModel
{
    protected string $table = 'sav_parametres_application';
    protected string $colPrefix = 'pap_';

    public function lister(array $filters = [], int $limit = 300): array
    {
        $where = ['pap_supprime_le IS NULL'];
        $params = [];

        $domain = trim((string) ($filters['domain'] ?? ''));
        if ($domain !== '') {
            $where[] = 'pap_domaine = :domain';
            $params['domain'] = $domain;
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $where[] = '(pap_domaine LIKE :q OR pap_cle LIKE :q OR pap_description LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }

        $limit = max(1, min(1000, $limit));
        return $this->db->fetchAll(
            'SELECT p.*, st.sta_code AS statut_code, st.sta_libelle AS statut_libelle
               FROM sav_parametres_application p
          LEFT JOIN sav_statuts st ON st.sta_id = p.pap_statut_id
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY p.pap_domaine ASC, p.pap_est_secret ASC, p.pap_cle ASC
              LIMIT ' . $limit,
            $params
        );
    }

    public function domaines(): array
    {
        return $this->db->fetchAll(
            "SELECT pap_domaine AS domaine, COUNT(*) AS total
               FROM sav_parametres_application
              WHERE pap_supprime_le IS NULL
           GROUP BY pap_domaine
           ORDER BY pap_domaine ASC"
        );
    }

    public function trouver(int $id): ?array
    {
        $row = $this->db->fetch(
            'SELECT p.*, st.sta_code AS statut_code, st.sta_libelle AS statut_libelle
               FROM sav_parametres_application p
          LEFT JOIN sav_statuts st ON st.sta_id = p.pap_statut_id
              WHERE p.pap_id = :id
                AND p.pap_supprime_le IS NULL
              LIMIT 1',
            ['id' => $id]
        );
        return $row ? $this->enrichir($row) : null;
    }

    public function trouverParCle(string $domain, string $key): ?array
    {
        $row = $this->db->fetch(
            'SELECT *
               FROM sav_parametres_application
              WHERE pap_domaine = :domain
                AND pap_cle = :cle
                AND pap_supprime_le IS NULL
           ORDER BY pap_id DESC
              LIMIT 1',
            ['domain' => $domain, 'cle' => $key]
        );
        return $row ? $this->enrichir($row) : null;
    }

    public function valeur(string $domain, string $key, mixed $default = null): mixed
    {
        $row = $this->trouverParCle($domain, $key);
        if (!$row || !array_key_exists('valeur_decodee', $row)) {
            return $default;
        }
        return $row['valeur_decodee'];
    }

    public function creer(array $input, int $userId = 0, string $ip = ''): int
    {
        $data = $this->normaliser($input);
        $existing = $this->trouverParCle($data['pap_domaine'], $data['pap_cle']);
        if ($existing) {
            throw new \InvalidArgumentException('Ce paramètre existe déjà pour ce domaine.');
        }

        $payload = $this->payloadValeur($data);
        $this->db->execute(
            'INSERT INTO sav_parametres_application
                (pap_domaine, pap_cle, pap_valeur_json, pap_valeur_chiffree, pap_algorithme_chiffrement,
                 pap_reference_coffre_secret, pap_description, pap_est_secret, pap_est_systeme, pap_statut_id,
                 pap_cree_le, pap_modifie_le)
             VALUES
                (:domaine, :cle, :valeur_json, :valeur_chiffree, :algorithme, :reference_secret,
                 :description, :est_secret, :est_systeme, :statut_id, NOW(), NOW())',
            [
                'domaine' => $data['pap_domaine'],
                'cle' => $data['pap_cle'],
                'valeur_json' => $payload['json'],
                'valeur_chiffree' => $payload['encrypted'],
                'algorithme' => $payload['algorithm'],
                'reference_secret' => $data['pap_reference_coffre_secret'],
                'description' => $data['pap_description'],
                'est_secret' => $data['pap_est_secret'],
                'est_systeme' => $data['pap_est_systeme'],
                'statut_id' => $data['pap_statut_id'],
            ]
        );
        $id = (int) $this->pdo->lastInsertId();
        $this->journaliser('settings.create', $id, $userId, $ip, $data['pap_domaine'] . '.' . $data['pap_cle']);
        return $id;
    }

    public function modifier(int $id, array $input, int $userId = 0, string $ip = ''): bool
    {
        $current = $this->trouver($id);
        if (!$current) {
            throw new \InvalidArgumentException('Paramètre introuvable.');
        }

        $data = $this->normaliser($input, $current);
        $payload = $this->payloadValeur($data, $current);

        $this->db->execute(
            'UPDATE sav_parametres_application
                SET pap_domaine = :domaine,
                    pap_cle = :cle,
                    pap_valeur_json = :valeur_json,
                    pap_valeur_chiffree = :valeur_chiffree,
                    pap_algorithme_chiffrement = :algorithme,
                    pap_reference_coffre_secret = :reference_secret,
                    pap_description = :description,
                    pap_est_secret = :est_secret,
                    pap_est_systeme = :est_systeme,
                    pap_statut_id = :statut_id,
                    pap_modifie_le = NOW()
              WHERE pap_id = :id
                AND pap_supprime_le IS NULL',
            [
                'id' => $id,
                'domaine' => $data['pap_domaine'],
                'cle' => $data['pap_cle'],
                'valeur_json' => $payload['json'],
                'valeur_chiffree' => $payload['encrypted'],
                'algorithme' => $payload['algorithm'],
                'reference_secret' => $data['pap_reference_coffre_secret'],
                'description' => $data['pap_description'],
                'est_secret' => $data['pap_est_secret'],
                'est_systeme' => $data['pap_est_systeme'],
                'statut_id' => $data['pap_statut_id'],
            ]
        );
        $this->journaliser('settings.update', $id, $userId, $ip, $data['pap_domaine'] . '.' . $data['pap_cle']);
        return true;
    }

    public function supprimer(int $id, int $userId = 0, string $ip = ''): bool
    {
        $row = $this->trouver($id);
        if (!$row) {
            return false;
        }
        $this->db->execute(
            'UPDATE sav_parametres_application
                SET pap_supprime_le = NOW(), pap_modifie_le = NOW()
              WHERE pap_id = :id
                AND pap_supprime_le IS NULL',
            ['id' => $id]
        );
        $this->journaliser('settings.delete', $id, $userId, $ip, ($row['pap_domaine'] ?? '') . '.' . ($row['pap_cle'] ?? ''));
        return true;
    }

    public function sauvegarderMaintenance(array $input, int $userId = 0, string $ip = ''): void
    {
        $active = !empty($input['mode_maintenance']);
        $message = trim((string) ($input['message_public'] ?? 'Application temporairement indisponible pour maintenance.'));
        $roles = $this->listeDepuisTextarea((string) ($input['roles_autorises'] ?? 'super_administrateur'));
        $ips = $this->listeDepuisTextarea((string) ($input['ips_autorisees'] ?? "127.0.0.1\n::1"));
        $now = date('Y-m-d H:i:s');
        $previous = (bool) $this->valeur('application', 'mode_maintenance', false);

        $this->upsertValue('application', 'mode_maintenance', $active, 'Activation du mode maintenance', false, true);
        $this->upsertValue('maintenance', 'message_public', $message, 'Message public affiché pendant la maintenance', false, true);
        $this->upsertValue('maintenance', 'roles_autorises', $roles, 'Rôles autorisés pendant la maintenance', false, true);
        $this->upsertValue('maintenance', 'ips_autorisees', $ips, 'IP autorisées pendant la maintenance', false, true);
        $this->upsertValue('maintenance', 'modifie_le', $now, 'Dernière modification maintenance', false, true);

        if ($active && !$previous) {
            $this->upsertValue('maintenance', 'dernier_debut_le', $now, 'Dernier début du mode maintenance', false, true);
        }
        if (!$active && $previous) {
            $this->upsertValue('maintenance', 'derniere_fin_le', $now, 'Dernière fin du mode maintenance', false, true);
        }

        $this->journaliser('settings.maintenance', 0, $userId, $ip, $active ? 'maintenance.active' : 'maintenance.inactive');
    }

    public function etatMaintenance(): array
    {
        return [
            'mode_maintenance' => (bool) $this->valeur('application', 'mode_maintenance', false),
            'message_public' => (string) $this->valeur('maintenance', 'message_public', 'Application temporairement indisponible pour maintenance.'),
            'roles_autorises' => (array) $this->valeur('maintenance', 'roles_autorises', ['super_administrateur']),
            'ips_autorisees' => (array) $this->valeur('maintenance', 'ips_autorisees', ['127.0.0.1', '::1']),
            'dernier_debut_le' => $this->valeur('maintenance', 'dernier_debut_le', null),
            'derniere_fin_le' => $this->valeur('maintenance', 'derniere_fin_le', null),
            'modifie_le' => $this->valeur('maintenance', 'modifie_le', null),
        ];
    }

    public function statistiques(): array
    {
        return [
            'total' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_parametres_application WHERE pap_supprime_le IS NULL'),
            'secrets' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_parametres_application WHERE pap_supprime_le IS NULL AND pap_est_secret = 1'),
            'systeme' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_parametres_application WHERE pap_supprime_le IS NULL AND pap_est_systeme = 1'),
            'domaines' => (int) $this->db->fetchColumn('SELECT COUNT(DISTINCT pap_domaine) FROM sav_parametres_application WHERE pap_supprime_le IS NULL'),
        ];
    }

    public function statuts(): array
    {
        return $this->db->fetchAll(
            "SELECT sta_id, sta_code, sta_libelle
               FROM sav_statuts
              WHERE sta_supprime_le IS NULL
                AND sta_est_actif = 1
                AND sta_domaine IN ('general', 'configuration')
           ORDER BY sta_domaine ASC, sta_ordre ASC, sta_libelle ASC"
        );
    }

    public function export(): array
    {
        $rows = $this->lister([], 1000);
        return array_map(static function (array $row): array {
            $decoded = json_decode((string) ($row['pap_valeur_json'] ?? ''), true);
            return [
                'domaine' => $row['pap_domaine'] ?? '',
                'cle' => $row['pap_cle'] ?? '',
                'valeur' => !empty($row['pap_est_secret']) ? '[secret masqué]' : ($decoded['valeur'] ?? null),
                'est_secret' => (bool) ($row['pap_est_secret'] ?? false),
                'est_systeme' => (bool) ($row['pap_est_systeme'] ?? false),
                'description' => $row['pap_description'] ?? null,
                'modifie_le' => $row['pap_modifie_le'] ?? null,
            ];
        }, $rows);
    }

    public function upsertValue(string $domain, string $key, mixed $value, string $description = '', bool $secret = false, bool $system = true): void
    {
        $current = $this->trouverParCle($domain, $key);
        $data = [
            'pap_domaine' => $domain,
            'pap_cle' => $key,
            'value_type' => is_bool($value) ? 'bool' : (is_int($value) ? 'int' : (is_array($value) ? 'json' : 'string')),
            'plain_value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $value,
            'pap_description' => $description,
            'pap_est_secret' => $secret ? 1 : 0,
            'pap_est_systeme' => $system ? 1 : 0,
            'pap_statut_id' => $current['pap_statut_id'] ?? null,
            'pap_reference_coffre_secret' => $current['pap_reference_coffre_secret'] ?? null,
        ];

        if ($current) {
            $this->modifier((int) $current['pap_id'], $data);
        } else {
            $this->creer($data);
        }
    }

    private function normaliser(array $input, ?array $current = null): array
    {
        $domain = $this->slug((string) ($input['pap_domaine'] ?? $current['pap_domaine'] ?? 'application'));
        $key = $this->slug((string) ($input['pap_cle'] ?? $current['pap_cle'] ?? ''));
        if ($domain === '' || $key === '') {
            throw new \InvalidArgumentException('Le domaine et la clé sont obligatoires.');
        }

        return [
            'pap_domaine' => $domain,
            'pap_cle' => $key,
            'plain_value' => (string) ($input['plain_value'] ?? ''),
            'value_type' => (string) ($input['value_type'] ?? 'string'),
            'pap_description' => trim((string) ($input['pap_description'] ?? $current['pap_description'] ?? '')),
            'pap_est_secret' => !empty($input['pap_est_secret']) ? 1 : 0,
            'pap_est_systeme' => !empty($input['pap_est_systeme']) ? 1 : 0,
            'pap_statut_id' => ($input['pap_statut_id'] ?? '') !== '' ? (int) $input['pap_statut_id'] : null,
            'pap_reference_coffre_secret' => trim((string) ($input['pap_reference_coffre_secret'] ?? $current['pap_reference_coffre_secret'] ?? '')) ?: null,
        ];
    }

    private function payloadValeur(array $data, ?array $current = null): array
    {
        if (!empty($data['pap_est_secret'])) {
            $plain = (string) ($data['plain_value'] ?? '');
            if ($plain === '' && $current) {
                return [
                    'json' => $current['pap_valeur_json'] ?? json_encode(['secret' => true], JSON_UNESCAPED_UNICODE),
                    'encrypted' => $current['pap_valeur_chiffree'] ?? null,
                    'algorithm' => $current['pap_algorithme_chiffrement'] ?? 'AES-256-GCM',
                ];
            }
            return [
                'json' => json_encode(['secret' => true, 'updated_at' => date('c')], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'encrypted' => $plain !== '' ? $this->encryptSecret($plain) : null,
                'algorithm' => $plain !== '' ? 'AES-256-GCM' : null,
            ];
        }

        $value = $this->typedValue((string) ($data['plain_value'] ?? ''), (string) ($data['value_type'] ?? 'string'));
        return [
            'json' => json_encode(['valeur' => $value], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'encrypted' => null,
            'algorithm' => null,
        ];
    }

    private function typedValue(string $value, string $type): mixed
    {
        return match ($type) {
            'bool' => in_array(mb_strtolower(trim($value)), ['1', 'true', 'oui', 'yes', 'on'], true),
            'int' => (int) $value,
            'float' => (float) str_replace(',', '.', $value),
            'json', 'array' => $this->decodeJsonValue($value),
            default => $value,
        };
    }

    private function decodeJsonValue(string $value): mixed
    {
        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('La valeur JSON est invalide : ' . json_last_error_msg());
        }
        return $decoded;
    }

    private function enrichir(array $row): array
    {
        if (!empty($row['pap_est_secret'])) {
            $row['valeur_decodee'] = '[secret masqué]';
            $row['type_valeur'] = 'secret';
            return $row;
        }

        $decoded = json_decode((string) ($row['pap_valeur_json'] ?? ''), true);
        $value = is_array($decoded) && array_key_exists('valeur', $decoded) ? $decoded['valeur'] : null;
        $row['valeur_decodee'] = $value;
        $row['type_valeur'] = get_debug_type($value);
        return $row;
    }

    private function encryptSecret(string $plain): string
    {
        $key = $this->encryptionKey();
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new \RuntimeException('Le chiffrement du secret a échoué.');
        }
        return json_encode([
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ciphertext' => base64_encode($cipher),
        ], JSON_UNESCAPED_SLASHES);
    }

    private function encryptionKey(): string
    {
        $seed = defined('ENCRYPTION_KEY') ? (string) ENCRYPTION_KEY : '';
        if (strlen($seed) < 32) {
            throw new \RuntimeException('ENCRYPTION_KEY absente ou trop courte.');
        }
        return hash('sha256', $seed, true);
    }

    private function slug(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = preg_replace('/[^a-z0-9_.-]+/i', '_', $value) ?? '';
        return trim($value, '_');
    }

    private function listeDepuisTextarea(string $value): array
    {
        $items = preg_split('/[\r\n,;]+/', $value) ?: [];
        return array_values(array_filter(array_unique(array_map('trim', $items)), static fn(string $v): bool => $v !== ''));
    }

    private function journaliser(string $action, int $targetId, int $userId = 0, string $ip = '', string $reason = ''): void
    {
        try {
            $packedIp = filter_var($ip, FILTER_VALIDATE_IP) ? @inet_pton($ip) : null;
            $this->db->execute(
                'INSERT INTO sav_journaux_audit
                    (jau_utilisateur_id, jau_action, jau_table_cible, jau_id_cible, jau_raison, jau_adresse_ip, jau_metadata_json, jau_cree_le)
                 VALUES
                    (:user_id, :action, :table_cible, :id_cible, :raison, :ip, :metadata, NOW())',
                [
                    'user_id' => $userId > 0 ? $userId : null,
                    'action' => $action,
                    'table_cible' => 'sav_parametres_application',
                    'id_cible' => $targetId > 0 ? $targetId : null,
                    'raison' => $reason,
                    'ip' => $packedIp,
                    'metadata' => json_encode(['module' => 'Settings'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable) {
            // L'audit ne doit jamais bloquer la sauvegarde d'un paramètre.
        }
    }
}
