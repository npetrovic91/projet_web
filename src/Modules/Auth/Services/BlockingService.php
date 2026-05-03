<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Services;

use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Modules\Auth\Models\EmailBlacklistModel;
use Nenad\Autosav\Modules\Auth\Models\IpBlacklistModel;
use Nenad\Autosav\Modules\Auth\Models\LoginAttemptModel;
use Nenad\Autosav\Modules\Auth\Models\UnblockHistoryModel;

class BlockingService
{
    public function __construct(
        private IpBlacklistModel $ipModel,
        private EmailBlacklistModel $emailModel,
        private LoginAttemptModel $attemptModel,
        private UnblockHistoryModel $unblockModel,
        private LogManager $logger
    ) {
    }

    public function isIpBlocked(string $ip): bool
    {
        return $this->ipModel->findActiveByIp($ip) !== null;
    }

    public function isEmailBlocked(string $email): bool
    {
        return $this->emailModel->findActiveByEmail($email) !== null;
    }

    public function evaluateAndBlockIp(string $ip, string $email): bool
    {
        $failures = $this->attemptModel->countRecentFailuresByIp($ip, AUTH_IP_WINDOW_MINUTES);
        if ($failures < AUTH_MAX_IP_ATTEMPTS || $this->isIpBlocked($ip)) {
            return false;
        }

        $this->ipModel->blockIp($ip, "{$failures} echecs de connexion", 'auto', AUTH_IP_BLOCK_DURATION_MINUTES, $failures);
        $this->logger->channel('security')->warning('ip_blocked_auto', ['ip' => $ip, 'email' => $email, 'failures' => $failures]);
        return true;
    }

    public function evaluateAndBlockEmail(string $email, string $ip): bool
    {
        $failures = $this->attemptModel->countRecentFailuresByEmail($email, AUTH_EMAIL_WINDOW_MINUTES);
        if ($failures < AUTH_MAX_EMAIL_ATTEMPTS || $this->isEmailBlocked($email)) {
            return false;
        }

        $this->emailModel->blockEmail($email, "{$failures} echecs de connexion", 'auto', AUTH_EMAIL_BLOCK_DURATION_MINUTES, $failures);
        $this->logger->channel('security')->warning('email_blocked_auto', ['email' => $email, 'ip' => $ip, 'failures' => $failures]);
        return true;
    }

    public function adminUnblockIp(int $blockId, int $adminId, string $adminIp, string $reason): bool
    {
        $block = $this->ipModel->findById($blockId);
        if (!$block) {
            throw new \RuntimeException("Blocage IP #{$blockId} introuvable.");
        }
        $result = $this->ipModel->unblockById($blockId, $adminId);
        if ($result) {
            $this->unblockModel->record('ip', $block['ibl_ip'], $blockId, $adminId, $adminIp, $reason);
            $this->logger->channel('security')->info('ip_unblocked_admin', ['block_id' => $blockId, 'admin_id' => $adminId]);
        }
        return $result;
    }

    public function adminUnblockEmail(int $blockId, int $adminId, string $adminIp, string $reason): bool
    {
        $block = $this->emailModel->findById($blockId);
        if (!$block) {
            throw new \RuntimeException("Blocage email #{$blockId} introuvable.");
        }
        $result = $this->emailModel->unblockById($blockId, $adminId);
        if ($result) {
            $this->unblockModel->record('email', $block['ebl_email'], $blockId, $adminId, $adminIp, $reason);
            $this->logger->channel('security')->info('email_unblocked_admin', ['block_id' => $blockId, 'admin_id' => $adminId]);
        }
        return $result;
    }

    public function getActiveIpBlocks(int $limit = 50, int $offset = 0): array
    {
        return $this->ipModel->getActiveBlocks($limit, $offset);
    }

    public function getActiveEmailBlocks(int $limit = 50, int $offset = 0): array
    {
        return $this->emailModel->getActiveBlocks($limit, $offset);
    }

    public function getBlockCounts(): array
    {
        return ['active_ip' => $this->ipModel->countActive(), 'active_email' => $this->emailModel->countActive()];
    }
}
