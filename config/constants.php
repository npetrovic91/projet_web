<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Accès direct interdit.');

// ============================================================
// AUTOSAV — Constantes métier globales
// ============================================================

// Rôles système
define('ROLE_SUPERADMIN',      'SUPERADMIN');
define('ROLE_CONSTRUCTEUR',    'CONSTRUCTEUR');
define('ROLE_IMPORTATEUR',     'IMPORTATEUR');
define('ROLE_CONCESSIONNAIRE', 'CONCESSIONNAIRE');
define('ROLE_REPARATEUR',      'REPARATEUR');
define('ROLE_MANAGER',         'MANAGER');
define('ROLE_USER',            'USER');

// Types d'entreprises
define('COMPANY_TYPE_CONSTRUCTEUR',    'CONSTRUCTEUR');
define('COMPANY_TYPE_IMPORTATEUR',     'IMPORTATEUR');
define('COMPANY_TYPE_CONCESSIONNAIRE', 'CONCESSIONNAIRE');
define('COMPANY_TYPE_REPARATEUR',      'REPARATEUR');
define('COMPANY_TYPE_INDEPENDANT',     'INDEPENDANT');
define('COMPANY_TYPE_HOLDING',         'HOLDING');

// Statuts de demandes RGPD
define('GDPR_STATUS_PENDING',     'pending');
define('GDPR_STATUS_IN_PROGRESS', 'in_progress');
define('GDPR_STATUS_COMPLETED',   'completed');
define('GDPR_STATUS_REJECTED',    'rejected');
define('GDPR_RESPONSE_DAYS',       30);

// Types de tokens
define('TOKEN_TYPE_EMAIL',    'email_verify');
define('TOKEN_TYPE_PASSWORD', 'password_reset');

// Canaux de notification
define('NOTIF_CHANNEL_EMAIL', 'email');
define('NOTIF_CHANNEL_SMS',   'sms');

// Niveaux de compétences
define('SKILL_LEVEL_MIN', 1);
define('SKILL_LEVEL_MAX', 5);
