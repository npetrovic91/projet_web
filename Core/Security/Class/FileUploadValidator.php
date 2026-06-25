<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Security\Class;

/**
 * Validation serveur stricte des fichiers téléversés.
 */
final class FileUploadValidator
{
    public static function validate(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Fichier invalide ou non transmis.');
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp) || !is_readable($tmp)) {
            throw new \RuntimeException('Upload non valide.');
        }

        $size = (int)($file['size'] ?? filesize($tmp));
        $max = defined('FILES_MAX_UPLOAD_BYTES') ? (int) FILES_MAX_UPLOAD_BYTES : 10 * 1024 * 1024;
        if ($size < 1) {
            throw new \RuntimeException('Le fichier est vide.');
        }
        if ($size > $max) {
            throw new \RuntimeException('Le fichier dépasse la taille maximale autorisée.');
        }

        $original = trim((string)($file['name'] ?? 'fichier')) ?: 'fichier';
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $forbidden = defined('FILES_FORBIDDEN_EXTENSIONS') ? array_map('strtolower', (array) FILES_FORBIDDEN_EXTENSIONS) : [];
        if ($extension !== '' && in_array($extension, $forbidden, true)) {
            throw new \RuntimeException('Extension de fichier interdite pour des raisons de sécurité.');
        }

        $mime = self::detectMime($tmp);
        $allowed = defined('FILES_ALLOWED_MIME_TYPES') ? (array) FILES_ALLOWED_MIME_TYPES : [];
        if ($allowed !== [] && !in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Type MIME non autorisé : ' . $mime);
        }

        self::rejectExecutableContent($tmp, $mime);

        return [
            'tmp' => $tmp,
            'original' => $original,
            'mime' => $mime,
            'size' => $size,
            'extension' => $extension,
        ];
    }

    private static function detectMime(string $tmp): string
    {
        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $tmp);
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    $mime = $detected;
                }
            }
        }
        return $mime;
    }

    private static function rejectExecutableContent(string $tmp, string $mime): void
    {
        $handle = fopen($tmp, 'rb');
        if (!$handle) {
            throw new \RuntimeException('Impossible de contrôler le fichier téléversé.');
        }
        $head = (string) fread($handle, 4096);
        fclose($handle);

        if (preg_match('/<\?(php|=)|<script\b|<svg\b|#!\s*\/bin\//i', $head)) {
            throw new \RuntimeException('Contenu actif interdit dans les fichiers téléversés.');
        }

        if (str_starts_with($mime, 'image/') && preg_match('/<\?(php|=)|<script\b/i', $head)) {
            throw new \RuntimeException('Image refusée : contenu actif détecté.');
        }
    }
}
