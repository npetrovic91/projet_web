<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Emails\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Emails\Models\EmailModel;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService implements ServiceInterface{
    private EmailModel $model;

    public function __construct(?EmailModel $model = null)
    {
        $this->model = $model ?? new EmailModel();
    }

    public function envoyer(array $message): array
    {
        $to = trim((string) ($message['to'] ?? $message['email_destinataire'] ?? ''));
        $subject = trim((string) ($message['subject'] ?? $message['sujet'] ?? ''));
        $body = (string) ($message['body'] ?? $message['corps'] ?? '');

        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Adresse email destinataire invalide.'];
        }
        if ($subject === '') {
            return ['success' => false, 'message' => 'Sujet email obligatoire.'];
        }

        $statusSent = $this->model->statutId('notification', 'envoyee');
        $statusFail = $this->model->statutId('notification', 'echouee');
        $sent = false;
        $error = null;

        try {
            $mail = $this->mailer();
            $mail->clearAllRecipients();
            if (defined('MAIL_SANDBOX') && MAIL_SANDBOX && defined('MAIL_SANDBOX_TO') && MAIL_SANDBOX_TO !== '') {
                $mail->addAddress((string) MAIL_SANDBOX_TO, 'Sandbox AUTOSAV');
                $subject = '[SANDBOX pour ' . $to . '] ' . $subject;
            } else {
                $mail->addAddress($to, (string) ($message['to_name'] ?? ''));
            }
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $this->wrapHtml($body, $message);
            $mail->AltBody = trim(strip_tags($body));
            $mail->send();
            $sent = true;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $logBody = $body;
        if ($error !== null) {
            $logBody .= "\n\n[ERREUR_ENVOI] " . mb_substr($error, 0, 500);
        }

        $logId = $this->model->journaliser([
            'modele_id' => $message['modele_id'] ?? null,
            'societe_expediteur_id' => $message['societe_expediteur_id'] ?? null,
            'societe_destinataire_id' => $message['societe_destinataire_id'] ?? null,
            'utilisateur_destinataire_id' => $message['utilisateur_destinataire_id'] ?? null,
            'email_destinataire' => $to,
            'type_evenement' => $message['type_evenement'] ?? 'email.manuel',
            'sujet' => $subject,
            'corps' => $logBody,
            'envoye' => $sent,
            'statut_id' => $sent ? $statusSent : $statusFail,
        ]);

        return [
            'success' => $sent,
            'log_id' => $logId,
            'message' => $sent ? 'Email envoyé.' : 'Email journalisé en échec : ' . $error,
        ];
    }

    public function envoyerModele(int $modeleId, array $recipient, array $variables = [], array $meta = []): array
    {
        $modele = $this->model->trouverModele($modeleId);
        if (!$modele) {
            return ['success' => false, 'message' => 'Modèle email introuvable.'];
        }

        $variables = array_merge($this->variablesDestinataire($recipient), $variables);
        return $this->envoyer(array_merge($meta, [
            'to' => $recipient['email'] ?? $recipient['uti_email'] ?? '',
            'to_name' => trim((string) (($recipient['pui_prenom'] ?? '') . ' ' . ($recipient['pui_nom'] ?? ''))),
            'subject' => $this->rendre($modele['mel_sujet'], $variables),
            'body' => $this->rendre($modele['mel_corps'], $variables),
            'modele_id' => $modeleId,
            'utilisateur_destinataire_id' => $recipient['uti_id'] ?? $recipient['utilisateur_id'] ?? null,
            'societe_destinataire_id' => $recipient['societe_id'] ?? null,
        ]));
    }

    public function rendre(string $template, array $variables): string
    {
        $map = [];
        foreach ($variables as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $map['{{' . $key . '}}'] = (string) $value;
            }
        }
        return strtr($template, $map);
    }

    public function creerTokenDesabonnement(int $userId, string $email): string
    {
        $payload = [
            'uid' => $userId,
            'email' => mb_strtolower(trim($email)),
            'ts' => time(),
        ];
        $payload['sig'] = hash_hmac('sha256', $payload['uid'] . '|' . $payload['email'] . '|' . $payload['ts'], $this->secret());
        return rtrim(strtr(base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
    }

    public function lireTokenDesabonnement(string $token): ?array
    {
        $json = base64_decode(strtr($token, '-_', '+/'), true);
        $payload = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($payload) || empty($payload['uid']) || empty($payload['email']) || empty($payload['ts']) || empty($payload['sig'])) {
            return null;
        }
        $sig = hash_hmac('sha256', (int) $payload['uid'] . '|' . mb_strtolower((string) $payload['email']) . '|' . (int) $payload['ts'], $this->secret());
        if (!hash_equals($sig, (string) $payload['sig'])) {
            return null;
        }
        return $payload;
    }

    private function mailer(): PHPMailer
    {
        $mail = new PHPMailer(true);

        if (!defined('MAIL_HOST')) {
            $mailConfig = dirname(__DIR__, 3) . '/config/mail.php';
            if (is_file($mailConfig)) {
                require_once $mailConfig;
            }
        }

        $host = defined('MAIL_HOST') ? (string) MAIL_HOST : '';
        $username = defined('MAIL_USERNAME') ? (string) MAIL_USERNAME : '';
        $password = defined('MAIL_PASSWORD') ? (string) MAIL_PASSWORD : '';

        if ($host !== '') {
            if ((defined('MAIL_FAIL_IF_UNCONFIGURED') && MAIL_FAIL_IF_UNCONFIGURED) && ($username === '' || $password === '')) {
                throw new \RuntimeException('Configuration SMTP incomplète : MAIL_USERNAME ou MAIL_PASSWORD manquant.');
            }
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = ($username !== '' || $password !== '');
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->SMTPSecure = defined('MAIL_ENCRYPTION') ? (string) MAIL_ENCRYPTION : 'tls';
            $mail->Port = defined('MAIL_PORT') ? (int) MAIL_PORT : 587;
        } elseif (defined('MAIL_FAIL_IF_UNCONFIGURED') && MAIL_FAIL_IF_UNCONFIGURED) {
            throw new \RuntimeException('Configuration SMTP absente : MAIL_HOST manquant.');
        }

        $mail->CharSet = defined('MAIL_CHARSET') ? (string) MAIL_CHARSET : 'UTF-8';
        $from = defined('MAIL_FROM_EMAIL') ? (string) MAIL_FROM_EMAIL : (defined('MAIL_FROM') ? (string) MAIL_FROM : 'noreply@autosav.local');
        $fromName = defined('MAIL_FROM_NAME') ? (string) MAIL_FROM_NAME : 'AUTOSAV';
        $mail->setFrom($from, $fromName);
        if (defined('MAIL_REPLY_TO') && MAIL_REPLY_TO) {
            $mail->addReplyTo((string) MAIL_REPLY_TO);
        }
        return $mail;
    }

    private function wrapHtml(string $body, array $message = []): string
    {
        $appUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : '';
        $unsubscribe = (string) ($message['unsubscribe_url'] ?? '');
        $alreadyHtml = str_contains(mb_strtolower($body), '<html') || str_contains(mb_strtolower($body), '<body');
        if ($alreadyHtml) {
            return $body;
        }
        $footer = '';
        if ($unsubscribe !== '') {
            $footer = '<p style="color:#888;font-size:12px;text-align:center"><a href="' . htmlspecialchars($unsubscribe, ENT_QUOTES, 'UTF-8') . '">Se désabonner des emails groupés</a></p>';
        }
        $logoPath = defined('PUBLIC_PATH') ? PUBLIC_PATH . '/assets/img/logo-autosav.png' : '';
        $logo = $appUrl !== '' && $logoPath !== '' && is_file($logoPath)
            ? '<img src="' . htmlspecialchars($appUrl . '/assets/img/logo-autosav.png', ENT_QUOTES, 'UTF-8') . '" alt="AUTOSAV" style="height:40px;margin-bottom:20px">'
            : '<p style="color:#007bff;font-size:22px;font-weight:bold;margin:0 0 20px">AUTOSAV</p>';
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:20px"><div style="max-width:680px;margin:auto;background:#fff;border-radius:8px;padding:32px">' . $logo . $body . '<hr style="border:none;border-top:1px solid #eee;margin:24px 0">' . $footer . '</div></body></html>';
    }

    private function variablesDestinataire(array $recipient): array
    {
        $prenom = (string) ($recipient['pui_prenom'] ?? $recipient['firstname'] ?? '');
        $nom = (string) ($recipient['pui_nom'] ?? $recipient['lastname'] ?? '');
        $email = (string) ($recipient['uti_email'] ?? $recipient['email'] ?? '');
        return [
            'firstname' => $prenom,
            'lastname' => $nom,
            'fullname' => trim($prenom . ' ' . $nom),
            'email' => $email,
            'societe' => (string) ($recipient['societe_nom'] ?? ''),
        ];
    }

    private function secret(): string
    {
        $secret = defined('ENCRYPTION_KEY') ? (string) ENCRYPTION_KEY : '';
        if (strlen($secret) < 32) {
            throw new \RuntimeException('Cle applicative absente ou trop courte pour signer les emails.');
        }
        return $secret;
    }
}
