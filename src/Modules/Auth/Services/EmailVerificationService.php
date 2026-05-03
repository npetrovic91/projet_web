<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Services;

use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Modules\Auth\Models\EmailTokenModel;

/**
 * Service d'envoi d'emails d'authentification via PHPMailer.
 * PHPMailer est chargÃ© via require_once depuis src/Vendor/.
 */
class EmailVerificationService
{
    private EmailTokenModel $tokenModel;
    private LogManager      $logger;

    public function __construct(EmailTokenModel $tokenModel, LogManager $logger)
    {
        $this->tokenModel = $tokenModel;
        $this->logger     = $logger;

        if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
            autosav_load_phpmailer();
        }
    }

    /**
     * GÃ©nÃ¨re et envoie un email de validation d'adresse.
     *
     * @param int    $userId    ID de l'utilisateur
     * @param string $email     Email de destination
     * @param string $firstname PrÃ©nom (personnalisation)
     * @return bool             true si l'email a Ã©tÃ© envoyÃ© avec succÃ¨s
     */
    public function sendVerificationEmail(int $userId, string $email, string $firstname): bool
    {
        // Invalider les tokens prÃ©cÃ©dents
        $this->tokenModel->invalidatePrevious($userId);

        // GÃ©nÃ©rer un token cryptographiquement sÃ»r
        $token     = bin2hex(random_bytes(TOKEN_BYTE_LENGTH));
        $tokenHash = hash('sha256', $token);

        // Enregistrer en base (hash uniquement)
        $this->tokenModel->create($userId, $tokenHash, EMAIL_TOKEN_EXPIRY_HOURS);

        // Construire le lien
        $verifyUrl = rtrim(APP_URL, '/') . '/auth/verify-email/' . urlencode($token);

        // Contenu email
        $subject = 'Validation de votre adresse email â€” Autosav';
        $body    = $this->buildVerificationEmailBody($firstname, $verifyUrl);

        return $this->sendEmail($email, $firstname, $subject, $body);
    }

    /**
     * GÃ©nÃ¨re et envoie un email de rÃ©initialisation de mot de passe.
     * MÃ©thode dÃ©couplÃ©e : utilisÃ©e par PasswordResetService.
     *
     * @param string $email     Email de destination
     * @param string $firstname PrÃ©nom
     * @param string $token     Token brut (envoyÃ© dans l'email)
     * @return bool
     */
    public function sendPasswordResetEmail(string $email, string $firstname, string $token): bool
    {
        $resetUrl = rtrim(APP_URL, '/') . '/auth/reset-password/' . urlencode($token);

        $subject = 'RÃ©initialisation de votre mot de passe â€” Autosav';
        $body    = $this->buildResetEmailBody($firstname, $resetUrl);

        return $this->sendEmail($email, $firstname, $subject, $body);
    }

    /**
     * Envoie un email via PHPMailer SMTP.
     *
     * @param string $toEmail
     * @param string $toName
     * @param string $subject
     * @param string $htmlBody
     * @return bool
     */
    private function sendEmail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
    {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Configuration SMTP
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = MAIL_ENCRYPTION === 'ssl'
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAIL_PORT;
            $mail->CharSet    = MAIL_CHARSET;
            $mail->SMTPDebug  = MAIL_DEBUG;

            // ExpÃ©diteur
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addReplyTo(MAIL_REPLY_TO, MAIL_FROM_NAME);

            // Destinataire
            $mail->addAddress($toEmail, $toName);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

            $mail->send();

            $this->logger->channel('application')->info('email_sent', [
                'to'      => $toEmail,
                'subject' => $subject,
            ]);

            return true;

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            $this->logger->channel('application')->error('email_send_failed', [
                'to'      => $toEmail,
                'subject' => $subject,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Corps HTML de l'email de validation.
     *
     * @param string $firstname PrÃ©nom
     * @param string $url       Lien de validation
     * @return string
     */
    private function buildVerificationEmailBody(string $firstname, string $url): string
    {
        $expiry = EMAIL_TOKEN_EXPIRY_HOURS;
        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #343a40;">Validation de votre adresse email</h2>
            <p>Bonjour {$this->escape($firstname)},</p>
            <p>Votre compte Autosav a Ã©tÃ© crÃ©Ã©. Pour activer votre accÃ¨s, veuillez valider votre adresse email en cliquant sur le bouton ci-dessous :</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="{$this->escape($url)}"
                   style="background-color: #007bff; color: white; padding: 12px 24px;
                          text-decoration: none; border-radius: 4px; font-size: 16px;">
                    Valider mon adresse email
                </a>
            </p>
            <p style="color: #6c757d; font-size: 13px;">
                Ce lien est valable <strong>{$expiry} heures</strong>.<br>
                Si vous n'Ãªtes pas Ã  l'origine de cette demande, ignorez cet email.
            </p>
            <hr style="border-color: #dee2e6;">
            <p style="color: #6c757d; font-size: 12px;">
                Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
                <a href="{$this->escape($url)}">{$this->escape($url)}</a>
            </p>
        </div>
        HTML;
    }

    /**
     * Corps HTML de l'email de reset password.
     *
     * @param string $firstname PrÃ©nom
     * @param string $url       Lien de reset
     * @return string
     */
    private function buildResetEmailBody(string $firstname, string $url): string
    {
        $expiry = RESET_TOKEN_EXPIRY_HOURS;
        return <<<HTML
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
            <h2 style="color: #343a40;">RÃ©initialisation de votre mot de passe</h2>
            <p>Bonjour {$this->escape($firstname)},</p>
            <p>Vous avez demandÃ© la rÃ©initialisation de votre mot de passe Autosav.</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="{$this->escape($url)}"
                   style="background-color: #dc3545; color: white; padding: 12px 24px;
                          text-decoration: none; border-radius: 4px; font-size: 16px;">
                    RÃ©initialiser mon mot de passe
                </a>
            </p>
            <p style="color: #6c757d; font-size: 13px;">
                Ce lien est valable <strong>{$expiry} heure(s)</strong> et ne peut Ãªtre utilisÃ© qu'une seule fois.<br>
                Si vous n'avez pas effectuÃ© cette demande, ignorez cet email.
            </p>
            <hr style="border-color: #dee2e6;">
            <p style="color: #6c757d; font-size: 12px;">
                Lien direct : <a href="{$this->escape($url)}">{$this->escape($url)}</a>
            </p>
        </div>
        HTML;
    }

    /**
     * Ã‰chappe les caractÃ¨res spÃ©ciaux HTML.
     *
     * @param string $value
     * @return string
     */
    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
