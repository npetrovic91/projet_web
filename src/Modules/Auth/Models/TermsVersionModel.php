<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class TermsVersionModel extends BaseModel
{
    private const TABLE = 'sav_terms_versions';

    public function findCurrent(): ?array
    {
        return $this->db()->fetch(
            "SELECT * FROM " . self::TABLE . "
             WHERE trv_is_current = 1
               AND trv_published_at IS NOT NULL
               AND trv_published_at <= NOW()
             ORDER BY trv_published_at DESC
             LIMIT 1"
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db()->fetch("SELECT * FROM " . self::TABLE . " WHERE trv_id = :id LIMIT 1", [':id' => $id]);
    }
}
