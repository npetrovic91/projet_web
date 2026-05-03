<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\GDPR\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class GdprRequestModel extends BaseModel
{
    protected string $table = 'sav_gdpr_requests';

    public function listRequests(array $filters = []): array
    {
        $conditions = ['1 = 1'];
        $params = [];
        if (($filters['status'] ?? '') !== '') {
            $conditions[] = 'r.grq_status = :status';
            $params['status'] = $filters['status'];
        }
        if (($filters['type'] ?? '') !== '') {
            $conditions[] = 'r.grq_type = :type';
            $params['type'] = $filters['type'];
        }

        return $this->db->fetchAll(
            "SELECT r.*, u.use_email, u.use_firstname, u.use_lastname
             FROM sav_gdpr_requests r
             INNER JOIN sav_users u ON u.use_id = r.grq_user_id
             WHERE " . implode(' AND ', $conditions) . "
             ORDER BY r.grq_created_at DESC",
            $params
        );
    }

    public function findRequest(int $requestId): ?array
    {
        return $this->db->fetch(
            "SELECT r.*, u.use_email, u.use_firstname, u.use_lastname
             FROM sav_gdpr_requests r
             INNER JOIN sav_users u ON u.use_id = r.grq_user_id
             WHERE r.grq_id = :id",
            ['id' => $requestId]
        );
    }

    public function updateStatus(int $requestId, string $status, int $adminId, ?string $response = null, ?string $rejectionReason = null): bool
    {
        return $this->db->execute(
            "UPDATE sav_gdpr_requests
             SET grq_status = :status,
                 grq_response = :response,
                 grq_rejection_reason = :rejection_reason,
                 grq_handled_by = :admin_id,
                 grq_handled_at = NOW(),
                 grq_updated_at = NOW()
             WHERE grq_id = :id",
            [
                'status' => $status,
                'response' => $response,
                'rejection_reason' => $rejectionReason,
                'admin_id' => $adminId,
                'id' => $requestId,
            ]
        );
    }
}
