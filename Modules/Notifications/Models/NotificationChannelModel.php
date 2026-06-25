<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class NotificationChannelModel extends BaseModel
{
    protected string $table = 'sav_canaux_notifications';
    protected string $colPrefix = 'cno_';

    public function allActive(): array
    {
        return $this->db->fetchAll(
            "SELECT cno_id, cno_code, cno_nom, cno_description, cno_est_systeme
             FROM sav_canaux_notifications
             WHERE cno_supprime_le IS NULL
             ORDER BY cno_nom ASC"
        );
    }

    public function idByCode(string $code): ?int
    {
        $row = $this->db->fetch(
            "SELECT cno_id
             FROM sav_canaux_notifications
             WHERE cno_code = :code AND cno_supprime_le IS NULL
             LIMIT 1",
            ['code' => mb_strtolower(trim($code))]
        );
        return $row ? (int) $row['cno_id'] : null;
    }
}
