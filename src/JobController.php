<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class EventTriggerModel extends BaseModel
{
    protected string $table = 'sav_event_triggers';

    public function allActive(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM sav_event_triggers
             WHERE evt_is_active = 1
             ORDER BY evt_module ASC, evt_label ASC"
        );
    }

    public function findByCode(string $code): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM sav_event_triggers WHERE evt_code = :code AND evt_is_active = 1",
            ['code' => strtoupper(trim($code))]
        );
    }
}
