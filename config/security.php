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
defined('SESSION_LIFETIME_MINUTES')   || define('SESSION_LIFETIME_MINUTES',    30);
defined('SESSION_NAME')               || define('SESSION_NAME',   'AUTOSAV_SESSION');
defined('SESSION_REGENERATE_MINUTES') || define('SESSION_REGENERATE_MINUTES',  10);

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

    // Source par defaut : uniquement self
    "default-src 'self'",

    // Scripts : self + inline necessaire pour AdminLTE/Bootstrap
    // IMPORTANT : 'unsafe-eval' NON inclus (interdit par OWASP)
    "script-src 'self' 'unsafe-inline'",

    // Styles : self + inline pour AdminLTE (classes dynamiques)
    "style-src 'self' 'unsafe-inline'",

    // Images : self + data: (avatars base64, icones inline)
    "img-src 'self' data: blob:",

    // Polices : self + data: (icones FontAwesome en base64)
    "font-src 'self' data:",

    // XHR/Fetch AJAX : uniquement vers self
    "connect-src 'self'",

    // Frames : uniquement self (protection clickjacking redondante)
    "frame-src 'self'",
    "frame-ancestors 'self'",

    // Restriction de l'attribut base href
    "base-uri 'self'",

    // Les formulaires ne peuvent soumettre que vers self
    "form-action 'self'",

    // Aucun plugin (Flash, Silverlight...)
    "object-src 'none'",

    // Aucun worker externe
    "worker-src 'self'",

    // Manifest PWA
    "manifest-src 'self'",
]));
