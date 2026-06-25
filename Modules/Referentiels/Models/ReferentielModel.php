<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Referentiels\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Référentiels transversaux alignés sur les tables SQL actuelles.
 *
 * Tables couvertes :
 * - sav_pays ;
 * - sav_devises ;
 * - sav_fuseaux_horaires ;
 * - sav_taux_tva ;
 * - sav_statuts ;
 * - sav_transitions_statuts.
 */
class ReferentielModel extends BaseModel
{
    protected string $table = 'sav_statuts';
    protected string $colPrefix = 'sta_';

    public function tableauDeBord(): array
    {
        return [
            'stats' => [
                'pays' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_pays WHERE pay_supprime_le IS NULL'),
                'devises' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_devises WHERE dev_supprime_le IS NULL'),
                'fuseaux' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_fuseaux_horaires WHERE fuh_supprime_le IS NULL'),
                'tva' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_taux_tva WHERE tva_supprime_le IS NULL'),
                'statuts' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_statuts WHERE sta_supprime_le IS NULL'),
                'transitions' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_transitions_statuts WHERE tst_supprime_le IS NULL'),
            ],
            'domaines_statuts' => $this->domainesStatuts(),
            'pays' => $this->pays(['actifs' => true], 20),
            'devises' => $this->devises(['actives' => true], 20),
            'fuseaux' => $this->fuseaux(['actifs' => true], 20),
            'tva' => $this->tauxTva([], 20),
        ];
    }

    public function pays(array $filters = [], int $limit = 300): array
    {
        $where = ['pay_supprime_le IS NULL'];
        $params = [];
        if (($filters['actifs'] ?? false) === true) {
            $where[] = 'pay_est_actif = 1';
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(pay_code_iso2 LIKE :q OR pay_code_iso3 LIKE :q OR pay_nom LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        return $this->db->fetchAll(
            'SELECT * FROM sav_pays WHERE ' . implode(' AND ', $where) . ' ORDER BY pay_nom ASC LIMIT ' . $this->limit($limit),
            $params
        );
    }

    public function trouverPays(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM sav_pays WHERE pay_id = :id AND pay_supprime_le IS NULL LIMIT 1', ['id' => $id]);
    }

    public function enregistrerPays(array $input, ?int $id = null): int
    {
        $data = [
            'iso2' => strtoupper(substr(trim((string) ($input['pay_code_iso2'] ?? '')), 0, 2)),
            'iso3' => strtoupper(substr(trim((string) ($input['pay_code_iso3'] ?? '')), 0, 3)),
            'nom' => trim((string) ($input['pay_nom'] ?? '')),
            'actif' => !empty($input['pay_est_actif']) ? 1 : 0,
        ];
        if ($data['iso2'] === '' || $data['iso3'] === '' || $data['nom'] === '') {
            throw new \InvalidArgumentException('Code ISO2, code ISO3 et nom du pays sont obligatoires.');
        }
        if ($id) {
            $this->db->execute(
                'UPDATE sav_pays SET pay_code_iso2 = :iso2, pay_code_iso3 = :iso3, pay_nom = :nom, pay_est_actif = :actif, pay_modifie_le = NOW() WHERE pay_id = :id',
                $data + ['id' => $id]
            );
            return $id;
        }
        $this->db->execute(
            'INSERT INTO sav_pays (pay_code_iso2, pay_code_iso3, pay_nom, pay_est_actif, pay_cree_le, pay_modifie_le) VALUES (:iso2, :iso3, :nom, :actif, NOW(), NOW())',
            $data
        );
        return (int) $this->db->lastInsertId();
    }

    public function devises(array $filters = [], int $limit = 300): array
    {
        $where = ['dev_supprime_le IS NULL'];
        $params = [];
        if (($filters['actives'] ?? false) === true) {
            $where[] = 'dev_est_active = 1';
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(dev_code_iso LIKE :q OR dev_nom LIKE :q OR dev_symbole LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        return $this->db->fetchAll('SELECT * FROM sav_devises WHERE ' . implode(' AND ', $where) . ' ORDER BY dev_code_iso ASC LIMIT ' . $this->limit($limit), $params);
    }

    public function trouverDevise(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM sav_devises WHERE dev_id = :id AND dev_supprime_le IS NULL LIMIT 1', ['id' => $id]);
    }

    public function enregistrerDevise(array $input, ?int $id = null): int
    {
        $data = [
            'code' => strtoupper(substr(trim((string) ($input['dev_code_iso'] ?? '')), 0, 3)),
            'nom' => trim((string) ($input['dev_nom'] ?? '')),
            'symbole' => trim((string) ($input['dev_symbole'] ?? '')) ?: null,
            'decimales' => max(0, min(9, (int) ($input['dev_nombre_decimales'] ?? 2))),
            'active' => !empty($input['dev_est_active']) ? 1 : 0,
        ];
        if ($data['code'] === '' || $data['nom'] === '') {
            throw new \InvalidArgumentException('Code ISO et nom de la devise sont obligatoires.');
        }
        if ($id) {
            $this->db->execute(
                'UPDATE sav_devises SET dev_code_iso = :code, dev_nom = :nom, dev_symbole = :symbole, dev_nombre_decimales = :decimales, dev_est_active = :active, dev_modifie_le = NOW() WHERE dev_id = :id',
                $data + ['id' => $id]
            );
            return $id;
        }
        $this->db->execute(
            'INSERT INTO sav_devises (dev_code_iso, dev_nom, dev_symbole, dev_nombre_decimales, dev_est_active, dev_cree_le, dev_modifie_le) VALUES (:code, :nom, :symbole, :decimales, :active, NOW(), NOW())',
            $data
        );
        return (int) $this->db->lastInsertId();
    }

    public function fuseaux(array $filters = [], int $limit = 300): array
    {
        $where = ['fuh_supprime_le IS NULL'];
        $params = [];
        if (($filters['actifs'] ?? false) === true) {
            $where[] = 'fuh_est_actif = 1';
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(fuh_nom_iana LIKE :q OR fuh_libelle LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        return $this->db->fetchAll('SELECT * FROM sav_fuseaux_horaires WHERE ' . implode(' AND ', $where) . ' ORDER BY fuh_nom_iana ASC LIMIT ' . $this->limit($limit), $params);
    }

    public function trouverFuseau(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM sav_fuseaux_horaires WHERE fuh_id = :id AND fuh_supprime_le IS NULL LIMIT 1', ['id' => $id]);
    }

    public function enregistrerFuseau(array $input, ?int $id = null): int
    {
        $data = [
            'iana' => trim((string) ($input['fuh_nom_iana'] ?? '')),
            'libelle' => trim((string) ($input['fuh_libelle'] ?? '')) ?: null,
            'actif' => !empty($input['fuh_est_actif']) ? 1 : 0,
        ];
        if ($data['iana'] === '') {
            throw new \InvalidArgumentException('Le nom IANA est obligatoire.');
        }
        if ($id) {
            $this->db->execute('UPDATE sav_fuseaux_horaires SET fuh_nom_iana = :iana, fuh_libelle = :libelle, fuh_est_actif = :actif, fuh_modifie_le = NOW() WHERE fuh_id = :id', $data + ['id' => $id]);
            return $id;
        }
        $this->db->execute('INSERT INTO sav_fuseaux_horaires (fuh_nom_iana, fuh_libelle, fuh_est_actif, fuh_cree_le, fuh_modifie_le) VALUES (:iana, :libelle, :actif, NOW(), NOW())', $data);
        return (int) $this->db->lastInsertId();
    }

    public function tauxTva(array $filters = [], int $limit = 300): array
    {
        $where = ['t.tva_supprime_le IS NULL'];
        $params = [];
        if (($filters['pays_id'] ?? '') !== '') {
            $where[] = 't.tva_pays_id = :pays';
            $params['pays'] = (int) $filters['pays_id'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(t.tva_code LIKE :q OR t.tva_nom LIKE :q OR p.pay_nom LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        return $this->db->fetchAll(
            'SELECT t.*, p.pay_nom, p.pay_code_iso2, s.sta_libelle AS statut_libelle, s.sta_code AS statut_code
               FROM sav_taux_tva t
               JOIN sav_pays p ON p.pay_id = t.tva_pays_id
          LEFT JOIN sav_statuts s ON s.sta_id = t.tva_statut_id
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY p.pay_nom ASC, t.tva_debute_le DESC, t.tva_code ASC
              LIMIT ' . $this->limit($limit),
            $params
        );
    }

    public function trouverTva(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT t.*, p.pay_nom FROM sav_taux_tva t JOIN sav_pays p ON p.pay_id = t.tva_pays_id WHERE t.tva_id = :id AND t.tva_supprime_le IS NULL LIMIT 1',
            ['id' => $id]
        );
    }

    public function enregistrerTva(array $input, ?int $id = null): int
    {
        $data = [
            'pays_id' => (int) ($input['tva_pays_id'] ?? 0),
            'code' => trim((string) ($input['tva_code'] ?? '')),
            'nom' => trim((string) ($input['tva_nom'] ?? '')),
            'taux' => (string) number_format((float) str_replace(',', '.', (string) ($input['tva_taux'] ?? 0)), 2, '.', ''),
            'debute' => trim((string) ($input['tva_debute_le'] ?? date('Y-m-d'))),
            'termine' => trim((string) ($input['tva_termine_le'] ?? '')) ?: null,
            'statut_id' => ($input['tva_statut_id'] ?? '') !== '' ? (int) $input['tva_statut_id'] : null,
        ];
        if ($data['pays_id'] <= 0 || $data['code'] === '' || $data['nom'] === '') {
            throw new \InvalidArgumentException('Pays, code et nom du taux de TVA sont obligatoires.');
        }
        if ($id) {
            $this->db->execute(
                'UPDATE sav_taux_tva SET tva_pays_id = :pays_id, tva_code = :code, tva_nom = :nom, tva_taux = :taux, tva_debute_le = :debute, tva_termine_le = :termine, tva_statut_id = :statut_id, tva_modifie_le = NOW() WHERE tva_id = :id',
                $data + ['id' => $id]
            );
            return $id;
        }
        $this->db->execute(
            'INSERT INTO sav_taux_tva (tva_pays_id, tva_code, tva_nom, tva_taux, tva_debute_le, tva_termine_le, tva_statut_id, tva_cree_le, tva_modifie_le) VALUES (:pays_id, :code, :nom, :taux, :debute, :termine, :statut_id, NOW(), NOW())',
            $data
        );
        return (int) $this->db->lastInsertId();
    }

    public function statuts(array $filters = [], int $limit = 500): array
    {
        $where = ['s.sta_supprime_le IS NULL'];
        $params = [];
        if (($filters['domaine'] ?? '') !== '') {
            $where[] = 's.sta_domaine = :domaine';
            $params['domaine'] = trim((string) $filters['domaine']);
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(s.sta_domaine LIKE :q OR s.sta_code LIKE :q OR s.sta_libelle LIKE :q OR s.sta_entite_table LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        return $this->db->fetchAll(
            'SELECT s.*, COUNT(ts.tst_id) AS transitions_sortantes, COUNT(tc.tst_id) AS transitions_entrantes
               FROM sav_statuts s
          LEFT JOIN sav_transitions_statuts ts ON ts.tst_statut_source_id = s.sta_id AND ts.tst_supprime_le IS NULL
          LEFT JOIN sav_transitions_statuts tc ON tc.tst_statut_cible_id = s.sta_id AND tc.tst_supprime_le IS NULL
              WHERE ' . implode(' AND ', $where) . '
           GROUP BY s.sta_id
           ORDER BY s.sta_domaine ASC, s.sta_ordre ASC, s.sta_libelle ASC
              LIMIT ' . $this->limit($limit),
            $params
        );
    }

    public function trouverStatut(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM sav_statuts WHERE sta_id = :id AND sta_supprime_le IS NULL LIMIT 1', ['id' => $id]);
    }

    public function enregistrerStatut(array $input, ?int $id = null): int
    {
        $data = [
            'domaine' => trim((string) ($input['sta_domaine'] ?? '')),
            'code' => trim((string) ($input['sta_code'] ?? '')),
            'libelle' => trim((string) ($input['sta_libelle'] ?? '')),
            'description' => trim((string) ($input['sta_description'] ?? '')) ?: null,
            'couleur' => trim((string) ($input['sta_couleur'] ?? '')) ?: null,
            'icone' => trim((string) ($input['sta_icone'] ?? '')) ?: null,
            'entite' => trim((string) ($input['sta_entite_table'] ?? '')) ?: null,
            'initial' => !empty($input['sta_est_initial']) ? 1 : 0,
            'final' => !empty($input['sta_est_final']) ? 1 : 0,
            'systeme' => !empty($input['sta_est_systeme']) ? 1 : 0,
            'actif' => !empty($input['sta_est_actif']) ? 1 : 0,
            'ordre' => max(0, (int) ($input['sta_ordre'] ?? 100)),
        ];
        if ($data['domaine'] === '' || $data['code'] === '' || $data['libelle'] === '') {
            throw new \InvalidArgumentException('Domaine, code et libellé du statut sont obligatoires.');
        }
        if ($id) {
            $this->db->execute(
                'UPDATE sav_statuts SET sta_domaine = :domaine, sta_code = :code, sta_libelle = :libelle, sta_description = :description, sta_couleur = :couleur, sta_icone = :icone, sta_entite_table = :entite, sta_est_initial = :initial, sta_est_final = :final, sta_est_systeme = :systeme, sta_est_actif = :actif, sta_ordre = :ordre, sta_modifie_le = NOW() WHERE sta_id = :id',
                $data + ['id' => $id]
            );
            return $id;
        }
        $this->db->execute(
            'INSERT INTO sav_statuts (sta_domaine, sta_code, sta_libelle, sta_description, sta_couleur, sta_icone, sta_entite_table, sta_est_initial, sta_est_final, sta_est_systeme, sta_est_actif, sta_ordre, sta_cree_le, sta_modifie_le) VALUES (:domaine, :code, :libelle, :description, :couleur, :icone, :entite, :initial, :final, :systeme, :actif, :ordre, NOW(), NOW())',
            $data
        );
        return (int) $this->db->lastInsertId();
    }

    public function transitions(array $filters = [], int $limit = 500): array
    {
        $where = ['t.tst_supprime_le IS NULL'];
        $params = [];
        if (($filters['domaine'] ?? '') !== '') {
            $where[] = 't.tst_domaine = :domaine';
            $params['domaine'] = trim((string) $filters['domaine']);
        }
        return $this->db->fetchAll(
            'SELECT t.*, ss.sta_libelle AS source_libelle, ss.sta_code AS source_code, sc.sta_libelle AS cible_libelle, sc.sta_code AS cible_code
               FROM sav_transitions_statuts t
               JOIN sav_statuts ss ON ss.sta_id = t.tst_statut_source_id
               JOIN sav_statuts sc ON sc.sta_id = t.tst_statut_cible_id
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY t.tst_domaine ASC, ss.sta_ordre ASC, sc.sta_ordre ASC
              LIMIT ' . $this->limit($limit),
            $params
        );
    }

    public function trouverTransition(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM sav_transitions_statuts WHERE tst_id = :id AND tst_supprime_le IS NULL LIMIT 1', ['id' => $id]);
    }

    public function enregistrerTransition(array $input, ?int $id = null): int
    {
        $data = [
            'domaine' => trim((string) ($input['tst_domaine'] ?? '')),
            'source_id' => (int) ($input['tst_statut_source_id'] ?? 0),
            'cible_id' => (int) ($input['tst_statut_cible_id'] ?? 0),
            'permission' => trim((string) ($input['tst_permission_code'] ?? '')) ?: null,
            'motif' => !empty($input['tst_motif_obligatoire']) ? 1 : 0,
            'validation' => !empty($input['tst_validation_requise']) ? 1 : 0,
            'notification' => !empty($input['tst_notification_requise']) ? 1 : 0,
            'active' => !empty($input['tst_est_active']) ? 1 : 0,
        ];
        if ($data['domaine'] === '' || $data['source_id'] <= 0 || $data['cible_id'] <= 0 || $data['source_id'] === $data['cible_id']) {
            throw new \InvalidArgumentException('Domaine, statut source et statut cible distincts sont obligatoires.');
        }
        if ($id) {
            $this->db->execute(
                'UPDATE sav_transitions_statuts SET tst_domaine = :domaine, tst_statut_source_id = :source_id, tst_statut_cible_id = :cible_id, tst_permission_code = :permission, tst_motif_obligatoire = :motif, tst_validation_requise = :validation, tst_notification_requise = :notification, tst_est_active = :active, tst_modifie_le = NOW() WHERE tst_id = :id',
                $data + ['id' => $id]
            );
            return $id;
        }
        $this->db->execute(
            'INSERT INTO sav_transitions_statuts (tst_domaine, tst_statut_source_id, tst_statut_cible_id, tst_permission_code, tst_motif_obligatoire, tst_validation_requise, tst_notification_requise, tst_est_active, tst_cree_le, tst_modifie_le) VALUES (:domaine, :source_id, :cible_id, :permission, :motif, :validation, :notification, :active, NOW(), NOW())',
            $data
        );
        return (int) $this->db->lastInsertId();
    }

    public function domainesStatuts(): array
    {
        return $this->db->fetchAll(
            'SELECT sta_domaine AS domaine, COUNT(*) AS total
               FROM sav_statuts
              WHERE sta_supprime_le IS NULL
           GROUP BY sta_domaine
           ORDER BY sta_domaine ASC'
        );
    }

    public function statutsPourSelect(?string $domaine = null): array
    {
        if ($domaine) {
            return $this->db->fetchAll('SELECT sta_id, sta_domaine, sta_code, sta_libelle FROM sav_statuts WHERE sta_supprime_le IS NULL AND sta_domaine = :domaine ORDER BY sta_ordre ASC, sta_libelle ASC', ['domaine' => $domaine]);
        }
        return $this->db->fetchAll('SELECT sta_id, sta_domaine, sta_code, sta_libelle FROM sav_statuts WHERE sta_supprime_le IS NULL ORDER BY sta_domaine ASC, sta_ordre ASC, sta_libelle ASC');
    }

    public function supprimer(string $type, int $id, int $userId = 0, string $ip = ''): bool
    {
        $map = [
            'pays' => ['sav_pays', 'pay_id', 'pay_supprime_le'],
            'devises' => ['sav_devises', 'dev_id', 'dev_supprime_le'],
            'fuseaux' => ['sav_fuseaux_horaires', 'fuh_id', 'fuh_supprime_le'],
            'tva' => ['sav_taux_tva', 'tva_id', 'tva_supprime_le'],
            'statuts' => ['sav_statuts', 'sta_id', 'sta_supprime_le'],
            'transitions' => ['sav_transitions_statuts', 'tst_id', 'tst_supprime_le'],
        ];
        if (!isset($map[$type])) {
            throw new \InvalidArgumentException('Type de référentiel inconnu.');
        }
        [$table, $pk, $deleted] = $map[$type];
        $ok = $this->db->execute("UPDATE {$table} SET {$deleted} = NOW() WHERE {$pk} = :id", ['id' => $id]);
        $this->auditer('referentiel.supprimer', $table, $id, $userId, $ip, ['type' => $type]);
        return $ok;
    }

    public function auditer(string $action, string $table, int $id, int $userId = 0, string $ip = '', array $meta = []): void
    {
        try {
            $this->db->execute(
                'INSERT INTO sav_journaux_audit (jau_utilisateur_id, jau_action, jau_table_cible, jau_id_cible, jau_adresse_ip, jau_metadata_json, jau_cree_le) VALUES (:uid, :action, :table, :id, INET6_ATON(:ip), :meta, NOW())',
                [
                    'uid' => $userId > 0 ? $userId : null,
                    'action' => $action,
                    'table' => $table,
                    'id' => $id,
                    'ip' => $ip ?: '127.0.0.1',
                    'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );
        } catch (\Throwable) {
            // L'audit ne doit jamais bloquer la gestion d'un référentiel.
        }
    }

    public function export(): array
    {
        return [
            'pays' => $this->pays([], 10000),
            'devises' => $this->devises([], 10000),
            'fuseaux_horaires' => $this->fuseaux([], 10000),
            'taux_tva' => $this->tauxTva([], 10000),
            'statuts' => $this->statuts([], 10000),
            'transitions_statuts' => $this->transitions([], 10000),
        ];
    }

    private function limit(int $limit): int
    {
        return max(1, min(10000, $limit));
    }
}
