<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Models;

use Nenad\Autosav\Core\Model\BaseModel;
use PDO;

class UserModel extends BaseModel
{
    protected string $table = 'sav_users';

    public function findById(int $id): ?array
    {
        $sql = "SELECT u.*,
                       r.rol_code AS primary_role_code,
                       r.rol_label AS primary_role_label,
                       r.rol_level AS primary_role_level,
                       c.com_name AS active_company_name,
                       b.brd_name AS active_brand_name
                FROM sav_users u
                LEFT JOIN sav_user_roles ur ON ur.url_user_id = u.use_id
                    AND ur.url_is_primary = 1
                    AND ur.url_revoked_at IS NULL
                LEFT JOIN sav_roles r ON r.rol_id = ur.url_role_id
                LEFT JOIN sav_companies c ON c.com_id = u.use_active_company_id
                LEFT JOIN sav_brands b ON b.brd_id = u.use_active_brand_id
                WHERE u.use_id = :id
                  AND u.use_deleted_at IS NULL";

        return $this->db->fetch($sql, ['id' => $id]);
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT u.*, r.rol_code AS primary_role_code, r.rol_label AS primary_role_label
                FROM sav_users u
                LEFT JOIN sav_user_roles ur ON ur.url_user_id = u.use_id
                    AND ur.url_is_primary = 1
                    AND ur.url_revoked_at IS NULL
                LEFT JOIN sav_roles r ON r.rol_id = ur.url_role_id
                WHERE u.use_email = :email
                  AND u.use_deleted_at IS NULL";

        return $this->db->fetch($sql, ['email' => strtolower(trim($email))]);
    }

    public function existsByEmail(string $email, ?int $excludeUserId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM sav_users
                WHERE use_email = :email
                  AND use_deleted_at IS NULL";
        $params = ['email' => strtolower(trim($email))];

        if ($excludeUserId !== null) {
            $sql .= " AND use_id <> :exclude_id";
            $params['exclude_id'] = $excludeUserId;
        }

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO sav_users (
                    use_uuid, use_email, use_password_hash, use_civility,
                    use_lastname, use_firstname, use_phone, use_mobile,
                    use_employee_number, use_department, use_job_title,
                    use_locale, use_timezone, use_is_active, use_is_locked,
                    use_email_verification_token, use_email_verification_sent_at,
                    use_email_verification_attempts, use_must_change_password,
                    use_created_by, use_created_at, use_updated_at
                ) VALUES (
                    :uuid, :email, :password_hash, :civility,
                    :lastname, :firstname, :phone, :mobile,
                    :employee_number, :department, :job_title,
                    :locale, :timezone, 1, 0,
                    :verification_token, NOW(), 0, :must_change_password,
                    :created_by, NOW(), NOW()
                )";

        $this->db->execute($sql, [
            'uuid' => $data['uuid'],
            'email' => strtolower(trim((string) $data['email'])),
            'password_hash' => $data['password_hash'],
            'civility' => $data['civility'] ?? null,
            'lastname' => $data['lastname'],
            'firstname' => $data['firstname'],
            'phone' => $data['phone'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'employee_number' => $data['employee_number'] ?? null,
            'department' => $data['department'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'locale' => $data['locale'] ?? 'fr',
            'timezone' => $data['timezone'] ?? 'Europe/Paris',
            'verification_token' => $data['verification_token'] ?? null,
            'must_change_password' => (int) ($data['must_change_password'] ?? 1),
            'created_by' => $data['created_by'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateUser(int $id, array $data, int $updatedBy): bool
    {
        $allowed = [
            'use_email', 'use_civility', 'use_lastname', 'use_firstname',
            'use_phone', 'use_mobile', 'use_employee_number', 'use_department',
            'use_job_title', 'use_locale', 'use_timezone', 'use_must_change_password',
        ];

        $sets = [];
        $params = ['id' => $id, 'updated_by' => $updatedBy];

        foreach ($data as $column => $value) {
            if (!in_array($column, $allowed, true)) {
                continue;
            }
            $sets[] = "{$column} = :{$column}";
            $params[$column] = $value;
        }

        if ($sets === []) {
            return false;
        }

        $sets[] = 'use_updated_by = :updated_by';
        $sets[] = 'use_updated_at = NOW()';

        $sql = "UPDATE sav_users SET " . implode(', ', $sets) . " WHERE use_id = :id";
        return $this->db->execute($sql, $params);
    }

    public function updatePassword(int $id, string $hash): bool
    {
        return $this->db->execute(
            "UPDATE sav_users
             SET use_password_hash = :hash,
                 use_password_changed_at = NOW(),
                 use_must_change_password = 0,
                 use_updated_at = NOW()
             WHERE use_id = :id",
            ['hash' => $hash, 'id' => $id]
        );
    }

    public function setActiveState(int $id, bool $active, int $updatedBy): bool
    {
        return $this->db->execute(
            "UPDATE sav_users
             SET use_is_active = :active,
                 use_updated_by = :updated_by,
                 use_updated_at = NOW()
             WHERE use_id = :id",
            ['active' => $active, 'updated_by' => $updatedBy, 'id' => $id]
        );
    }

    public function updateActiveContext(int $id, ?int $companyId, ?int $brandId): bool
    {
        return $this->db->execute(
            "UPDATE sav_users
             SET use_active_company_id = :company_id,
                 use_active_brand_id = :brand_id,
                 use_updated_at = NOW()
             WHERE use_id = :id",
            ['company_id' => $companyId, 'brand_id' => $brandId, 'id' => $id]
        );
    }

    public function getFiltered(array $filters, int $page, int $perPage): array
    {
        $conditions = ['u.use_deleted_at IS NULL'];
        $params = [];

        if (($filters['search'] ?? '') !== '') {
            $conditions[] = "(u.use_lastname LIKE :search_name OR u.use_firstname LIKE :search_first OR u.use_email LIKE :search_email)";
            $like = '%' . trim((string) $filters['search']) . '%';
            $params['search_name'] = $like;
            $params['search_first'] = $like;
            $params['search_email'] = $like;
        }

        if (($filters['role_code'] ?? '') !== '') {
            $conditions[] = 'r.rol_code = :role_code';
            $params['role_code'] = $filters['role_code'];
        }

        if (($filters['company_id'] ?? '') !== '') {
            $conditions[] = "EXISTS (
                SELECT 1 FROM sav_user_companies ucx
                WHERE ucx.ucm_user_id = u.use_id
                  AND ucx.ucm_company_id = :company_id
                  AND ucx.ucm_is_active = 1
            )";
            $params['company_id'] = (int) $filters['company_id'];
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '') {
            $conditions[] = 'u.use_is_active = :is_active';
            $params['is_active'] = (int) $filters['is_active'];
        }

        if (!empty($filters['scope_company_ids']) && is_array($filters['scope_company_ids'])) {
            $scopeIds = array_values(array_unique(array_map('intval', $filters['scope_company_ids'])));
            if ($scopeIds !== []) {
                $placeholders = [];
                foreach ($scopeIds as $index => $companyId) {
                    $key = 'scope_company_' . $index;
                    $placeholders[] = ':' . $key;
                    $params[$key] = $companyId;
                }
                $conditions[] = "EXISTS (
                    SELECT 1 FROM sav_user_companies ucs
                    WHERE ucs.ucm_user_id = u.use_id
                      AND ucs.ucm_company_id IN (" . implode(',', $placeholders) . ")
                      AND ucs.ucm_is_active = 1
                )";
            } else {
                $conditions[] = '1 = 0';
            }
        }

        $where = implode(' AND ', $conditions);
        $total = (int) $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT u.use_id)
             FROM sav_users u
             LEFT JOIN sav_user_roles ur ON ur.url_user_id = u.use_id
                AND ur.url_is_primary = 1
                AND ur.url_revoked_at IS NULL
             LEFT JOIN sav_roles r ON r.rol_id = ur.url_role_id
             WHERE {$where}",
            $params
        );

        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT DISTINCT
                       u.use_id, u.use_uuid, u.use_civility, u.use_lastname,
                       u.use_firstname, u.use_email, u.use_phone, u.use_mobile,
                       u.use_photo_url, u.use_employee_number, u.use_department,
                       u.use_job_title, u.use_is_active, u.use_is_locked,
                       u.use_email_verified_at, u.use_last_login_at, u.use_created_at,
                       r.rol_code AS primary_role_code,
                       r.rol_label AS primary_role_label,
                       c.com_name AS active_company_name
                FROM sav_users u
                LEFT JOIN sav_user_roles ur ON ur.url_user_id = u.use_id
                    AND ur.url_is_primary = 1
                    AND ur.url_revoked_at IS NULL
                LEFT JOIN sav_roles r ON r.rol_id = ur.url_role_id
                LEFT JOIN sav_companies c ON c.com_id = u.use_active_company_id
                WHERE {$where}
                ORDER BY u.use_lastname ASC, u.use_firstname ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $type);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'users' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'pages' => (int) max(1, ceil($total / $perPage)),
            'page' => $page,
        ];
    }

    public function search(string $query, int $limit = 10): array
    {
        $sql = "SELECT u.use_id, u.use_firstname, u.use_lastname, u.use_email,
                       r.rol_label AS primary_role_label
                FROM sav_users u
                LEFT JOIN sav_user_roles ur ON ur.url_user_id = u.use_id
                    AND ur.url_is_primary = 1
                    AND ur.url_revoked_at IS NULL
                LEFT JOIN sav_roles r ON r.rol_id = ur.url_role_id
                WHERE u.use_deleted_at IS NULL
                  AND u.use_is_active = 1
                  AND (u.use_lastname LIKE :q1 OR u.use_firstname LIKE :q2 OR u.use_email LIKE :q3)
                ORDER BY u.use_lastname ASC, u.use_firstname ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $like = '%' . trim($query) . '%';
        $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
