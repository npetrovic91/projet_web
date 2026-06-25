<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Menus\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Menus dynamiques alignés sur les tables SQL actuelles.
 *
 * Tables utilisées :
 * - sav_menus ;
 * - sav_elements_menus ;
 * - sav_modules ;
 * - sav_modules_societes ;
 * - sav_permissions ;
 * - sav_statuts ;
 * - sav_journaux_audit.
 */
class MenuModel extends BaseModel
{
    protected string $table = 'menus';
    protected string $colPrefix = 'men_';

    public function tableauDeBord(?int $societeId = null): array
    {
        return [
            'stats' => [
                'menus' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_menus WHERE men_supprime_le IS NULL'),
                'elements' => (int) $this->db->fetchColumn('SELECT COUNT(*) FROM sav_elements_menus WHERE eme_supprime_le IS NULL'),
                'modules_actifs_societe' => $societeId ? (int) $this->db->fetchColumn(
                    'SELECT COUNT(*) FROM sav_modules_societes WHERE mos_societe_id = :societe AND mos_supprime_le IS NULL AND mos_archive_le IS NULL AND (mos_termine_le IS NULL OR mos_termine_le >= NOW())',
                    ['societe' => $societeId]
                ) : 0,
                'permissions_liees' => (int) $this->db->fetchColumn('SELECT COUNT(DISTINCT eme_permission_requise_id) FROM sav_elements_menus WHERE eme_permission_requise_id IS NOT NULL AND eme_supprime_le IS NULL'),
            ],
            'menus' => $this->menus([], 50),
            'elements' => $this->elements([], 80),
            'navigation' => $this->navigation($societeId),
        ];
    }

    public function menus(array $filters = [], int $limit = 300): array
    {
        $where = ['m.men_supprime_le IS NULL'];
        $params = [];
        if (($filters['module_id'] ?? '') !== '') {
            $where[] = 'm.men_module_id = :module_id';
            $params['module_id'] = (int) $filters['module_id'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(m.men_code LIKE :q OR m.men_nom LIKE :q OR mo.mod_nom LIKE :q OR mo.mod_code LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        return $this->db->fetchAll(
            'SELECT m.*, mo.mod_code, mo.mod_nom, s.sta_code AS statut_code, s.sta_libelle AS statut_libelle,
                    COUNT(e.eme_id) AS nombre_elements
               FROM sav_menus m
          LEFT JOIN sav_modules mo ON mo.mod_id = m.men_module_id
          LEFT JOIN sav_statuts s ON s.sta_id = m.men_statut_id
          LEFT JOIN sav_elements_menus e ON e.eme_menu_id = m.men_id AND e.eme_supprime_le IS NULL
              WHERE ' . implode(' AND ', $where) . '
           GROUP BY m.men_id
           ORDER BY mo.mod_nom ASC, m.men_code ASC, m.men_nom ASC
              LIMIT ' . $this->limit($limit),
            $params
        );
    }

    public function trouverMenu(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM sav_menus WHERE men_id = :id AND men_supprime_le IS NULL LIMIT 1', ['id' => $id]);
    }

    public function enregistrerMenu(array $input, ?int $id = null): int
    {
        $data = [
            'code' => $this->normaliserCode((string) ($input['men_code'] ?? '')),
            'nom' => trim((string) ($input['men_nom'] ?? '')),
            'module_id' => ($input['men_module_id'] ?? '') !== '' ? (int) $input['men_module_id'] : null,
            'statut_id' => ($input['men_statut_id'] ?? '') !== '' ? (int) $input['men_statut_id'] : null,
        ];
        if ($data['code'] === '' || $data['nom'] === '') {
            throw new \InvalidArgumentException('Le code et le nom du menu sont obligatoires.');
        }
        if ($id) {
            $this->db->execute(
                'UPDATE sav_menus SET men_code = :code, men_nom = :nom, men_module_id = :module_id, men_statut_id = :statut_id, men_modifie_le = NOW() WHERE men_id = :id',
                $data + ['id' => $id]
            );
            return $id;
        }
        $this->db->execute(
            'INSERT INTO sav_menus (men_code, men_nom, men_module_id, men_statut_id, men_cree_le, men_modifie_le) VALUES (:code, :nom, :module_id, :statut_id, NOW(), NOW())',
            $data
        );
        return (int) $this->db->lastInsertId();
    }

    public function supprimerMenu(int $id): void
    {
        $this->db->execute('UPDATE sav_menus SET men_supprime_le = NOW() WHERE men_id = :id', ['id' => $id]);
        $this->db->execute('UPDATE sav_elements_menus SET eme_supprime_le = NOW() WHERE eme_menu_id = :id AND eme_supprime_le IS NULL', ['id' => $id]);
    }

    public function elements(array $filters = [], int $limit = 500): array
    {
        $where = ['e.eme_supprime_le IS NULL', 'm.men_supprime_le IS NULL'];
        $params = [];
        if (($filters['menu_id'] ?? '') !== '') {
            $where[] = 'e.eme_menu_id = :menu_id';
            $params['menu_id'] = (int) $filters['menu_id'];
        }
        if (($filters['module_id'] ?? '') !== '') {
            $where[] = 'e.eme_module_id = :module_id';
            $params['module_id'] = (int) $filters['module_id'];
        }
        if (($filters['q'] ?? '') !== '') {
            $where[] = '(e.eme_libelle LIKE :q OR e.eme_route LIKE :q OR e.eme_icone LIKE :q OR per.per_code LIKE :q)';
            $params['q'] = '%' . trim((string) $filters['q']) . '%';
        }
        return $this->db->fetchAll(
            'SELECT e.*, m.men_code, m.men_nom, p.eme_libelle AS parent_libelle,
                    mo.mod_code, mo.mod_nom, per.per_code AS permission_code,
                    s.sta_code AS statut_code, s.sta_libelle AS statut_libelle
               FROM sav_elements_menus e
               JOIN sav_menus m ON m.men_id = e.eme_menu_id
          LEFT JOIN sav_elements_menus p ON p.eme_id = e.eme_parent_id
          LEFT JOIN sav_modules mo ON mo.mod_id = e.eme_module_id
          LEFT JOIN sav_permissions per ON per.per_id = e.eme_permission_requise_id
          LEFT JOIN sav_statuts s ON s.sta_id = e.eme_statut_id
              WHERE ' . implode(' AND ', $where) . '
           ORDER BY m.men_code ASC, COALESCE(e.eme_parent_id, e.eme_id) ASC, e.eme_position ASC, e.eme_libelle ASC
              LIMIT ' . $this->limit($limit),
            $params
        );
    }

    public function trouverElement(int $id): ?array
    {
        return $this->db->fetch('SELECT * FROM sav_elements_menus WHERE eme_id = :id AND eme_supprime_le IS NULL LIMIT 1', ['id' => $id]);
    }

    public function enregistrerElement(array $input, ?int $id = null): int
    {
        $data = [
            'menu_id' => (int) ($input['eme_menu_id'] ?? 0),
            'module_id' => ($input['eme_module_id'] ?? '') !== '' ? (int) $input['eme_module_id'] : null,
            'parent_id' => ($input['eme_parent_id'] ?? '') !== '' ? (int) $input['eme_parent_id'] : null,
            'libelle' => trim((string) ($input['eme_libelle'] ?? '')),
            'route' => trim((string) ($input['eme_route'] ?? '')) ?: null,
            'icone' => trim((string) ($input['eme_icone'] ?? '')) ?: null,
            'position' => max(0, (int) ($input['eme_position'] ?? 100)),
            'permission_id' => ($input['eme_permission_requise_id'] ?? '') !== '' ? (int) $input['eme_permission_requise_id'] : null,
            'statut_id' => ($input['eme_statut_id'] ?? '') !== '' ? (int) $input['eme_statut_id'] : null,
        ];
        if ($data['menu_id'] <= 0 || $data['libelle'] === '') {
            throw new \InvalidArgumentException('Le menu et le libellé sont obligatoires.');
        }
        if ($data['parent_id'] !== null && $id !== null && $data['parent_id'] === $id) {
            throw new \InvalidArgumentException('Un élément de menu ne peut pas être son propre parent.');
        }
        if ($id) {
            $this->db->execute(
                'UPDATE sav_elements_menus
                    SET eme_menu_id = :menu_id, eme_module_id = :module_id, eme_parent_id = :parent_id,
                        eme_libelle = :libelle, eme_route = :route, eme_icone = :icone, eme_position = :position,
                        eme_permission_requise_id = :permission_id, eme_statut_id = :statut_id, eme_modifie_le = NOW()
                  WHERE eme_id = :id',
                $data + ['id' => $id]
            );
            return $id;
        }
        $this->db->execute(
            'INSERT INTO sav_elements_menus
                (eme_menu_id, eme_module_id, eme_parent_id, eme_libelle, eme_route, eme_icone, eme_position, eme_permission_requise_id, eme_statut_id, eme_cree_le, eme_modifie_le)
             VALUES
                (:menu_id, :module_id, :parent_id, :libelle, :route, :icone, :position, :permission_id, :statut_id, NOW(), NOW())',
            $data
        );
        return (int) $this->db->lastInsertId();
    }

    public function supprimerElement(int $id): void
    {
        $this->db->execute('UPDATE sav_elements_menus SET eme_parent_id = NULL WHERE eme_parent_id = :id AND eme_supprime_le IS NULL', ['id' => $id]);
        $this->db->execute('UPDATE sav_elements_menus SET eme_supprime_le = NOW() WHERE eme_id = :id', ['id' => $id]);
    }

    public function navigation(?int $societeId = null): array
    {
        $params = [];
        $moduleFilter = '';
        if ($societeId) {
            $moduleFilter = ' AND (e.eme_module_id IS NULL OR e.eme_module_id IN (
                SELECT mos_module_id
                  FROM sav_modules_societes
                 WHERE mos_societe_id = :societe
                   AND mos_supprime_le IS NULL
                   AND mos_archive_le IS NULL
                   AND (mos_termine_le IS NULL OR mos_termine_le >= NOW())
            ))';
            $params['societe'] = $societeId;
        }

        $rows = $this->db->fetchAll(
            'SELECT m.men_id, m.men_code, m.men_nom,
                    e.eme_id, e.eme_parent_id, e.eme_libelle, e.eme_route, e.eme_icone, e.eme_position,
                    mo.mod_code, mo.mod_nom, per.per_code AS permission_code
               FROM sav_menus m
               JOIN sav_elements_menus e ON e.eme_menu_id = m.men_id AND e.eme_supprime_le IS NULL
          LEFT JOIN sav_modules mo ON mo.mod_id = e.eme_module_id
          LEFT JOIN sav_permissions per ON per.per_id = e.eme_permission_requise_id
              WHERE m.men_supprime_le IS NULL' . $moduleFilter . '
           ORDER BY m.men_code ASC, e.eme_position ASC, e.eme_libelle ASC',
            $params
        );
        return $this->construireArbre($rows);
    }

    public function modules(): array
    {
        return $this->db->fetchAll('SELECT mod_id, mod_code, mod_nom FROM sav_modules WHERE mod_supprime_le IS NULL ORDER BY mod_nom ASC');
    }

    public function permissions(): array
    {
        return $this->db->fetchAll('SELECT per_id, per_code, per_description FROM sav_permissions WHERE per_supprime_le IS NULL ORDER BY per_code ASC');
    }

    public function statuts(): array
    {
        return $this->db->fetchAll('SELECT sta_id, sta_code, sta_libelle FROM sav_statuts WHERE sta_supprime_le IS NULL ORDER BY sta_domaine ASC, sta_libelle ASC');
    }

    public function parentsPossibles(?int $menuId = null, ?int $excludeId = null): array
    {
        $where = ['eme_supprime_le IS NULL'];
        $params = [];
        if ($menuId) {
            $where[] = 'eme_menu_id = :menu_id';
            $params['menu_id'] = $menuId;
        }
        if ($excludeId) {
            $where[] = 'eme_id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        return $this->db->fetchAll('SELECT eme_id, eme_libelle, eme_menu_id FROM sav_elements_menus WHERE ' . implode(' AND ', $where) . ' ORDER BY eme_position ASC, eme_libelle ASC', $params);
    }

    public function audit(string $action, string $table, int $cibleId, ?int $userId, ?string $ip, array $meta = []): void
    {
        $this->db->execute(
            'INSERT INTO sav_journaux_audit
                (jau_utilisateur_id, jau_action, jau_table_cible, jau_id_cible, jau_adresse_ip, jau_metadata_json, jau_cree_le)
             VALUES
                (:user_id, :action, :table_cible, :id_cible, INET6_ATON(:ip), :meta, NOW())',
            [
                'user_id' => $userId,
                'action' => $action,
                'table_cible' => $table,
                'id_cible' => $cibleId,
                'ip' => $ip ?: '127.0.0.1',
                'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );
    }

    private function construireArbre(array $rows): array
    {
        $menus = [];
        $items = [];
        foreach ($rows as $row) {
            $menuId = (int) $row['men_id'];
            $menus[$menuId] ??= [
                'id' => $menuId,
                'code' => $row['men_code'],
                'nom' => $row['men_nom'],
                'items' => [],
            ];
            $itemId = (int) $row['eme_id'];
            $items[$itemId] = [
                'id' => $itemId,
                'parent_id' => $row['eme_parent_id'] !== null ? (int) $row['eme_parent_id'] : null,
                'menu_id' => $menuId,
                'libelle' => $row['eme_libelle'],
                'route' => $row['eme_route'],
                'icone' => $row['eme_icone'],
                'position' => (int) $row['eme_position'],
                'module_code' => $row['mod_code'],
                'module_nom' => $row['mod_nom'],
                'permission_code' => $row['permission_code'],
                'enfants' => [],
            ];
        }
        foreach ($items as $id => &$item) {
            $permission = (string) ($item['permission_code'] ?? '');
            if ($permission !== '' && function_exists('has_permission') && !has_permission($permission)) {
                unset($items[$id]);
                continue;
            }
        }
        unset($item);
        foreach ($items as $id => $item) {
            if ($item['parent_id'] && isset($items[$item['parent_id']])) {
                $items[$item['parent_id']]['enfants'][] = $item;
            } else {
                $menus[$item['menu_id']]['items'][] = $item;
            }
        }
        return array_values($menus);
    }

    private function normaliserCode(string $code): string
    {
        $code = strtolower(trim($code));
        $code = preg_replace('/[^a-z0-9_.-]+/', '_', $code) ?? '';
        return trim($code, '_.-');
    }

    private function limit(int $limit): int
    {
        return max(1, min(1000, $limit));
    }
}
