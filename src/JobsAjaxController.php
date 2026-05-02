<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Services;

use Nenad\Autosav\Modules\Notifications\Models\EventTriggerModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationAuditModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationContactModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationRuleModel;

class NotificationRuleService
{
    public function __construct(
        private EventTriggerModel $events,
        private NotificationContactModel $contacts,
        private NotificationRuleModel $rules,
        private NotificationModel $notifications,
        private NotificationAuditModel $audit
    ) {}

    public function dashboard(int $companyId): array
    {
        return [
            'events' => $this->events->allActive(),
            'contacts' => $this->contacts->forCompany($companyId),
            'rules' => $this->rules->forCompany($companyId),
        ];
    }

    public function createContact(array $data, int $adminId, string $ip): array
    {
        $errors = $this->validateContact($data);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }
        $id = $this->contacts->create($data, $adminId);
        $this->audit->record(null, null, 'contact_created', $adminId, $ip, ['contact_id' => $id]);
        return ['success' => true, 'id' => $id, 'errors' => []];
    }

    public function createRule(array $data, int $adminId, string $ip): array
    {
        if (empty($data['company_id']) || empty($data['event_trigger_id']) || empty($data['contact_id'])) {
            return ['success' => false, 'errors' => ['general' => 'Entreprise, evenement et contact sont obligatoires.']];
        }
        $id = $this->rules->create($data, $adminId);
        $this->audit->record($id ?: null, null, 'rule_created', $adminId, $ip, $data);
        return ['success' => true, 'id' => $id, 'errors' => []];
    }

    public function toggleContact(int $contactId, bool $active, int $adminId, string $ip): void
    {
        $this->contacts->setActive($contactId, $active, $adminId);
        $this->audit->record(null, null, $active ? 'contact_enabled' : 'contact_disabled', $adminId, $ip, ['contact_id' => $contactId]);
    }

    public function toggleRule(int $ruleId, bool $active, int $adminId, string $ip): void
    {
        $this->rules->setActive($ruleId, $active, $adminId);
        $this->audit->record($ruleId, null, $active ? 'rule_enabled' : 'rule_disabled', $adminId, $ip);
    }

    public function dispatchEvent(string $eventCode, int $companyId, string $title, string $message, array $payload = []): int
    {
        $rules = $this->rules->activeRulesForEvent($eventCode, $companyId);
        $count = 0;
        foreach ($rules as $rule) {
            $channels = json_decode((string) $rule['nru_channels'], true) ?: ['email'];
            foreach ($channels as $channel) {
                $notificationId = $this->notifications->queue(
                    $rule['nco_user_id'] ? (int) $rule['nco_user_id'] : null,
                    (int) $rule['nco_id'],
                    (int) $rule['evt_id'],
                    (string) $channel,
                    $title,
                    $message,
                    $payload
                );
                $this->audit->record((int) $rule['nru_id'], $notificationId, 'notification_queued', null, client_ip(), ['event_code' => $eventCode]);
                $count++;
            }
        }
        return $count;
    }

    private function validateContact(array $data): array
    {
        $errors = [];
        if (empty($data['company_id'])) {
            $errors['company_id'] = 'La structure est obligatoire.';
        }
        if (empty($data['email']) || !filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Une adresse email valide est obligatoire.';
        }
        return $errors;
    }
}
