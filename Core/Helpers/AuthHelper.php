<?php
declare(strict_types=1);

use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Core\Security\Class\CsrfProtection;

if (!function_exists('e')) {
    function e(mixed $value, bool $doubleEncode = true): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return CsrfProtection::getToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $name = defined('CSRF_TOKEN_NAME') ? (string) CSRF_TOKEN_NAME : '_csrf_token';
        $token = csrf_token();
        return '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" value="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_meta')) {
    function csrf_meta(): string
    {
        $name = defined('CSRF_HEADER_NAME') ? (string) CSRF_HEADER_NAME : 'X-CSRF-Token';
        return '<meta name="csrf-header" content="' . htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
            . '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
    }
}

if (!function_exists('is_authenticated')) {
    function is_authenticated(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        $id = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
        if (!$id) {
            return false;
        }
        if (isset($_SESSION['_last_activity']) && defined('SESSION_LIFETIME_MINUTES')) {
            if ((time() - (int) $_SESSION['_last_activity']) > ((int) SESSION_LIFETIME_MINUTES * 60)) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('has_role')) {
    function has_role(string|array $role): bool
    {
        $roles = array_values(array_unique(array_map(
            static fn($value): string => mb_strtolower(trim((string) $value)),
            (array) ($_SESSION['user']['roles'] ?? $_SESSION['user_roles'] ?? [])
        )));

        $required = array_values(array_unique(array_map(
            static fn($value): string => mb_strtolower(trim((string) $value)),
            (array) $role
        )));

        $aliases = [
            'super_admin' => ['super_admin', 'super_administrateur'],
            'super_administrateur' => ['super_admin', 'super_administrateur'],
        ];

        foreach ($required as $candidate) {
            $wanted = $aliases[$candidate] ?? [$candidate];
            foreach ($wanted as $alias) {
                if (in_array($alias, $roles, true)) {
                    return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('has_permission')) {
    function has_permission(string|array $permission): bool
    {
        $permissions = array_values(array_unique(array_map(
            static fn($value): string => trim((string) $value),
            (array) ($_SESSION['user']['permissions'] ?? $_SESSION['user_permissions'] ?? $_SESSION['permissions'] ?? [])
        )));
        if (in_array('*', $permissions, true) || has_role('super_administrateur')) {
            return true;
        }

        $aliases = [
            'users.read' => ['utilisateur.creer', 'utilisateur.modifier', 'utilisateur.bloquer'],
            'users.manage' => ['utilisateur.creer', 'utilisateur.modifier', 'utilisateur.bloquer'],
            'admin.users' => ['utilisateur.creer', 'utilisateur.modifier', 'utilisateur.bloquer'],
            'roles.read' => ['role.gerer'],
            'roles.manage' => ['role.gerer'],
            'admin.roles' => ['role.gerer'],
            'functions.read' => ['fonction.gerer', 'role.gerer'],
            'functions.manage' => ['fonction.gerer', 'role.gerer'],
            'admin.functions' => ['fonction.gerer', 'role.gerer'],
            'skills.read' => ['competence.gerer', 'role.gerer'],
            'skills.manage' => ['competence.gerer', 'role.gerer'],
            'admin.skills' => ['competence.gerer', 'role.gerer'],
            'qualifications.read' => ['certification.gerer', 'role.gerer'],
            'qualifications.manage' => ['certification.gerer', 'role.gerer'],
            'admin.qualifications' => ['certification.gerer', 'role.gerer'],
            'permissions.read' => ['permission.gerer'],
            'permissions.manage' => ['permission.gerer'],
            'admin.permissions' => ['permission.gerer'],
            'admin.maintenance' => ['maintenance.gerer', 'maintenance.executer'],
            'maintenance.read' => ['maintenance.gerer', 'maintenance.executer', 'journal.consulter'],
            'maintenance.manage' => ['maintenance.gerer'],
            'organisation.read' => ['societe.modifier'],
            'organisation.manage' => ['societe.modifier'],
            'security.read' => ['audit.consulter', 'journal.consulter'],
            'security.manage' => ['audit.consulter', 'journal.consulter', 'maintenance.gerer'],
            'admin.security' => ['audit.consulter', 'journal.consulter'],
            'notifications.read' => ['notification.consulter'],
            'notifications.manage' => ['notification.gerer'],
            'eventtriggers.read' => ['evenement.gerer', 'notification.gerer'],
            'eventtriggers.manage' => ['evenement.gerer', 'notification.gerer'],
            'settings.read' => ['maintenance.gerer', 'journal.consulter', 'audit.consulter'],
            'settings.manage' => ['maintenance.gerer'],
            'admin.settings' => ['maintenance.gerer'],
            'abonnements.read' => ['abonnement.consulter', 'abonnement.gerer'],
            'abonnements.manage' => ['abonnement.gerer'],
            'admin.abonnements' => ['abonnement.gerer'],
            'verrous.read' => ['verrou.consulter', 'verrou.gerer'],
            'verrous.manage' => ['verrou.gerer'],
            'admin.verrous' => ['verrou.gerer'],
            'validation.read' => ['validation.consulter', 'validation.gerer'],
            'validation.manage' => ['validation.gerer'],
            'admin.validation' => ['validation.gerer'],
            'standards.read' => ['standard.consulter', 'standard.gerer'],
            'standards.manage' => ['standard.gerer'],
            'admin.standards' => ['standard.gerer'],
            'horaires.read' => ['horaire.consulter', 'horaire.gerer'],
            'horaires.manage' => ['horaire.gerer'],
            'admin.horaires' => ['horaire.gerer'],
            'jobs.read' => ['fonction.gerer'],
            'jobs.manage' => ['fonction.gerer'],
            'admin.jobs' => ['fonction.gerer'],
            'invitation.read' => ['utilisateur.creer', 'utilisateur.modifier'],
            'invitation.manage' => ['utilisateur.creer', 'utilisateur.modifier'],
            'admin.invitations' => ['utilisateur.creer', 'utilisateur.modifier'],
            'portail.read' => ['module.acceder'],
            'portail.context.update' => ['module.acceder'],
        ];

        $roleAliases = [
            'functions.read' => ['pdg', 'chef_de_service', 'chef_service', 'directeur_service', 'responsable_service'],
            'functions.manage' => ['pdg', 'chef_de_service', 'chef_service', 'directeur_service', 'responsable_service'],
            'admin.functions' => ['pdg', 'chef_de_service', 'chef_service', 'directeur_service', 'responsable_service'],
            'organisation.read' => ['pdg', 'chef_de_service', 'chef_service', 'directeur_service', 'responsable_service', 'chef_equipe'],
            'organisation.manage' => ['pdg', 'chef_de_service', 'chef_service', 'directeur_service', 'responsable_service'],
        ];

        foreach ((array) $permission as $candidate) {
            $candidate = trim((string) $candidate);
            $wanted = array_values(array_unique(array_merge([$candidate], $aliases[$candidate] ?? [])));
            foreach ($wanted as $code) {
                if (in_array($code, $permissions, true)) {
                    return true;
                }
            }
            if (isset($roleAliases[$candidate]) && has_role($roleAliases[$candidate])) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('session')) {
    function session(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $_SESSION;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        if (defined('APP_URL')) {
            return rtrim((string) APP_URL, '/') . $path;
        }
        return $path;
    }
}

if (!function_exists('ip_matches_cidr')) {
    function ip_matches_cidr(string $ip, string $cidr): bool
    {
        $ip = trim($ip);
        $cidr = trim($cidr);
        if ($ip === '' || $cidr === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        if (!str_contains($cidr, '/')) {
            return hash_equals($cidr, $ip);
        }
        [$network, $prefix] = explode('/', $cidr, 2);
        if (!filter_var($network, FILTER_VALIDATE_IP) || !is_numeric($prefix)) {
            return false;
        }

        $ipBin = inet_pton($ip);
        $networkBin = inet_pton($network);
        if ($ipBin === false || $networkBin === false || strlen($ipBin) !== strlen($networkBin)) {
            return false;
        }

        $bits = strlen($ipBin) * 8;
        $prefix = max(0, min($bits, (int) $prefix));
        $fullBytes = intdiv($prefix, 8);
        $remainingBits = $prefix % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($networkBin, 0, $fullBytes)) {
            return false;
        }
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        return (ord($ipBin[$fullBytes]) & $mask) === (ord($networkBin[$fullBytes]) & $mask);
    }
}

if (!function_exists('remote_addr_is_trusted_proxy')) {
    function remote_addr_is_trusted_proxy(?string $remoteAddr = null): bool
    {
        $remoteAddr = trim((string) ($remoteAddr ?? ($_SERVER['REMOTE_ADDR'] ?? '')));
        if ($remoteAddr === '' || !filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            return false;
        }
        $trusted = defined('TRUSTED_PROXIES') ? (array) TRUSTED_PROXIES : [];
        foreach ($trusted as $proxy) {
            if (ip_matches_cidr($remoteAddr, (string) $proxy)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('client_ip')) {
    function client_ip(): string
    {
        $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $fallback = filter_var($remoteAddr, FILTER_VALIDATE_IP) ? $remoteAddr : '0.0.0.0';

        if (!remote_addr_is_trusted_proxy($remoteAddr)) {
            return $fallback;
        }

        $headers = defined('TRUSTED_PROXY_HEADERS')
            ? (array) TRUSTED_PROXY_HEADERS
            : ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'];

        foreach ($headers as $header) {
            $value = $_SERVER[(string) $header] ?? null;
            if (!$value) {
                continue;
            }
            foreach (explode(',', (string) $value) as $candidate) {
                $ip = trim($candidate);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $fallback;
    }
}


if (!function_exists('logger')) {
    function logger(string $channel = 'application'): object
    {
        try {
            return LogManager::getInstance()->channel($channel);
        } catch (Throwable) {
            return new class {
                public function debug(string $message, array $context = []): void { error_log('[debug] ' . $message . ' ' . json_encode($context)); }
                public function info(string $message, array $context = []): void { error_log('[info] ' . $message . ' ' . json_encode($context)); }
                public function warning(string $message, array $context = []): void { error_log('[warning] ' . $message . ' ' . json_encode($context)); }
                public function error(string $message, array $context = []): void { error_log('[error] ' . $message . ' ' . json_encode($context)); }
                public function critical(string $message, array $context = []): void { error_log('[critical] ' . $message . ' ' . json_encode($context)); }
            };
        }
    }
}

if (!function_exists('generate_uuid')) {
    function generate_uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('csp_nonce')) {
    function csp_nonce(): string
    {
        return \Nenad\Autosav\Core\Security\Class\CspNonce::value();
    }
}

if (!function_exists('csp_nonce_attr')) {
    function csp_nonce_attr(): string
    {
        return \Nenad\Autosav\Core\Security\Class\CspNonce::attribute();
    }
}

if (!function_exists('send_security_headers')) {
    function send_security_headers(): void
    {
        \Nenad\Autosav\Core\Security\Class\CspNonce::sendHeaders();
    }
}
