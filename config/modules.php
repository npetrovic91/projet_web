<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Accès direct interdit.');

// ============================================================
// AUTOSAV — Activation/désactivation des modules
// ============================================================

define('MODULES_ENABLED', [
    'Auth'           => true,
    'Dashboard'      => true,
    'Users'          => true,
    'Profile'        => true,
    'Companies'      => true,
    'Brands'         => true,
    'Roles'          => true,
    'Jobs'           => true,
    'Functions'      => true,
    'Skills'         => true,
    'Qualifications' => true,
    'Ajax'           => true,
    'Administration' => true,
    'GDPR'           => true,
    'Maintenance'    => true,
    'EventTriggers'  => true,
    'Notifications'  => true,
    'Settings'       => true,
]);
