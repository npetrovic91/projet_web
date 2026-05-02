<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Services;

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Core\Security\SecurityManager;
use Nenad\Autosav\Modules\Auth\Models\LoginAttemptModel;

class AuthService
{
    public const RESULT_SUCCESS = 'success';
    public const RESULT_IP_BLOCKED = 'ip_blocked';
    public const RESULT_EMAIL_BLOCKED = 'email_blocked';
    public const RESULT_INVALID_CREDENTIALS = 'invalid_credentials';
    public const RESULT_EMAIL_NOT_VERIFIED = 'email_not_verified';
    public const RESULT_ACCOUNT_LOCKED = 'account_locked';
    public const RESULT_ACCOUNT_INACTIVE = 'account_inactive';

    public function __construct(
        private BlockingService $blockingService,
        private LoginAttemptModel $attemptModel,
        private SecurityManager $security,
        private LogManager $logger
    ) {
    }

    public function processLogin(string $rawEmail, string $rawPassword, string $ip, string $userAgent): array
    {
        $email = mb_strtolower(trim($rawEmail));

        if ($this->blockingService->isIpBlocked($ip)) {
            $this->attemptModel->record($ip, $email, false, self::RESULT_IP_BLOCKED, null, $userAgent);
            return $this->fail(self::RESULT_IP_BLOCKED, 'Acces temporairement bloque.');
        }

        if ($this->blockingService->isEmailBlocked($email)) {
            $this->attemptModel->record($ip, $email, false, self::RESULT_EMAIL_BLOCKED, null, $userAgent);
            return $this->fail(self::RESULT_EMAIL_BLOCKED, 'Acces temporairement bloque.');
        }

        $user = $this->findUserByEmail($email);
        if ($user === null) {
            $this->attemptModel->record($ip, $email, false, 'user_not_found', null, $userAgent);
            $this->blockingService->evaluateAndBlockIp($ip, $email);
            return $this->fail(self::RESULT_INVALID_CREDENTIALS, 'Identifiants incorrects.');
        }

        $userId = (int) $user['use_id'];

        if (!(bool) $user['use_is_active']) {
            $this->attemptModel->record($ip, $email, false, self::RESULT_ACCOUNT_INACTIVE, $userId, $userAgent);
            return $this->fail(self::RESULT_ACCOUNT_INACTIVE, 'Compte desactive.');
        }

        if ($user['use_email_verified_at'] === null) {
            $this->attemptModel->record($ip, $email, false, self::RESULT_EMAIL_NOT_VERIFIED, $userId, $userAgent);
            return $this->fail(self::RESULT_EMAIL_NOT_VERIFIED, 'Adresse email non verifiee.');
        }

        if ((bool) $user['use_is_locked']) {
            if ($user['use_locked_until'] !== null && strtotime((string) $user['use_locked_until']) < time()) {
                $this->unlockUserAccount($userId);
            } else {
                $this->attemptModel->record($ip, $email, false, self::RESULT_ACCOUNT_LOCKED, $userId, $userAgent);
                return $this->fail(self::RESULT_ACCOUNT_LOCKED, 'Compte temporairement verrouille.');
            }
        }

        if (!$this->security->verifyPassword($rawPassword, $user['use_password_hash'])) {
            $this->attemptModel->record($ip, $email, false, 'invalid_password', $userId, $userAgent);
            $this->incrementFailedAttempts($userId);
            $this->blockingService->evaluateAndBlockIp($ip, $email);
            $this->blockingService->evaluateAndBlockEmail($email, $ip);
            return $this->fail(self::RESULT_INVALID_CREDENTIALS, 'Identifiants incorrects.');
        }

        $this->resetFailedAttempts($userId);
        $this->updateLastLogin($userId, $ip, $userAgent);
        $this->attemptModel->record($ip, $email, true, null, $userId, $userAgent);

        unset($user['use_password_hash'], $user['use_2fa_secret'], $user['use_2fa_backup_codes']);
        return ['result' => self::RESULT_SUCCESS, 'message' => 'Connexion reussie.', 'user' => $user];
    }

    private function findUserByEmail(string $email): ?array
    {
        return Database::getInstance()->fetch(
            "SELECT use_id, use_uuid, use_email, use_password_hash, use_firstname, use_lastname,
                    use_civility, use_email_verified_at, use_is_active, use_is_locked,
                    use_locked_until, use_locked_reason, use_failed_login_attempts,
                    use_active_company_id, use_active_brand_id, use_terms_accepted_version,
                    use_terms_accepted_at, use_locale, use_timezone, use_2fa_enabled,
                    use_2fa_secret, use_2fa_backup_codes, use_gdpr_anonymized, use_deleted_at
             FROM sav_users
             WHERE use_email = :email
               AND use_deleted_at IS NULL
               AND use_gdpr_anonymized = 0
             LIMIT 1",
            [':email' => $email]
        );
    }

    private function incrementFailedAttempts(int $userId): void
    {
        Database::getInstance()->execute(
            "UPDATE sav_users
             SET use_failed_login_attempts = use_failed_login_attempts + 1,
                 use_updated_at = NOW()
             WHERE use_id = :id",
            [':id' => $userId]
        );
    }

    private function resetFailedAttempts(int $userId): void
    {
        Database::getInstance()->execute(
            "UPDATE sav_users
             SET use_failed_login_attempts = 0,
                 use_updated_at = NOW()
             WHERE use_id = :id",
            [':id' => $userId]
        );
    }

    private function updateLastLogin(int $userId, string $ip, string $userAgent): void
    {
        Database::getInstance()->execute(
            "UPDATE sav_users
             SET use_last_login_at = NOW(),
                 use_last_login_ip = :ip,
                 use_last_user_agent = :ua,
                 use_updated_at = NOW()
             WHERE use_id = :id",
            [':ip' => $ip, ':ua' => mb_substr($userAgent, 0, 500), ':id' => $userId]
        );
    }

    private function unlockUserAccount(int $userId): void
    {
        Database::getInstance()->execute(
            "UPDATE sav_users
             SET use_is_locked = 0,
                 use_locked_until = NULL,
                 use_locked_reason = NULL,
                 use_updated_at = NOW()
             WHERE use_id = :id",
            [':id' => $userId]
        );
    }

    private function fail(string $result, string $message): array
    {
        return ['result' => $result, 'message' => $message, 'user' => null];
    }

    public static function detectClientIp(): string
    {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        if ($forwarded !== null) {
            $first = trim(explode(',', $forwarded)[0]);
            if (filter_var($first, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $first;
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
