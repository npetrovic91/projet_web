<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class NotificationTemplateModel extends BaseModel
{
    protected string $table = 'sav_modeles_notifications';
    protected string $colPrefix = 'mno_';

    public function allActive(): array
    {
        return $this->db->fetchAll(
            "SELECT m.*, c.cno_code, c.cno_nom
             FROM sav_modeles_notifications m
             LEFT JOIN sav_canaux_notifications c ON c.cno_id = m.mno_canal_notification_id
             WHERE m.mno_supprime_le IS NULL
             ORDER BY m.mno_nom ASC"
        );
    }

    public function findById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        return $this->db->fetch(
            "SELECT * FROM sav_modeles_notifications
             WHERE mno_id = :id AND mno_supprime_le IS NULL
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function render(?int $templateId, string $fallbackTitle, string $fallbackMessage, array $payload = []): array
    {
        $template = $templateId ? $this->findById($templateId) : null;
        $title = $template['mno_titre_modele'] ?? $fallbackTitle;
        $message = $template['mno_corps_modele'] ?? $fallbackMessage;

        foreach ($payload as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $title = str_replace('{{' . $key . '}}', (string) $value, $title);
                $message = str_replace('{{' . $key . '}}', (string) $value, $message);
            }
        }

        return ['title' => $title, 'message' => $message, 'template_id' => $template ? (int) $template['mno_id'] : null];
    }
}
