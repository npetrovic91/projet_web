<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Services\Production;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class LogRotationService
{
    /**
     * @return array<string,mixed>
     */
    public function rotate(string $path, int $retentionDays): array
    {
        if (!is_dir($path)) {
            return ['status' => 'NOTOK', 'message' => 'Répertoire de journaux introuvable.', 'path' => $path];
        }

        $limit = time() - max(1, $retentionDays) * 86400;
        $archived = 0;
        $deleted = 0;
        $errors = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }
            $filename = $file->getPathname();
            if (!preg_match('/\.(log|txt)$/i', $filename)) {
                continue;
            }
            if ($file->getMTime() >= $limit) {
                continue;
            }
            $gz = $filename . '.' . date('Ymd', $file->getMTime()) . '.gz';
            $data = @file_get_contents($filename);
            if ($data === false || @file_put_contents($gz, gzencode($data, 6)) === false) {
                $errors[] = $filename;
                continue;
            }
            $archived++;
            if (@unlink($filename)) {
                $deleted++;
            }
        }

        return [
            'status' => $errors === [] ? 'SUCCESS' : 'NOTOK',
            'path' => $path,
            'retention_days' => $retentionDays,
            'archived' => $archived,
            'deleted' => $deleted,
            'errors' => $errors,
        ];
    }
}
