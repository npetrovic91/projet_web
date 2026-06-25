<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Notifications\Models\EventTriggerModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationAuditModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationChannelModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationContactModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationPreferenceModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationRuleModel;
use Nenad\Autosav\Modules\Notifications\Models\NotificationTemplateModel;

class NotificationRuleService implements ServiceInterface{
    public function __construct(
        private EventTriggerModel $events,
        private NotificationContactModel $contacts,
        private NotificationRuleModel $rules,
        private NotificationModel $notifications,
        private NotificationAuditModel $audit,
        private ?NotificationChannelModel $channels = null,
        private ?NotificationTemplateModel $templates = null,
        private ?NotificationPreferenceModel $preferences = null
    ) {
        $this->channels ??= new NotificationChannelModel();
        $this->templates ??= new NotificationTemplateModel();
        $this->preferences ??= new NotificationPreferenceModel();
    }

    public function dashboard(int $companyId): array
    {
        return [
            'events' => $this->events->allActive(),
            'contacts' => $this->contacts->forCompany($companyId),
            'rules' => $this->rules->forCompany($companyId),
            'channels' => $this->channels->allActive(),
            'templates' => $this->templates->allActive(),
            'recent_notifications' => $this->notifications->recentForCompany($companyId, 20),
        ];
    }

    public function createContact(array $data, int $adminId, string $ip): array
    {
        $errors = $this->validateContact($data);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }
        $id = $this->contacts->create($data, $adminId);
        $this->audit->record(null, null, 'contact_notification_cree', $adminId, $ip, [
            'contact_id' => $id,
            'company_id' => (int) $data['company_id'],
        ]);
        return ['success' => true, 'id' => $id, 'errors' => []];
    }

    public function createRule(array $data, int $adminId, string $ip): array
    {
        $errors = [];
        if (empty($data['company_id'])) {
            $errors['company_id'] = 'La société est obligatoire.';
        }
        if (empty($data['event_code'])) {
            $errors['event_code'] = 'Le code événement est obligatoire.';
        }
        if (empty($data['contact_id'])) {
            $errors['contact_id'] = 'Le contact destinataire est obligatoire.';
        }
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        $event = $this->events->findByCode((string) $data['event_code']);
        $data['event_name'] = $data['event_name'] ?? ($event['evt_label'] ?? $data['event_code']);

        $id = $this->rules->create($data, $adminId);
        $this->audit->record($id ?: null, null, 'abonnement_evenement_notification_cree', $adminId, $ip, $data);
        return ['success' => true, 'id' => $id, 'errors' => []];
    }

    public function toggleContact(int $contactId, bool $active, int $adminId, string $ip): void
    {
        $this->contacts->setActive($contactId, $active, $adminId);
        $this->audit->record(null, null, $active ? 'contact_notification_active' : 'contact_notification_desactive', $adminId, $ip, ['contact_id' => $contactId]);
    }

    public function toggleRule(int $ruleId, bool $active, int $adminId, string $ip): void
    {
        $this->rules->setActive($ruleId, $active, $adminId);
        $this->audit->record($ruleId, null, $active ? 'abonnement_evenement_active' : 'abonnement_evenement_desactive', $adminId, $ip);
    }

    public function dispatchEvent(
        string $eventCode,
        int $companyId,
        string $title,
        string $message,
        array $payload = [],
        ?int $emitterUserId = null,
        ?string $targetType = null,
        ?int $targetId = null
    ): int {
        $eventCode = strtoupper(trim($eventCode));
        $eventId = $this->events->createApplicationEvent(
            $eventCode,
            $payload['event_name'] ?? $eventCode,
            $companyId,
            isset($payload['module_id']) ? (int) $payload['module_id'] : null,
            $emitterUserId,
            $targetType,
            $targetId,
            $payload
        );

        $rules = $this->rules->activeRulesForEvent($eventCode, $companyId);
        $count = 0;
        foreach ($rules as $rule) {
            $config = is_array($rule['config'] ?? null) ? $rule['config'] : [];
            $channels = json_decode((string) $rule['nru_channels'], true) ?: ['application'];
            $rendered = $this->templates->render(
                isset($config['template_id']) ? (int) $config['template_id'] : null,
                (string) ($config['title'] ?? $title),
                (string) ($config['message'] ?? $message),
                $payload
            );

            foreach ($channels as $channel) {
                $channelId = $this->channels->idByCode((string) $channel);
                $userId = !empty($rule['nco_user_id']) ? (int) $rule['nco_user_id'] : null;
                if ($userId && $channelId && !$this->preferences->isAllowed($userId, $eventCode, $channelId)) {
                    continue;
                }
                $notificationPayload = array_merge($payload, [
                    'event_code' => $eventCode,
                    'event_application_id' => $eventId,
                    'event_subscription_id' => (int) $rule['nru_id'],
                    'template_id' => $rendered['template_id'],
                    'priority' => $config['priority'] ?? 3,
                    'email' => $rule['nco_email'] ?? null,
                    'target_type' => $targetType,
                    'target_id' => $targetId,
                ]);

                $notificationId = $this->notifications->queue(
                    $userId,
                    !empty($rule['nco_id']) ? (int) $rule['nco_id'] : null,
                    (int) $rule['nru_id'],
                    (string) $channel,
                    $rendered['title'],
                    $rendered['message'],
                    $notificationPayload,
                    $companyId,
                    $emitterUserId
                );
                $this->audit->record((int) $rule['nru_id'], $notificationId, 'notification_filee', $emitterUserId, client_ip(), [
                    'event_code' => $eventCode,
                    'company_id' => $companyId,
                    'channel' => $channel,
                ]);
                $count++;
            }
        }
        return $count;
    }

    private function validateContact(array $data): array
    {
        $errors = [];
        if (empty($data['company_id'])) {
            $errors['company_id'] = 'La société est obligatoire.';
        }
        $hasUser = trim((string) ($data['user_id'] ?? '')) !== '';
        $email = trim((string) ($data['email'] ?? ''));
        if (!$hasUser && ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $errors['email'] = 'Une adresse email valide est obligatoire pour un contact externe.';
        }
        if ($hasUser && (!ctype_digit((string) $data['user_id']) || (int) $data['user_id'] <= 0)) {
            $errors['user_id'] = 'L’identifiant utilisateur interne est invalide.';
        }
        return $errors;
    }
}
