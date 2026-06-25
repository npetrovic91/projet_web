<?php
declare(strict_types=1);

require_once __DIR__ . '/AuthHelper.php';

// ─────────────────────────────────────────────────────────────
// Helper global d'accès au singleton Database
// Utilisé dans les widgets, les vues et les helpers procéduraux.
// ─────────────────────────────────────────────────────────────
if (!function_exists('db')) {
    function db(): \Nenad\Autosav\Core\Database\Database
    {
        return \Nenad\Autosav\Core\Database\Database::getInstance();
    }
}
