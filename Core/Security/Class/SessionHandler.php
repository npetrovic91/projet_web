<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Security\Class;

/**
 * AUTOSAV — Gestionnaire de session sécurisée
 * Fichier : src/Core/Security/Class/SessionHandler.php
 */
class SessionHandler
{
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        // Configurer avant démarrage
        ini_set('session.use_strict_mode',    '1');
        ini_set('session.use_only_cookies',   '1');
        ini_set('session.use_trans_sid',      '0');
        ini_set('session.gc_maxlifetime',     (string)SESSION_GC_MAXLIFETIME);
        ini_set('session.gc_probability',     (string)SESSION_GC_PROBABILITY);
        ini_set('session.gc_divisor',         (string)SESSION_GC_DIVISOR);
        ini_set('session.save_handler',       'files');
        ini_set('session.save_path',          SESSION_SAVE_PATH);

        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0, // Cookie de session (fermeture navigateur)
            'path'     => '/',
            'domain'   => '',
            'secure'   => SESSION_COOKIE_SECURE,
            'httponly' => SESSION_COOKIE_HTTPONLY,
            'samesite' => SESSION_COOKIE_SAMESITE,
        ]);

        session_start();

        // Vérification d'intégrité de session (user-agent binding)
        //
        // CORRECTIF HIGH-4 (audit sécurité 2026-06-26) : la détection d'un
        // changement de user-agent (signe possible de vol de cookie de
        // session) détruisait silencieusement la session sans laisser
        // aucune trace — un détournement de session passait inaperçu côté
        // surveillance. Le binding lui-même reste une défense faible (un
        // attaquant qui vole un cookie peut aussi copier le user-agent),
        // mais doit au moins être journalisé pour donner une visibilité
        // forensique. sha256 remplace md5 (obsolète, sans impact réel ici
        // puisqu'il ne s'agit pas d'un hash cryptographique de secret, mais
        // évite un signal faux-positif dans les futurs audits/scanners).
        if (isset($_SESSION['_ua'])) {
            $currentUa = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
            if (!hash_equals((string) $_SESSION['_ua'], $currentUa)) {
                $userId = $_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? null;
                if (function_exists('logger')) {
                    logger('security')->warning('Session détruite : changement de user-agent détecté (vol de session possible).', [
                        'user_id' => $userId,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    ]);
                }
                self::destroy();
                session_start();
            }
        } else {
            $_SESSION['_ua'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
        }

        // Régénération périodique de l'ID de session
        if (!isset($_SESSION['_last_regen'])) {
            $_SESSION['_last_regen'] = time();
        } elseif (time() - $_SESSION['_last_regen'] > SESSION_REGENERATE_MINUTES * 60) {
            session_regenerate_id(true);
            $_SESSION['_last_regen'] = time();
        }

        // Timeout inactivité
        if (isset($_SESSION['_last_activity'])) {
            $idle = time() - $_SESSION['_last_activity'];
            if ($idle > SESSION_LIFETIME_MINUTES * 60) {
                self::destroy();
                session_start();
            }
        }
        $_SESSION['_last_activity'] = time();
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_last_regen'] = time();
    }
}
