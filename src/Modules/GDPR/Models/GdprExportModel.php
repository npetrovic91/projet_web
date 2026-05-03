<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\GDPR\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class GdprExportModel extends BaseModel
{
    protected string $table = 'sav_gdpr_exports';

    public function record(int $userId, ?int $requestId, string $fileName, int $adminId, string $ip): int
    {
        $this->db->execute(
            "INSERT INTO sav_gdpr_exports
                (gex_uuid, gex_user_id, gex_request_id, gex_format, gex_file_name,
                 gex_generated_by, gex_generated_ip, gex_expires_at, gex_created_at)
             VALUES
                (:uuid, :user_id, :request_id, 'json', :file_name,
                 :admin_id, :ip, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW())",
            [
                'uuid' => $this->uuid(),
                'user_id' => $userId,
                'request_id' => $requestId,
                'file_name' => $fileName,
                'admin_id' => $adminId,
                'ip' => $ip,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
