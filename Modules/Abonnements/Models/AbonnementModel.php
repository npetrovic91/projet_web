<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Abonnements\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Abonnements / formules / espaces applicatifs / modules sociétés.
 *
 * Tables couvertes :
 * - sav_abonnements_societes ;
 * - sav_formules_abonnement ;
 * - sav_espaces_applicatifs ;
 * - sav_modules_societes ;
 * - sav_modules ;
 * - sav_societes ;
 * - sav_statuts ;
 * - sav_journaux_audit.
 */
class AbonnementModel extends BaseModel
{
    protected string $table = 'sav_abonnements_societes';
    protected string $colPrefix = 'abo_';

    public function stats(?int $societeId = null): array
    {
        $params = [];
        $filtreSocieteAbo = '';
        $filtreSocieteEap = '';
        $filtreSocieteMos = '';
        if ($societeId !== null && $societeId > 0) {
            $params['societe_id'] = $societeId;
            $filtreSocieteAbo = ' AND abo.abo_societe_id = :societe_id';
            $filtreSocieteEap = ' AND eap.eap_societe_id = :societe_id';
            $filtreSocieteMos = ' AND mos.mos_societe_id = :societe_id';
        }

        $abonnements = $this->fetchOne("SELECT
                COUNT(*) AS abonnements_total,
                COUNT(DISTINCT abo.abo_societe_id) AS societes_abonnees,
                SUM(CASE WHEN abo.abo_termine_le IS NULL OR abo.abo_termine_le >= NOW() THEN 1 ELSE 0 END) AS abonnements_actifs,
                SUM(CASE WHEN abo.abo_termine_le IS NOT NULL AND abo.abo_termine_le < NOW() THEN 1 ELSE 0 END) AS abonnements_expires
            FROM sav_abonnements_societes abo
            WHERE abo.abo_supprime_le IS NULL AND abo.abo_archive_le IS NULL{$filtreSocieteAbo}", $params) ?? [];

        $espaces = $this->fetchOne("SELECT COUNT(*) AS espaces_total,
                SUM(CASE WHEN eap.eap_bloque_le IS NULL THEN 1 ELSE 0 END) AS espaces_actifs,
                SUM(CASE WHEN eap.eap_bloque_le IS NOT NULL THEN 1 ELSE 0 END) AS espaces_bloques
            FROM sav_espaces_applicatifs eap
            WHERE eap.eap_supprime_le IS NULL{$filtreSocieteEap}", $params) ?? [];

        $modules = $this->fetchOne("SELECT COUNT(*) AS modules_societes_total,
                COUNT(DISTINCT mos.mos_module_id) AS modules_distincts,
                COUNT(DISTINCT mos.mos_societe_id) AS societes_avec_modules
            FROM sav_modules_societes mos
            WHERE mos.mos_supprime_le IS NULL AND mos.mos_archive_le IS NULL{$filtreSocieteMos}", $params) ?? [];

        $formules = $this->fetchOne("SELECT COUNT(*) AS formules_total
            FROM sav_formules_abonnement fab
            WHERE fab.fab_supprime_le IS NULL AND fab.fab_archive_le IS NULL") ?? [];

        return [
            'abonnements_total' => (int)($abonnements['abonnements_total'] ?? 0),
            'societes_abonnees' => (int)($abonnements['societes_abonnees'] ?? 0),
            'abonnements_actifs' => (int)($abonnements['abonnements_actifs'] ?? 0),
            'abonnements_expires' => (int)($abonnements['abonnements_expires'] ?? 0),
            'espaces_total' => (int)($espaces['espaces_total'] ?? 0),
            'espaces_actifs' => (int)($espaces['espaces_actifs'] ?? 0),
            'espaces_bloques' => (int)($espaces['espaces_bloques'] ?? 0),
            'modules_societes_total' => (int)($modules['modules_societes_total'] ?? 0),
            'modules_distincts' => (int)($modules['modules_distincts'] ?? 0),
            'societes_avec_modules' => (int)($modules['societes_avec_modules'] ?? 0),
            'formules_total' => (int)($formules['formules_total'] ?? 0),
        ];
    }

    public function listerAbonnements(array $filters = []): array
    {
        $where = ['abo.abo_supprime_le IS NULL', 'abo.abo_archive_le IS NULL'];
        $params = [];
        if (!empty($filters['societe_id'])) {
            $where[] = 'abo.abo_societe_id = :societe_id';
            $params['societe_id'] = (int)$filters['societe_id'];
        }
        if (!empty($filters['formule_id'])) {
            $where[] = 'abo.abo_formule_abonnement_id = :formule_id';
            $params['formule_id'] = (int)$filters['formule_id'];
        }
        if (!empty($filters['statut_abonnement_id'])) {
            $where[] = 'abo.abo_statut_abonnement_id = :statut_abonnement_id';
            $params['statut_abonnement_id'] = (int)$filters['statut_abonnement_id'];
        }
        if (!empty($filters['q'])) {
            $where[] = '(soc.soc_nom LIKE :q OR soc.soc_code LIKE :q OR fab.fab_nom LIKE :q OR fab.fab_code LIKE :q)';
            $params['q'] = '%' . (string)$filters['q'] . '%';
        }
        return $this->fetchAll("SELECT abo.*, soc.soc_nom, soc.soc_code, fab.fab_code, fab.fab_nom,
                sta_abo.sta_libelle AS statut_abonnement_libelle,
                sta_pay.sta_libelle AS statut_paiement_libelle
            FROM sav_abonnements_societes abo
            INNER JOIN sav_societes soc ON soc.soc_id = abo.abo_societe_id
            LEFT JOIN sav_formules_abonnement fab ON fab.fab_id = abo.abo_formule_abonnement_id
            LEFT JOIN sav_statuts sta_abo ON sta_abo.sta_id = abo.abo_statut_abonnement_id
            LEFT JOIN sav_statuts sta_pay ON sta_pay.sta_id = abo.abo_statut_paiement_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY soc.soc_nom ASC, abo.abo_debute_le DESC, abo.abo_id DESC", $params);
    }

    public function trouverAbonnement(int $id): ?array
    {
        return $this->fetchOne("SELECT abo.*, soc.soc_nom, fab.fab_nom
            FROM sav_abonnements_societes abo
            LEFT JOIN sav_societes soc ON soc.soc_id = abo.abo_societe_id
            LEFT JOIN sav_formules_abonnement fab ON fab.fab_id = abo.abo_formule_abonnement_id
            WHERE abo.abo_id = :id AND abo.abo_supprime_le IS NULL LIMIT 1", ['id' => $id]);
    }

    public function enregistrerAbonnement(array $data, ?int $id, ?int $userId): int
    {
        $payload = [
            'abo_societe_id' => (int)($data['abo_societe_id'] ?? 0),
            'abo_formule_abonnement_id' => $this->nullableInt($data['abo_formule_abonnement_id'] ?? null),
            'abo_statut_abonnement_id' => $this->nullableInt($data['abo_statut_abonnement_id'] ?? null),
            'abo_statut_paiement_id' => $this->nullableInt($data['abo_statut_paiement_id'] ?? null),
            'abo_debute_le' => $this->nullableDateTime($data['abo_debute_le'] ?? null),
            'abo_termine_le' => $this->nullableDateTime($data['abo_termine_le'] ?? null),
        ];
        if ($id) {
            $payload['abo_modifie_par_utilisateur_id'] = $userId;
            $sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($payload)));
            $payload['id'] = $id;
            $this->query("UPDATE sav_abonnements_societes SET {$sets}, abo_modifie_le = NOW() WHERE abo_id = :id", $payload);
            return $id;
        }
        $payload['abo_cree_par_utilisateur_id'] = $userId;
        $columns = implode(', ', array_keys($payload));
        $placeholders = ':' . implode(', :', array_keys($payload));
        $this->query("INSERT INTO sav_abonnements_societes ({$columns}) VALUES ({$placeholders})", $payload);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Fait passer une société non abonnée à abonnée en une seule opération
     * atomique : abonnement + espace applicatif actif. Cahier des charges
     * (ACC-001) : "Une société non abonnée [...] peut devenir abonnée sans
     * perte d'historique." Avant cette méthode, créer un abonnement et
     * créer l'espace applicatif correspondant étaient deux actions
     * manuelles distinctes (deux formulaires séparés) : rien n'empêchait
     * d'oublier la seconde, laissant la société "abonnée" en base mais
     * sans accès applicatif réel (TenantEntitlementMiddleware exige les
     * deux). Aucune donnée préexistante (relations, contacts, emails
     * reçus) n'est touchée : seul soc_id reste la clé stable qui les relie.
     */
    public function souscrireSociete(int $societeId, int $formuleId, ?int $userId): int
    {
        $this->beginTransaction();
        try {
            $statutActif = $this->statutGeneralId('actif');

            $aboId = $this->enregistrerAbonnement([
                'abo_societe_id' => $societeId,
                'abo_formule_abonnement_id' => $formuleId,
                'abo_statut_abonnement_id' => $statutActif,
                'abo_statut_paiement_id' => $statutActif,
                'abo_debute_le' => date('Y-m-d H:i:s'),
            ], null, $userId);

            $eapId = $this->enregistrerEspace([
                'eap_societe_id' => $societeId,
                'eap_abonnement_societe_id' => $aboId,
                'eap_statut_id' => $statutActif,
            ], null, $userId);

            $this->commit();
            return $eapId;
        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Historique conservé pour une société, accumulé qu'elle ait été
     * abonnée ou non (relations inter-sociétés, contacts, emails reçus).
     * Affiché à la souscription pour prouver concrètement qu'aucun
     * historique n'a été perdu (ACC-001).
     */
    public function historiqueConserve(int $societeId): array
    {
        return [
            'relations' => (int) $this->query(
                "SELECT COUNT(*) FROM sav_relations_societes
                 WHERE (rso_societe_source_id = :id OR rso_societe_cible_id = :id2) AND rso_supprime_le IS NULL",
                ['id' => $societeId, 'id2' => $societeId]
            )->fetchColumn(),
            'contacts' => (int) $this->query(
                "SELECT COUNT(*) FROM sav_contacts_societes WHERE cts_societe_id = :id AND cts_supprime_le IS NULL",
                ['id' => $societeId]
            )->fetchColumn(),
            'emails_recus' => (int) $this->query(
                "SELECT COUNT(*) FROM sav_journaux_emails WHERE jme_societe_destinataire_id = :id",
                ['id' => $societeId]
            )->fetchColumn(),
        ];
    }

    private function statutGeneralId(string $code): ?int
    {
        $id = $this->query(
            "SELECT sta_id FROM sav_statuts WHERE sta_domaine = 'general' AND sta_code = :code AND sta_supprime_le IS NULL LIMIT 1",
            ['code' => $code]
        )->fetchColumn();
        return $id !== false && $id !== null ? (int) $id : null;
    }

    public function supprimerAbonnement(int $id, ?int $userId): void
    {
        $this->query("UPDATE sav_abonnements_societes
            SET abo_supprime_le = NOW(), abo_supprime_par_utilisateur_id = :user_id
            WHERE abo_id = :id", ['id' => $id, 'user_id' => $userId]);
    }

    public function listerFormules(array $filters = []): array
    {
        $where = ['fab.fab_supprime_le IS NULL', 'fab.fab_archive_le IS NULL'];
        $params = [];
        if (!empty($filters['q'])) {
            $where[] = '(fab.fab_code LIKE :q OR fab.fab_nom LIKE :q OR fab.fab_description LIKE :q)';
            $params['q'] = '%' . (string)$filters['q'] . '%';
        }
        return $this->fetchAll("SELECT fab.*, sta.sta_libelle AS statut_libelle,
                COUNT(DISTINCT abo.abo_id) AS abonnements_total,
                COUNT(DISTINCT mos.mos_id) AS modules_societes_total
            FROM sav_formules_abonnement fab
            LEFT JOIN sav_statuts sta ON sta.sta_id = fab.fab_statut_id
            LEFT JOIN sav_abonnements_societes abo ON abo.abo_formule_abonnement_id = fab.fab_id AND abo.abo_supprime_le IS NULL AND abo.abo_archive_le IS NULL
            LEFT JOIN sav_modules_societes mos ON mos.mos_formule_abonnement_id = fab.fab_id AND mos.mos_supprime_le IS NULL AND mos.mos_archive_le IS NULL
            WHERE " . implode(' AND ', $where) . "
            GROUP BY fab.fab_id
            ORDER BY fab.fab_nom ASC", $params);
    }

    public function trouverFormule(int $id): ?array
    {
        return $this->fetchOne("SELECT * FROM sav_formules_abonnement WHERE fab_id = :id AND fab_supprime_le IS NULL LIMIT 1", ['id' => $id]);
    }

    public function enregistrerFormule(array $data, ?int $id, ?int $userId): int
    {
        $fonctionsJson = trim((string)($data['fab_fonctions_json'] ?? ''));
        $payload = [
            'fab_code' => trim((string)($data['fab_code'] ?? '')),
            'fab_nom' => trim((string)($data['fab_nom'] ?? '')),
            'fab_description' => trim((string)($data['fab_description'] ?? '')) ?: null,
            'fab_mode_tarif' => trim((string)($data['fab_mode_tarif'] ?? 'personnalise')) ?: 'personnalise',
            'fab_fonctions_json' => $fonctionsJson !== '' ? $fonctionsJson : null,
            'fab_statut_id' => $this->nullableInt($data['fab_statut_id'] ?? null),
        ];
        if ($id) {
            $payload['fab_modifie_par_utilisateur_id'] = $userId;
            $sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($payload)));
            $payload['id'] = $id;
            $this->query("UPDATE sav_formules_abonnement SET {$sets}, fab_modifie_le = NOW() WHERE fab_id = :id", $payload);
            return $id;
        }
        $payload['fab_cree_par_utilisateur_id'] = $userId;
        $columns = implode(', ', array_keys($payload));
        $placeholders = ':' . implode(', :', array_keys($payload));
        $this->query("INSERT INTO sav_formules_abonnement ({$columns}) VALUES ({$placeholders})", $payload);
        return (int)$this->pdo->lastInsertId();
    }

    public function supprimerFormule(int $id, ?int $userId): void
    {
        $this->query("UPDATE sav_formules_abonnement
            SET fab_supprime_le = NOW(), fab_supprime_par_utilisateur_id = :user_id
            WHERE fab_id = :id", ['id' => $id, 'user_id' => $userId]);
    }

    public function listerEspaces(array $filters = []): array
    {
        $where = ['eap.eap_supprime_le IS NULL'];
        $params = [];
        if (!empty($filters['societe_id'])) {
            $where[] = 'eap.eap_societe_id = :societe_id';
            $params['societe_id'] = (int)$filters['societe_id'];
        }
        if (!empty($filters['bloque'])) {
            $where[] = $filters['bloque'] === 'oui' ? 'eap.eap_bloque_le IS NOT NULL' : 'eap.eap_bloque_le IS NULL';
        }
        return $this->fetchAll("SELECT eap.*, soc.soc_nom, soc.soc_code, sta.sta_libelle AS statut_libelle,
                fab.fab_nom, abo.abo_debute_le, abo.abo_termine_le
            FROM sav_espaces_applicatifs eap
            INNER JOIN sav_societes soc ON soc.soc_id = eap.eap_societe_id
            LEFT JOIN sav_abonnements_societes abo ON abo.abo_id = eap.eap_abonnement_societe_id
            LEFT JOIN sav_formules_abonnement fab ON fab.fab_id = abo.abo_formule_abonnement_id
            LEFT JOIN sav_statuts sta ON sta.sta_id = eap.eap_statut_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY soc.soc_nom ASC, eap.eap_id DESC", $params);
    }

    public function trouverEspace(int $id): ?array
    {
        return $this->fetchOne("SELECT * FROM sav_espaces_applicatifs WHERE eap_id = :id AND eap_supprime_le IS NULL LIMIT 1", ['id' => $id]);
    }

    public function enregistrerEspace(array $data, ?int $id, ?int $userId): int
    {
        $payload = [
            'eap_societe_id' => (int)($data['eap_societe_id'] ?? 0),
            'eap_abonnement_societe_id' => $this->nullableInt($data['eap_abonnement_societe_id'] ?? null),
            'eap_statut_id' => $this->nullableInt($data['eap_statut_id'] ?? null),
            'eap_bloque_le' => !empty($data['eap_est_bloque']) ? date('Y-m-d H:i:s') : null,
            'eap_motif_blocage' => trim((string)($data['eap_motif_blocage'] ?? '')) ?: null,
        ];
        if ($id) {
            $payload['eap_modifie_par_utilisateur_id'] = $userId;
            $sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($payload)));
            $payload['id'] = $id;
            $this->query("UPDATE sav_espaces_applicatifs SET {$sets}, eap_modifie_le = NOW() WHERE eap_id = :id", $payload);
            return $id;
        }
        $payload['eap_cree_par_utilisateur_id'] = $userId;
        $columns = implode(', ', array_keys($payload));
        $placeholders = ':' . implode(', :', array_keys($payload));
        $this->query("INSERT INTO sav_espaces_applicatifs ({$columns}) VALUES ({$placeholders})", $payload);
        return (int)$this->pdo->lastInsertId();
    }

    public function supprimerEspace(int $id, ?int $userId): void
    {
        $this->query("UPDATE sav_espaces_applicatifs
            SET eap_supprime_le = NOW(), eap_supprime_par_utilisateur_id = :user_id
            WHERE eap_id = :id", ['id' => $id, 'user_id' => $userId]);
    }

    public function listerModulesSocietes(array $filters = []): array
    {
        $where = ['mos.mos_supprime_le IS NULL', 'mos.mos_archive_le IS NULL'];
        $params = [];
        if (!empty($filters['societe_id'])) {
            $where[] = 'mos.mos_societe_id = :societe_id';
            $params['societe_id'] = (int)$filters['societe_id'];
        }
        if (!empty($filters['module_id'])) {
            $where[] = 'mos.mos_module_id = :module_id';
            $params['module_id'] = (int)$filters['module_id'];
        }
        return $this->fetchAll("SELECT mos.*, soc.soc_nom, modl.mod_code, modl.mod_nom, modl.mod_version,
                fab.fab_nom, sta.sta_libelle AS statut_libelle
            FROM sav_modules_societes mos
            INNER JOIN sav_societes soc ON soc.soc_id = mos.mos_societe_id
            INNER JOIN sav_modules modl ON modl.mod_id = mos.mos_module_id
            LEFT JOIN sav_formules_abonnement fab ON fab.fab_id = mos.mos_formule_abonnement_id
            LEFT JOIN sav_statuts sta ON sta.sta_id = mos.mos_statut_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY soc.soc_nom ASC, modl.mod_nom ASC", $params);
    }

    public function trouverModuleSociete(int $id): ?array
    {
        return $this->fetchOne("SELECT * FROM sav_modules_societes WHERE mos_id = :id AND mos_supprime_le IS NULL LIMIT 1", ['id' => $id]);
    }

    public function enregistrerModuleSociete(array $data, ?int $id, ?int $userId): int
    {
        $payload = [
            'mos_societe_id' => (int)($data['mos_societe_id'] ?? 0),
            'mos_module_id' => (int)($data['mos_module_id'] ?? 0),
            'mos_formule_abonnement_id' => $this->nullableInt($data['mos_formule_abonnement_id'] ?? null),
            'mos_active_par_utilisateur_id' => $userId,
            'mos_debute_le' => $this->nullableDateTime($data['mos_debute_le'] ?? null) ?? date('Y-m-d H:i:s'),
            'mos_termine_le' => $this->nullableDateTime($data['mos_termine_le'] ?? null),
            'mos_statut_id' => $this->nullableInt($data['mos_statut_id'] ?? null),
        ];
        if ($id) {
            $payload['mos_modifie_par_utilisateur_id'] = $userId;
            $sets = implode(', ', array_map(fn($k) => "{$k} = :{$k}", array_keys($payload)));
            $payload['id'] = $id;
            $this->query("UPDATE sav_modules_societes SET {$sets}, mos_modifie_le = NOW() WHERE mos_id = :id", $payload);
            return $id;
        }
        $payload['mos_cree_par_utilisateur_id'] = $userId;
        $columns = implode(', ', array_keys($payload));
        $placeholders = ':' . implode(', :', array_keys($payload));
        $this->query("INSERT INTO sav_modules_societes ({$columns}) VALUES ({$placeholders})", $payload);
        return (int)$this->pdo->lastInsertId();
    }

    public function supprimerModuleSociete(int $id, ?int $userId): void
    {
        $this->query("UPDATE sav_modules_societes
            SET mos_supprime_le = NOW(), mos_supprime_par_utilisateur_id = :user_id
            WHERE mos_id = :id", ['id' => $id, 'user_id' => $userId]);
    }

    public function references(): array
    {
        return [
            'societes' => $this->fetchAll("SELECT soc_id, soc_code, soc_nom FROM sav_societes WHERE soc_supprime_le IS NULL AND soc_archive_le IS NULL ORDER BY soc_nom"),
            'formules' => $this->fetchAll("SELECT fab_id, fab_code, fab_nom FROM sav_formules_abonnement WHERE fab_supprime_le IS NULL AND fab_archive_le IS NULL ORDER BY fab_nom"),
            'abonnements' => $this->fetchAll("SELECT abo.abo_id, abo.abo_societe_id, soc.soc_nom, fab.fab_nom
                FROM sav_abonnements_societes abo
                INNER JOIN sav_societes soc ON soc.soc_id = abo.abo_societe_id
                LEFT JOIN sav_formules_abonnement fab ON fab.fab_id = abo.abo_formule_abonnement_id
                WHERE abo.abo_supprime_le IS NULL AND abo.abo_archive_le IS NULL
                ORDER BY soc.soc_nom"),
            'modules' => $this->fetchAll("SELECT mod_id, mod_code, mod_nom FROM sav_modules WHERE mod_supprime_le IS NULL AND mod_archive_le IS NULL ORDER BY mod_nom"),
            'statuts' => $this->fetchAll("SELECT sta_id, sta_domaine, sta_code, sta_libelle FROM sav_statuts WHERE sta_supprime_le IS NULL ORDER BY sta_domaine, sta_ordre, sta_libelle"),
        ];
    }

    public function export(?int $societeId = null): array
    {
        $filters = $societeId ? ['societe_id' => $societeId] : [];
        return [
            'stats' => $this->stats($societeId),
            'abonnements' => $this->listerAbonnements($filters),
            'formules' => $this->listerFormules(),
            'espaces_applicatifs' => $this->listerEspaces($filters),
            'modules_societes' => $this->listerModulesSocietes($filters),
        ];
    }

    public function audit(string $action, string $table, int $id, ?int $userId, ?string $ip, array $meta = []): void
    {
        $this->query("INSERT INTO sav_journaux_audit
            (jau_utilisateur_id, jau_action, jau_table_cible, jau_id_cible, jau_adresse_ip, jau_metadata_json, jau_cree_le)
            VALUES (:user_id, :action, :table_cible, :id_cible, INET6_ATON(:ip), :meta, NOW())", [
            'user_id' => $userId,
            'action' => $action,
            'table_cible' => $table,
            'id_cible' => $id,
            'ip' => $ip ?: '127.0.0.1',
            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || (int)$value <= 0) {
            return null;
        }
        return (int)$value;
    }

    private function nullableDateTime(mixed $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value . ' 00:00:00';
        }
        return $value;
    }
}
