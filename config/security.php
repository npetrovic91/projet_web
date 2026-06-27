<?php
declare(strict_types=1);

/**
 * AUTOSAV — Securite applicative
 * Fichier : config/security.php
 *
 * Ce fichier definit TOUTES les constantes de securite :
 *   - Brute-force / rate-limiting (IP + email)
 *   - Regles de mot de passe (ISO 27001 / OWASP)
 *   - Tokens (durees d'expiration)
 *   - Session
 *   - CSRF
 *   - En-tetes HTTP de securite (SECURITY_HEADERS + CSP_POLICY)
 *
 * ATTENTION : Ce fichier ne doit JAMAIS etre accessible directement
 * depuis le web. Protege par .htaccess au niveau racine.
 *
 * HISTORIQUE CORRECTIONS :
 *   - Encodage BOM + CRLF corrige (UTF-8 sans BOM, LF uniquement)
 *   - Constante AUTOSAV_ROOT guard ajoutee
 *   - SECURITY_HEADERS et CSP_POLICY ajoutes (requis par bootstrap.php)
 */

defined('AUTOSAV_ROOT') or die('Acces direct interdit.');

// ============================================================
// PROXYS DE CONFIANCE
// Les en-tetes HTTP_X_FORWARDED_FOR / HTTP_X_REAL_IP / CF_CONNECTING_IP
// ne sont acceptes que si REMOTE_ADDR appartient a cette liste.
// Laisser vide sans reverse-proxy connu.
// Configurable via .env : TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8
// ============================================================
if (!defined('TRUSTED_PROXIES')) {
    $_autosavTrustedProxies = (string) (getenv('TRUSTED_PROXIES') ?: '');
    $_autosavTrustedProxies = $_autosavTrustedProxies !== ''
        ? array_values(array_filter(array_map('trim', explode(',', $_autosavTrustedProxies))))
        : [];
    define('TRUSTED_PROXIES', $_autosavTrustedProxies);
    unset($_autosavTrustedProxies);
}

if (!defined('TRUSTED_PROXY_HEADERS')) {
    $_autosavProxyHeaders = (string) (getenv('TRUSTED_PROXY_HEADERS') ?: '');
    $_autosavProxyHeaders = $_autosavProxyHeaders !== ''
        ? array_values(array_filter(array_map('trim', explode(',', $_autosavProxyHeaders))))
        : ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'];
    define('TRUSTED_PROXY_HEADERS', $_autosavProxyHeaders);
    unset($_autosavProxyHeaders);
}


// ============================================================
// RATE LIMITING AJAX / API INTERNE
// ============================================================
defined('AJAX_RATE_LIMIT_ENABLED') || define('AJAX_RATE_LIMIT_ENABLED', true);
defined('AJAX_RATE_LIMIT_MAX_REQUESTS') || define('AJAX_RATE_LIMIT_MAX_REQUESTS', 60);
defined('AJAX_RATE_LIMIT_WINDOW_SECONDS') || define('AJAX_RATE_LIMIT_WINDOW_SECONDS', 60);
defined('AJAX_RATE_LIMIT_BUCKET_DIR') || define('AJAX_RATE_LIMIT_BUCKET_DIR', defined('CACHE_PATH') ? CACHE_PATH . '/rate_limit' : sys_get_temp_dir() . '/autosav_rate_limit');

// ============================================================
// CSP AVEC NONCES
// Les directives script-src/style-src utilisent un nonce par requete.
// L'ancien unsafe-inline reste desactive par defaut.
// ============================================================
defined('CSP_NONCE_ENABLED') || define('CSP_NONCE_ENABLED', true);
defined('CSP_ALLOW_UNSAFE_INLINE') || define('CSP_ALLOW_UNSAFE_INLINE', false);

// ============================================================
// UPLOADS — Validation serveur stricte
// ============================================================
defined('FILES_MAX_UPLOAD_BYTES') || define('FILES_MAX_UPLOAD_BYTES', 10 * 1024 * 1024);
defined('FILES_ALLOWED_MIME_TYPES') || define('FILES_ALLOWED_MIME_TYPES', [
    'application/pdf',
    'image/jpeg',
    'image/png',
    'image/webp',
    'image/gif',
    'text/plain',
    'text/csv',
    'application/json',
    'application/xml',
    'text/xml',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/msword',
    'application/vnd.ms-excel',
    'application/zip',
]);
defined('FILES_FORBIDDEN_EXTENSIONS') || define('FILES_FORBIDDEN_EXTENSIONS', [
    'php','phtml','phar','cgi','pl','py','sh','bash','exe','dll','bat','cmd','com','js','mjs','vbs','jar','jsp','asp','aspx','html','htm','svg'
]);

// ============================================================
// AUTHENTIFICATION — Blocages IP
// Protection brute-force au niveau reseau (ISO 27001 A.9.4.2)
// ============================================================
defined('AUTH_MAX_IP_ATTEMPTS')           || define('AUTH_MAX_IP_ATTEMPTS',           5);
defined('AUTH_IP_WINDOW_MINUTES')         || define('AUTH_IP_WINDOW_MINUTES',         15);
defined('AUTH_IP_BLOCK_DURATION_MINUTES') || define('AUTH_IP_BLOCK_DURATION_MINUTES', 30);

// ============================================================
// AUTHENTIFICATION — Blocages Email
// Limite par compte pour eviter l'enumeration (OWASP OAT-007)
// ============================================================
defined('AUTH_MAX_EMAIL_ATTEMPTS')           || define('AUTH_MAX_EMAIL_ATTEMPTS',           10);
defined('AUTH_EMAIL_WINDOW_MINUTES')         || define('AUTH_EMAIL_WINDOW_MINUTES',         60);
defined('AUTH_EMAIL_BLOCK_DURATION_MINUTES') || define('AUTH_EMAIL_BLOCK_DURATION_MINUTES', 120);

// ============================================================
// AUTHENTIFICATION — Delai artificiel sur echec (anti brute-force)
// Rend les attaques sequentielles non-rentables (ISO 27001 A.9.4)
// ============================================================
defined('AUTH_FAILURE_DELAY_SECONDS') || define('AUTH_FAILURE_DELAY_SECONDS', 2);

// ============================================================
// MOT DE PASSE — Regles de complexite
// Conformite RGPD + ISO 27001 A.9.3 + ANSSI R30
// ============================================================
defined('PASSWORD_MIN_LENGTH')        || define('PASSWORD_MIN_LENGTH',        10);
defined('PASSWORD_MAX_LENGTH')        || define('PASSWORD_MAX_LENGTH',        128);
defined('PASSWORD_REQUIRE_UPPERCASE') || define('PASSWORD_REQUIRE_UPPERCASE', true);
defined('PASSWORD_REQUIRE_LOWERCASE') || define('PASSWORD_REQUIRE_LOWERCASE', true);
defined('PASSWORD_REQUIRE_NUMBER')    || define('PASSWORD_REQUIRE_NUMBER',    true);
defined('PASSWORD_REQUIRE_SPECIAL')   || define('PASSWORD_REQUIRE_SPECIAL',   true);
defined('PASSWORD_HISTORY_COUNT')     || define('PASSWORD_HISTORY_COUNT',     5);

// Algorithme de hachage : Argon2id recommande par OWASP (2024)
// Prefere a bcrypt pour la resistance aux attaques GPU/ASIC
defined('HASH_ALGO')         || define('HASH_ALGO', PASSWORD_ARGON2ID);
defined('HASH_ALGO_OPTIONS') || define('HASH_ALGO_OPTIONS', [
    'memory_cost' => 65536, // 64 Mo RAM
    'time_cost'   => 4,     // 4 iterations
    'threads'     => 1,     // Compatible shared hosting (Hostinger)
]);

// ============================================================
// TOKENS — Durees d'expiration
// ============================================================
defined('EMAIL_TOKEN_EXPIRY_HOURS') || define('EMAIL_TOKEN_EXPIRY_HOURS', 72);
defined('RESET_TOKEN_EXPIRY_HOURS') || define('RESET_TOKEN_EXPIRY_HOURS',  2);
defined('TOKEN_BYTE_LENGTH')        || define('TOKEN_BYTE_LENGTH',         32);

// ============================================================
// SESSION
// ============================================================
defined('SESSION_LIFETIME_MINUTES')   || define('SESSION_LIFETIME_MINUTES', (int) (getenv('SESSION_LIFETIME_MINUTES') ?: 30));
defined('SESSION_NAME')               || define('SESSION_NAME', getenv('SESSION_NAME') ?: 'AUTOSAV_SESSION');
defined('SESSION_REGENERATE_MINUTES') || define('SESSION_REGENERATE_MINUTES', (int) (getenv('SESSION_REGENERATE_MINUTES') ?: 10));

// ============================================================
// CSRF
// ============================================================
defined('CSRF_TOKEN_LENGTH') || define('CSRF_TOKEN_LENGTH', 32);
defined('CSRF_SESSION_KEY')  || define('CSRF_SESSION_KEY',  '_csrf_token');
defined('CSRF_FORM_FIELD')   || define('CSRF_FORM_FIELD',   '_csrf_token');
defined('CSRF_HEADER_NAME')  || define('CSRF_HEADER_NAME',  'X-CSRF-Token');
defined('CSRF_TOKEN_NAME')   || define('CSRF_TOKEN_NAME',   CSRF_FORM_FIELD);
defined('CSRF_TOKEN_EXPIRY') || define('CSRF_TOKEN_EXPIRY', SESSION_LIFETIME_MINUTES * 60);

// ============================================================
// EN-TETES HTTP DE SECURITE
// Requis par bootstrap.php : foreach (SECURITY_HEADERS as $h => $v)
//
// Conformite :
//   - OWASP Secure Headers Project
//   - ISO 27001/27002 A.14.1.2 (securisation des services publics)
//   - RGPD Art. 25 (privacy by design)
// ============================================================
defined('SECURITY_HEADERS') || define('SECURITY_HEADERS', [

    // Empeche le clickjacking (OWASP A05:2021)
    'X-Frame-Options'           => 'SAMEORIGIN',

    // Empeche le MIME-sniffing (OWASP A05:2021)
    'X-Content-Type-Options'    => 'nosniff',

    // Protection XSS legacy pour anciens navigateurs
    'X-XSS-Protection'          => '1; mode=block',

    // Controle les informations de referant envoyees
    'Referrer-Policy'           => 'strict-origin-when-cross-origin',

    // Desactive les API sensibles non utilisees
    'Permissions-Policy'        => 'geolocation=(), microphone=(), camera=(), payment=()',

    // HSTS : force HTTPS pendant 1 an (production uniquement)
    // Bootstrap.php applique cet en-tete seulement en production.
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',

    // Empeche les navigateurs de deviner le type MIME
    'X-Permitted-Cross-Domain-Policies' => 'none',

    // Masque la technologie serveur
    'X-Powered-By'              => '',
]);

// ============================================================
// CONTENT SECURITY POLICY
// Requis par bootstrap.php : header("Content-Security-Policy: " . CSP_POLICY)
//
// Politique stricte adaptee a AdminLTE3/4 + Bootstrap + SweetAlert2
// (ressources servies en local depuis /assets/vendor/)
// ============================================================
defined('CSP_POLICY') || define('CSP_POLICY', implode('; ', [

    "default-src 'self'",
    "script-src 'self'",
    "style-src 'self'",
    "style-src-elem 'self'",
    "style-src-attr 'unsafe-inline'", // compatibilite temporaire avec les attributs style existants
    "img-src 'self' data: blob:",
    "font-src 'self' data:",
    "connect-src 'self'",
    "frame-src 'self'",
    "frame-ancestors 'self'",
    "base-uri 'self'",
    "form-action 'self'",
    "object-src 'none'",
    "worker-src 'self'",
    "manifest-src 'self'",
    // CORRECTIF LOW-3 (audit DevOps 2026-06-26) : sans report-uri, aucune
    // tentative XSS bloquée par cette CSP n'était jamais visible côté
    // équipe — voir public/csp-report.php.
    "report-uri /csp-report.php",
]));

// ============================================================
// CHIFFREMENT APPLICATIF — production
// ============================================================
// ENCRYPTION_KEY doit venir du .env en production.
// Formats acceptés :
// - base64:<clé binaire de 32 octets ou plus>
// - chaîne brute de 32 caractères ou plus
// - APP_KEY est accepté comme alias de compatibilité.
if (!defined('ENCRYPTION_CIPHER')) {
    define('ENCRYPTION_CIPHER', getenv('ENCRYPTION_CIPHER') ?: 'aes-256-cbc');
}

if (!defined('ENCRYPTION_KEY')) {
    $_autosavEncryptionRaw = (string) (getenv('ENCRYPTION_KEY') ?: getenv('APP_KEY') ?: '');
    $_autosavIsProduction = defined('APP_ENV') && APP_ENV === 'production';
    $_autosavIsPreflight = PHP_SAPI === 'cli' && getenv('AUTOSAV_PREFLIGHT') === '1';
    $_autosavPlaceholderPattern = '/^(base64:)?(generer_|changer_|change_|CHANGE_ME|autosav-change-me|autosav-dev-only-key)/i';
    $_autosavInvalidKey = $_autosavEncryptionRaw === '' || preg_match($_autosavPlaceholderPattern, $_autosavEncryptionRaw) === 1;

    if ($_autosavInvalidKey && $_autosavIsProduction && !$_autosavIsPreflight) {
        if (PHP_SAPI !== 'cli') {
            http_response_code(500);
        }
        error_log('[AUTOSAV][CRITICAL] ENCRYPTION_KEY absent ou placeholder en production. Démarrage refusé.');
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "[AUTOSAV] Configuration de securite incomplete. Contactez l'administrateur.\n");
            exit(1);
        }
        die('[AUTOSAV] Configuration de sécurité incomplète. Contactez l\'administrateur.');
    }

    if (str_starts_with($_autosavEncryptionRaw, 'base64:')) {
        $_autosavDecodedKey = base64_decode(substr($_autosavEncryptionRaw, 7), true);
        $_autosavEncryptionRaw = is_string($_autosavDecodedKey) ? $_autosavDecodedKey : '';
        unset($_autosavDecodedKey);
    }

    if ($_autosavEncryptionRaw === '') {
        // Développement uniquement : clé déterministe de secours.
        $_autosavEncryptionRaw = hash('sha256', 'autosav-dev-only-key', true);
    } elseif (strlen($_autosavEncryptionRaw) < 32) {
        if ($_autosavIsProduction && !$_autosavIsPreflight) {
            if (PHP_SAPI !== 'cli') {
                http_response_code(500);
            }
            error_log('[AUTOSAV][CRITICAL] ENCRYPTION_KEY trop courte en production. 32 octets minimum requis.');
            if (PHP_SAPI === 'cli') {
                fwrite(STDERR, "[AUTOSAV] Configuration de securite incomplete. Contactez l'administrateur.\n");
                exit(1);
            }
            die('[AUTOSAV] Configuration de sécurité incomplète. Contactez l\'administrateur.');
        }
        // Développement : dérive une clé 32 octets depuis la valeur fournie.
        $_autosavEncryptionRaw = hash('sha256', $_autosavEncryptionRaw, true);
    }

    define('ENCRYPTION_KEY', $_autosavEncryptionRaw);
    unset($_autosavEncryptionRaw, $_autosavIsProduction, $_autosavIsPreflight, $_autosavPlaceholderPattern, $_autosavInvalidKey);
}
