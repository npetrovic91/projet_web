<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Services\Production;

final class BackupService
{
    /**
     * @return array<string,mixed>
     */
    public function createDatabaseBackup(): array
    {
        $backupDir = defined('PRODUCTION_BACKUP_DIR') ? (string) PRODUCTION_BACKUP_DIR : dirname(__DIR__, 4) . '/storage/backups';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0770, true);
        }
        if (!is_dir($backupDir) || !is_writable($backupDir)) {
            return ['status' => 'NOTOK', 'message' => 'Répertoire de sauvegarde non accessible.', 'path' => $backupDir];
        }

        $db = getenv('DB_DATABASE') ?: (defined('DB_NAME') ? DB_NAME : '');
        $host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : '127.0.0.1');
        $port = getenv('DB_PORT') ?: '3306';
        $user = getenv('DB_USERNAME') ?: (defined('DB_USER') ? DB_USER : '');
        $pass = getenv('DB_PASSWORD') ?: (defined('DB_PASS') ? DB_PASS : '');
        $binary = defined('PRODUCTION_DB_DUMP_BINARY') ? (string) PRODUCTION_DB_DUMP_BINARY : 'mysqldump';

        if ($db === '' || $user === '') {
            return ['status' => 'NOTOK', 'message' => 'Configuration base de données incomplète.'];
        }

        $filename = $backupDir . '/autosav_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $db) . '_' . date('Ymd_His') . '.sql';
        $defaultsFile = null;
        if ($pass !== '') {
            $defaultsFile = tempnam(sys_get_temp_dir(), 'autosav_mysqldump_');
            if ($defaultsFile === false) {
                return ['status' => 'NOTOK', 'message' => 'Impossible de creer le fichier temporaire mysqldump.'];
            }
            $escapedPassword = str_replace(["\\", '"', "\r", "\n"], ["\\\\", '\\"', '', ''], (string) $pass);
            if (file_put_contents($defaultsFile, "[client]\npassword=\"{$escapedPassword}\"\n") === false) {
                @unlink($defaultsFile);
                return ['status' => 'NOTOK', 'message' => 'Impossible de preparer les identifiants mysqldump.'];
            }
            @chmod($defaultsFile, 0600);
        }

        $command = sprintf(
            '%s %s --single-transaction --quick --lock-tables=false --default-character-set=utf8mb4 -h%s -P%s -u%s %s > %s 2>&1',
            escapeshellcmd($binary),
            $defaultsFile !== null ? '--defaults-extra-file=' . escapeshellarg($defaultsFile) : '',
            escapeshellarg((string) $host),
            escapeshellarg((string) $port),
            escapeshellarg((string) $user),
            escapeshellarg((string) $db),
            escapeshellarg($filename)
        );

        try {
            exec($command, $output, $code);
        } finally {
            if ($defaultsFile !== null) {
                @unlink($defaultsFile);
            }
        }
        if ($code !== 0 || !is_file($filename) || filesize($filename) === 0) {
            @unlink($filename);
            return ['status' => 'NOTOK', 'message' => 'Échec mysqldump.', 'code' => $code, 'output' => $output];
        }

        $final = $filename;
        if (defined('PRODUCTION_BACKUP_COMPRESS') && PRODUCTION_BACKUP_COMPRESS) {
            $data = file_get_contents($filename);
            if ($data !== false) {
                $gz = $filename . '.gz';
                file_put_contents($gz, gzencode($data, 6));
                unlink($filename);
                $final = $gz;
            }
        }

        $this->purgeOldBackups($backupDir, defined('PRODUCTION_BACKUP_RETENTION_DAYS') ? (int) PRODUCTION_BACKUP_RETENTION_DAYS : 30);

        return ['status' => 'SUCCESS', 'file' => $final, 'size_bytes' => filesize($final) ?: 0];
    }

    private function purgeOldBackups(string $backupDir, int $retentionDays): void
    {
        $limit = time() - max(1, $retentionDays) * 86400;
        foreach (glob($backupDir . '/autosav_*.sql*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) !== false && filemtime($file) < $limit) {
                @unlink($file);
            }
        }
    }
}
