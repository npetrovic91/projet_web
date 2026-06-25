<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Notifications\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;
use Nenad\Autosav\Modules\Emails\Services\EmailService;
use Nenad\Autosav\Modules\Notifications\Models\NotificationModel;

/**
 * AUTOSAV — Point d'entrée unique pour notifier un utilisateur d'une
 * action sensible le concernant (in-app + email réel optionnel).
 *
 * Avant cette classe, Modules/Notifications/Models/NotificationModel::queue()
 * existait déjà (table sav_notifications + sav_destinataires_notifications
 * fonctionnelles) mais n'était jamais appelé par aucune action métier
 * (seulement par l'UI de lecture des notifications et l'admin
 * EventTriggers) : aucune action sensible ne notifiait personne.
 *
 * Le canal "email" de NotificationModel::queue() ne fait que JOURNALISER
 * l'intention dans sav_journaux_emails (aucun envoi réel) : pour un envoi
 * SMTP effectif via PHPMailer, cette classe appelle explicitement
 * EmailService::envoyer() en plus de la notification in-app.
 */
final class ActionNotifier implements ServiceInterface
{
    private NotificationModel $notifications;
    private EmailService $emails;

    public function __construct(?NotificationModel $notifications = null, ?EmailService $emails = null)
    {
        $this->notifications = $notifications ?? new NotificationModel();
        $this->emails = $emails ?? new EmailService();
    }

    /**
     * Notification in-app systématique. L'envoi d'email réel (PHPMailer)
     * est optionnel et séparé : on ne veut pas un email pour chaque
     * notification in-app, seulement pour les actions explicitement
     * désignées comme devant déclencher un email (changement de mot de
     * passe, changement de rôle...).
     */
    public function notifierUtilisateur(
        int $userId,
        string $type,
        string $titre,
        string $message,
        array $payload = [],
        ?int $companyId = null,
        ?int $createdBy = null
    ): int {
        return $this->notifications->queue(
            $userId,
            null,
            null,
            'application',
            $titre,
            $message,
            array_merge($payload, ['type' => $type]),
            $companyId,
            $createdBy
        );
    }

    /**
     * Notification in-app + email réel (PHPMailer, via EmailService).
     * L'échec d'envoi email ne doit jamais faire échouer l'action métier
     * qui a déclenché la notification : capturé et journalisé seulement.
     */
    public function notifierUtilisateurAvecEmail(
        int $userId,
        string $emailDestinataire,
        string $type,
        string $titre,
        string $message,
        array $payload = [],
        ?int $companyId = null,
        ?int $createdBy = null
    ): int {
        $notificationId = $this->notifierUtilisateur($userId, $type, $titre, $message, $payload, $companyId, $createdBy);

        if ($emailDestinataire !== '' && filter_var($emailDestinataire, FILTER_VALIDATE_EMAIL)) {
            try {
                $this->emails->envoyer([
                    'to' => $emailDestinataire,
                    'subject' => $titre,
                    'body' => $message,
                    'type_evenement' => $type,
                    'utilisateur_destinataire_id' => $userId,
                    'societe_destinataire_id' => $companyId,
                ]);
            } catch (\Throwable $e) {
                if (function_exists('logger')) {
                    logger('email')->warning('action_notifier_email_echec', [
                        'type' => $type,
                        'user_id' => $userId,
                        'erreur' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $notificationId;
    }
}
