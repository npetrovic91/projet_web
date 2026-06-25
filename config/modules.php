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
    'Companies'      => true, // Compatibilité : routes /companies redirigées vers le module Society
    'Society'        => true, // Module de référence aligné sur sav_societes
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
    'Emails'         => true,
    'BulkMail'       => true,
    'Settings'       => true,
    'Files'          => true,
    'LegalDocuments' => true,
    'Connectors'     => true,
    'Standards'      => true,
    'Validation'     => true,
    'Notes'          => true,
    'Horaires'       => true,
    'Verrous'        => true,
    'Referentiels'   => true,
    'Organisation'   => true,
    'Contacts'       => true,
    'Relations'      => true,
    'Abonnements'    => true,
    'Invitations'    => true,
    'Menus'          => true,
    'Portail'        => true,
    'SuperAdmin'     => true,
    'AutoTests'      => true,
]);
