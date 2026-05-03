<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Logger;

class DatabaseHandler implements HandlerInterface
{
    private int $minLevel;

    public function __construct(string $minLevel = LogLevel::WARNING)
    {
        $this->minLevel = LogLevel::toInt($minLevel);
    }

    public function handle(string $level, string $channel, string $message, array $context): void
    {
        if (!$this->isHandling($level)) { return; }
        try {
            $pdo  = db()->getPdo();
            $stmt = $pdo->prepare(
                "INSERT INTO sav_audit_log 
                 (aud_user_id, aud_ip, aud_channel, aud_event, aud_level, aud_message, aud_context, aud_user_agent, aud_created_at)
                 VALUES (:uid, :ip, :channel, :event, :level, :message, :context, :ua, NOW())"
            );
            $userId  = $_SESSION['user_id'] ?? null;
            $ip      = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua      = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $event   = $context['event'] ?? $level;
            $ctxJson = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : null;

            $stmt->bindValue(':uid',     $userId,  $userId  ? \PDO::PARAM_INT : \PDO::PARAM_NULL);
            $stmt->bindValue(':ip',      $ip,      $ip      ? \PDO::PARAM_STR : \PDO::PARAM_NULL);
            $stmt->bindValue(':channel', $channel, \PDO::PARAM_STR);
            $stmt->bindValue(':event',   $event,   \PDO::PARAM_STR);
            $stmt->bindValue(':level',   $level,   \PDO::PARAM_STR);
            $stmt->bindValue(':message', $message, \PDO::PARAM_STR);
            $stmt->bindValue(':context', $ctxJson, $ctxJson ? \PDO::PARAM_STR : \PDO::PARAM_NULL);
            $stmt->bindValue(':ua',      $ua,      $ua      ? \PDO::PARAM_STR : \PDO::PARAM_NULL);
            $stmt->execute();
        } catch (\Throwable $e) {
            // Ne pas faire échouer l'application si le log DB échoue
            error_log('[AUTOSAV LOG DB ERROR] ' . $e->getMessage());
        }
    }

    public function isHandling(string $level): bool
    {
        return LogLevel::toInt($level) <= $this->minLevel;
    }
}
