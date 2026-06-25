<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Horaires\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Horaires / calendriers de travail / exceptions horaires.
 *
 * Tables SQL réelles utilisées :
 * - sav_horaires_travail
 * - sav_exceptions_horaires_travail
 * - sav_societes
 * - sav_departements
 * - sav_services
 * - sav_equipes
 * - sav_utilisateurs
 * - sav_profils_utilisateurs
 * - sav_journaux_audit
 */
class HorairesModel extends BaseModel
{
    protected string $table = 'sav_horaires_travail';
    protected string $colPrefix = 'htr_';

    public function stats(): array
    {
        return [
            'horaires_total' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_horaires_travail WHERE htr_supprime_le IS NULL"),
            'horaires_fermes' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_horaires_travail WHERE htr_supprime_le IS NULL AND htr_est_ferme = 1"),
            'exceptions_total' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_exceptions_horaires_travail WHERE eht_supprime_le IS NULL"),
            'exceptions_futures' => (int) $this->db->fetchColumn("SELECT COUNT(*) FROM sav_exceptions_horaires_travail WHERE eht_supprime_le IS NULL AND eht_date >= CURDATE()"),
        ];
    }

    public function horaires(array $filters = [], int $limit = 300): array
    {
        $where = ['h.htr_supprime_le IS NULL'];
        $params = [];
        $this->applyCommonFilters($where, $params, $filters, 'h.htr_');
        if (!empty($filters['jour_semaine'])) {
            $where[] = 'h.htr_jour_semaine = :jour_semaine';
            $params['jour_semaine'] = (int) $filters['jour_semaine'];
        }
        $params['limit'] = max(1, min(1000, $limit));

        return $this->db->fetchAll(
            "SELECT h.*, soc.soc_nom AS societe_nom,
                    " . $this->porteeSql('h.htr_portee_type', 'h.htr_portee_id') . " AS portee_libelle
             FROM sav_horaires_travail h
             INNER JOIN sav_societes soc ON soc.soc_id = h.htr_societe_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY soc.soc_nom ASC, h.htr_portee_type ASC, h.htr_portee_id ASC, h.htr_jour_semaine ASC, h.htr_ouvre_a ASC
             LIMIT :limit",
            $params
        );
    }

    public function exceptions(array $filters = [], int $limit = 300): array
    {
        $where = ['e.eht_supprime_le IS NULL'];
        $params = [];
        $this->applyCommonFilters($where, $params, $filters, 'e.eht_');
        if (!empty($filters['date_debut'])) {
            $where[] = 'e.eht_date >= :date_debut';
            $params['date_debut'] = (string) $filters['date_debut'];
        }
        if (!empty($filters['date_fin'])) {
            $where[] = 'e.eht_date <= :date_fin';
            $params['date_fin'] = (string) $filters['date_fin'];
        }
        if (($filters['periode'] ?? '') === 'futures') {
            $where[] = 'e.eht_date >= CURDATE()';
        } elseif (($filters['periode'] ?? '') === 'passees') {
            $where[] = 'e.eht_date < CURDATE()';
        }
        $params['limit'] = max(1, min(1000, $limit));

        return $this->db->fetchAll(
            "SELECT e.*, soc.soc_nom AS societe_nom,
                    " . $this->porteeSql('e.eht_portee_type', 'e.eht_portee_id') . " AS portee_libelle
             FROM sav_exceptions_horaires_travail e
             INNER JOIN sav_societes soc ON soc.soc_id = e.eht_societe_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY e.eht_date ASC, soc.soc_nom ASC, e.eht_portee_type ASC, e.eht_portee_id ASC
             LIMIT :limit",
            $params
        );
    }

    public function calendrier(array $filters = []): array
    {
        $horaires = $this->horaires($filters, 1000);
        $exceptions = $this->exceptions($filters + ['periode' => $filters['periode'] ?? 'futures'], 1000);
        $groupes = [];
        foreach ($horaires as $h) {
            $key = $this->calendarKey($h['htr_societe_id'], $h['htr_portee_type'], $h['htr_portee_id']);
            $groupes[$key]['societe_nom'] = $h['societe_nom'] ?? '';
            $groupes[$key]['portee_type'] = $h['htr_portee_type'];
            $groupes[$key]['portee_id'] = $h['htr_portee_id'];
            $groupes[$key]['portee_libelle'] = $h['portee_libelle'] ?: 'Société entière';
            $groupes[$key]['jours'][(int)$h['htr_jour_semaine']][] = $h;
            $groupes[$key]['exceptions'] ??= [];
        }
        foreach ($exceptions as $e) {
            $key = $this->calendarKey($e['eht_societe_id'], $e['eht_portee_type'], $e['eht_portee_id']);
            $groupes[$key]['societe_nom'] = $e['societe_nom'] ?? '';
            $groupes[$key]['portee_type'] = $e['eht_portee_type'];
            $groupes[$key]['portee_id'] = $e['eht_portee_id'];
            $groupes[$key]['portee_libelle'] = $e['portee_libelle'] ?: 'Société entière';
            $groupes[$key]['jours'] ??= [];
            $groupes[$key]['exceptions'][] = $e;
        }
        uasort($groupes, static fn($a, $b) => [$a['societe_nom'], $a['portee_type'], (string)($a['portee_libelle'] ?? '')] <=> [$b['societe_nom'], $b['portee_type'], (string)($b['portee_libelle'] ?? '')]);
        return array_values($groupes);
    }

    public function findHoraire(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT h.*, soc.soc_nom AS societe_nom, " . $this->porteeSql('h.htr_portee_type', 'h.htr_portee_id') . " AS portee_libelle
             FROM sav_horaires_travail h
             INNER JOIN sav_societes soc ON soc.soc_id = h.htr_societe_id
             WHERE h.htr_id = :id AND h.htr_supprime_le IS NULL
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function createHoraire(array $data, int $userId): int
    {
        $payload = $this->horairePayload($data);
        $this->db->execute(
            "INSERT INTO sav_horaires_travail
                (htr_societe_id, htr_portee_type, htr_portee_id, htr_jour_semaine, htr_ouvre_a, htr_ferme_a, htr_est_ferme)
             VALUES
                (:societe_id, :portee_type, :portee_id, :jour_semaine, :ouvre_a, :ferme_a, :est_ferme)",
            $payload
        );
        $id = (int) $this->db->lastInsertId();
        $this->audit($userId, 'horaire.creer', 'sav_horaires_travail', $id, 'Création horaire de travail');
        return $id;
    }

    public function updateHoraire(int $id, array $data, int $userId): bool
    {
        if (!$this->findHoraire($id)) {
            throw new \InvalidArgumentException('Horaire introuvable.');
        }
        $payload = $this->horairePayload($data);
        $payload['id'] = $id;
        $ok = $this->db->execute(
            "UPDATE sav_horaires_travail
             SET htr_societe_id = :societe_id,
                 htr_portee_type = :portee_type,
                 htr_portee_id = :portee_id,
                 htr_jour_semaine = :jour_semaine,
                 htr_ouvre_a = :ouvre_a,
                 htr_ferme_a = :ferme_a,
                 htr_est_ferme = :est_ferme
             WHERE htr_id = :id AND htr_supprime_le IS NULL",
            $payload
        );
        if ($ok) {
            $this->audit($userId, 'horaire.modifier', 'sav_horaires_travail', $id, 'Modification horaire de travail');
        }
        return $ok;
    }

    public function deleteHoraire(int $id, int $userId): bool
    {
        $ok = $this->db->execute(
            "UPDATE sav_horaires_travail SET htr_supprime_le = NOW() WHERE htr_id = :id AND htr_supprime_le IS NULL",
            ['id' => $id]
        );
        if ($ok) {
            $this->audit($userId, 'horaire.supprimer', 'sav_horaires_travail', $id, 'Suppression logique horaire de travail');
        }
        return $ok;
    }

    public function findException(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT e.*, soc.soc_nom AS societe_nom, " . $this->porteeSql('e.eht_portee_type', 'e.eht_portee_id') . " AS portee_libelle
             FROM sav_exceptions_horaires_travail e
             INNER JOIN sav_societes soc ON soc.soc_id = e.eht_societe_id
             WHERE e.eht_id = :id AND e.eht_supprime_le IS NULL
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function createException(array $data, int $userId): int
    {
        $payload = $this->exceptionPayload($data);
        $this->db->execute(
            "INSERT INTO sav_exceptions_horaires_travail
                (eht_societe_id, eht_portee_type, eht_portee_id, eht_date, eht_ouvre_a, eht_ferme_a, eht_est_ferme, eht_raison)
             VALUES
                (:societe_id, :portee_type, :portee_id, :date, :ouvre_a, :ferme_a, :est_ferme, :raison)",
            $payload
        );
        $id = (int) $this->db->lastInsertId();
        $this->audit($userId, 'exception_horaire.creer', 'sav_exceptions_horaires_travail', $id, 'Création exception horaire');
        return $id;
    }

    public function updateException(int $id, array $data, int $userId): bool
    {
        if (!$this->findException($id)) {
            throw new \InvalidArgumentException('Exception horaire introuvable.');
        }
        $payload = $this->exceptionPayload($data);
        $payload['id'] = $id;
        $ok = $this->db->execute(
            "UPDATE sav_exceptions_horaires_travail
             SET eht_societe_id = :societe_id,
                 eht_portee_type = :portee_type,
                 eht_portee_id = :portee_id,
                 eht_date = :date,
                 eht_ouvre_a = :ouvre_a,
                 eht_ferme_a = :ferme_a,
                 eht_est_ferme = :est_ferme,
                 eht_raison = :raison
             WHERE eht_id = :id AND eht_supprime_le IS NULL",
            $payload
        );
        if ($ok) {
            $this->audit($userId, 'exception_horaire.modifier', 'sav_exceptions_horaires_travail', $id, 'Modification exception horaire');
        }
        return $ok;
    }

    public function deleteException(int $id, int $userId): bool
    {
        $ok = $this->db->execute(
            "UPDATE sav_exceptions_horaires_travail SET eht_supprime_le = NOW() WHERE eht_id = :id AND eht_supprime_le IS NULL",
            ['id' => $id]
        );
        if ($ok) {
            $this->audit($userId, 'exception_horaire.supprimer', 'sav_exceptions_horaires_travail', $id, 'Suppression logique exception horaire');
        }
        return $ok;
    }

    public function refs(): array
    {
        return [
            'societes' => $this->db->fetchAll("SELECT soc_id, soc_nom FROM sav_societes WHERE soc_supprime_le IS NULL AND soc_archive_le IS NULL ORDER BY soc_nom ASC"),
            'departements' => $this->db->fetchAll("SELECT dep_id, dep_societe_id, dep_nom FROM sav_departements WHERE dep_supprime_le IS NULL AND dep_archive_le IS NULL ORDER BY dep_nom ASC"),
            'services' => $this->db->fetchAll("SELECT srv_id, srv_societe_id, srv_nom FROM sav_services WHERE srv_supprime_le IS NULL AND srv_archive_le IS NULL ORDER BY srv_nom ASC"),
            'equipes' => $this->db->fetchAll("SELECT equ_id, equ_societe_id, equ_nom FROM sav_equipes WHERE equ_supprime_le IS NULL AND equ_archive_le IS NULL ORDER BY equ_nom ASC"),
            'utilisateurs' => $this->db->fetchAll("SELECT u.use_id, u.use_email, CONCAT(COALESCE(p.pui_prenom, ''), ' ', COALESCE(p.pui_nom, '')) AS nom_complet FROM sav_utilisateurs u LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.use_id AND p.pui_supprime_le IS NULL WHERE u.use_supprime_le IS NULL ORDER BY p.pui_nom ASC, p.pui_prenom ASC, u.use_email ASC"),
            'jours' => $this->jours(),
            'portees' => $this->portees(),
        ];
    }

    public function export(array $filters = []): array
    {
        return [
            'horaires' => $this->horaires($filters, 1000),
            'exceptions' => $this->exceptions($filters, 1000),
            'calendrier' => $this->calendrier($filters),
            'exporte_le' => date('c'),
        ];
    }

    private function horairePayload(array $data): array
    {
        $societeId = (int)($data['htr_societe_id'] ?? $data['societe_id'] ?? 0);
        if ($societeId <= 0) {
            throw new \InvalidArgumentException('La société est obligatoire.');
        }
        $jour = (int)($data['htr_jour_semaine'] ?? $data['jour_semaine'] ?? 0);
        if ($jour < 1 || $jour > 7) {
            throw new \InvalidArgumentException('Le jour doit être compris entre 1 et 7.');
        }
        $ferme = !empty($data['htr_est_ferme'] ?? $data['est_ferme'] ?? false) ? 1 : 0;
        return [
            'societe_id' => $societeId,
            'portee_type' => $this->porteeType((string)($data['htr_portee_type'] ?? $data['portee_type'] ?? 'societe')),
            'portee_id' => $this->nullableInt($data['htr_portee_id'] ?? $data['portee_id'] ?? null),
            'jour_semaine' => $jour,
            'ouvre_a' => $ferme ? null : $this->timeOrNull($data['htr_ouvre_a'] ?? $data['ouvre_a'] ?? null),
            'ferme_a' => $ferme ? null : $this->timeOrNull($data['htr_ferme_a'] ?? $data['ferme_a'] ?? null),
            'est_ferme' => $ferme,
        ];
    }

    private function exceptionPayload(array $data): array
    {
        $societeId = (int)($data['eht_societe_id'] ?? $data['societe_id'] ?? 0);
        if ($societeId <= 0) {
            throw new \InvalidArgumentException('La société est obligatoire.');
        }
        $date = trim((string)($data['eht_date'] ?? $data['date'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException('La date de l’exception est obligatoire au format AAAA-MM-JJ.');
        }
        $ferme = !empty($data['eht_est_ferme'] ?? $data['est_ferme'] ?? false) ? 1 : 0;
        return [
            'societe_id' => $societeId,
            'portee_type' => $this->porteeType((string)($data['eht_portee_type'] ?? $data['portee_type'] ?? 'societe')),
            'portee_id' => $this->nullableInt($data['eht_portee_id'] ?? $data['portee_id'] ?? null),
            'date' => $date,
            'ouvre_a' => $ferme ? null : $this->timeOrNull($data['eht_ouvre_a'] ?? $data['ouvre_a'] ?? null),
            'ferme_a' => $ferme ? null : $this->timeOrNull($data['eht_ferme_a'] ?? $data['ferme_a'] ?? null),
            'est_ferme' => $ferme,
            'raison' => $this->nullOrString($data['eht_raison'] ?? $data['raison'] ?? null, 255),
        ];
    }

    private function applyCommonFilters(array &$where, array &$params, array $filters, string $prefix): void
    {
        if (!empty($filters['societe_id'])) {
            $where[] = $prefix . 'societe_id = :societe_id';
            $params['societe_id'] = (int) $filters['societe_id'];
        }
        if (!empty($filters['portee_type'])) {
            $where[] = $prefix . 'portee_type = :portee_type';
            $params['portee_type'] = $this->porteeType((string) $filters['portee_type']);
        }
        if (!empty($filters['portee_id'])) {
            $where[] = $prefix . 'portee_id = :portee_id';
            $params['portee_id'] = (int) $filters['portee_id'];
        }
        if (($filters['etat'] ?? '') === 'ferme') {
            $where[] = $prefix . 'est_ferme = 1';
        } elseif (($filters['etat'] ?? '') === 'ouvert') {
            $where[] = $prefix . 'est_ferme = 0';
        }
    }

    private function porteeSql(string $typeColumn, string $idColumn): string
    {
        return "CASE {$typeColumn}
            WHEN 'societe' THEN soc.soc_nom
            WHEN 'departement' THEN (SELECT d.dep_nom FROM sav_departements d WHERE d.dep_id = {$idColumn} LIMIT 1)
            WHEN 'service' THEN (SELECT s.srv_nom FROM sav_services s WHERE s.srv_id = {$idColumn} LIMIT 1)
            WHEN 'equipe' THEN (SELECT eq.equ_nom FROM sav_equipes eq WHERE eq.equ_id = {$idColumn} LIMIT 1)
            WHEN 'utilisateur' THEN (SELECT CONCAT(COALESCE(p.pui_prenom, ''), ' ', COALESCE(p.pui_nom, ''), ' — ', u.use_email) FROM sav_utilisateurs u LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.use_id AND p.pui_supprime_le IS NULL WHERE u.use_id = {$idColumn} LIMIT 1)
            ELSE {$typeColumn}
        END";
    }

    private function porteeType(string $type): string
    {
        $type = strtolower(trim($type));
        $allowed = ['societe', 'departement', 'service', 'equipe', 'utilisateur'];
        return in_array($type, $allowed, true) ? $type : 'societe';
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || (int)$value <= 0) {
            return null;
        }
        return (int) $value;
    }

    private function nullOrString(mixed $value, int $max): ?string
    {
        $value = trim((string)($value ?? ''));
        if ($value === '') {
            return null;
        }
        return mb_substr($value, 0, $max);
    }

    private function timeOrNull(mixed $value): ?string
    {
        $value = trim((string)($value ?? ''));
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }
        throw new \InvalidArgumentException('Les heures doivent être au format HH:MM.');
    }

    private function calendarKey(mixed $societeId, mixed $type, mixed $id): string
    {
        return (int)$societeId . '|' . (string)$type . '|' . (string)((int)($id ?? 0));
    }

    private function jours(): array
    {
        return [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
    }

    private function portees(): array
    {
        return [
            'societe' => 'Société entière',
            'departement' => 'Département',
            'service' => 'Service',
            'equipe' => 'Équipe',
            'utilisateur' => 'Utilisateur',
        ];
    }

    private function audit(int $userId, string $action, string $table, int $targetId, string $raison): void
    {
        try {
            $this->db->execute(
                "INSERT INTO sav_journaux_audit
                    (jau_utilisateur_id, jau_action, jau_table_cible, jau_id_cible, jau_raison, jau_metadata_json)
                 VALUES
                    (:user_id, :action, :table_cible, :id_cible, :raison, :metadata)",
                [
                    'user_id' => $userId ?: null,
                    'action' => $action,
                    'table_cible' => $table,
                    'id_cible' => $targetId,
                    'raison' => $raison,
                    'metadata' => json_encode(['module' => 'Horaires'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable) {
            // L'audit ne doit jamais bloquer la gestion des horaires.
        }
    }
}
