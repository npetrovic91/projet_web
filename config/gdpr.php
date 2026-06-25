<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Accès direct interdit.');

// ============================================================
// AUTOSAV — Configuration RGPD
// ============================================================

// Durée légale de réponse aux demandes (jours)
define('GDPR_MAX_RESPONSE_DAYS', 30);

// Format export données
define('GDPR_EXPORT_FORMAT', 'json'); // 'json' ou 'pdf'

// Durées de conservation (en jours)
define('GDPR_RETENTION_LOGIN_ATTEMPTS',    365);  // 1 an
define('GDPR_RETENTION_PERSONAL_DATA',    1095);  // 3 ans après fin relation
define('GDPR_RETENTION_PROFESSIONAL',     1825);  // 5 ans
define('GDPR_RETENTION_AUDIT_LOG',         365);  // 1 an
define('GDPR_RETENTION_CGU_ACCEPTANCE',   1825);  // 5 ans
