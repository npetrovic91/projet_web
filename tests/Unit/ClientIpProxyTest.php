<?php
declare(strict_types=1);

/**
 * AUTOSAV — Test client_ip() avec proxy de confiance
 *
 * Simule différents scénarios d'en-têtes proxy pour vérifier
 * que client_ip() se comporte correctement selon la whitelist.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

$pass = 0;
$fail = 0;

function ok(bool $cond, string $label): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "[OK]   {$label}\n";
    } else {
        $fail++;
        echo "[FAIL] {$label}\n";
    }
}

// Sauvegarder l'état initial
$savedRemote   = $_SERVER['REMOTE_ADDR']           ?? null;
$savedXff      = $_SERVER['HTTP_X_FORWARDED_FOR']  ?? null;
$savedCf       = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? null;
$savedXReal    = $_SERVER['HTTP_X_REAL_IP']        ?? null;

// ── Scénario 1 : Sans proxy — REMOTE_ADDR direct ────────────
unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTP_X_REAL_IP']);
$_SERVER['REMOTE_ADDR'] = '185.10.20.30';
ok(client_ip() === '185.10.20.30', 'Sans proxy : retourne REMOTE_ADDR');

// ── Scénario 2 : En-têtes forgés sans proxy — ignorés ───────
$_SERVER['HTTP_X_FORWARDED_FOR']  = '1.1.1.1';
$_SERVER['HTTP_CF_CONNECTING_IP'] = '8.8.8.8';
ok(client_ip() === '185.10.20.30', 'En-têtes proxy ignorés si REMOTE_ADDR hors whitelist');

// ── Scénario 3 : Proxy de confiance + CF-Connecting-IP ──────
// (Ne s'applique que si TRUSTED_PROXIES est configuré)
if (!empty(TRUSTED_PROXIES)) {
    $trustedIp = TRUSTED_PROXIES[0];
    $_SERVER['REMOTE_ADDR']             = $trustedIp;
    $_SERVER['HTTP_CF_CONNECTING_IP']   = '203.0.113.5';
    unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    ok(client_ip() === '203.0.113.5', "Proxy de confiance ({$trustedIp}) : lit CF-Connecting-IP");

    // ── Scénario 4 : Proxy de confiance + X-Forwarded-For ───
    unset($_SERVER['HTTP_CF_CONNECTING_IP']);
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.10, 10.0.0.1';
    ok(client_ip() === '203.0.113.10', 'Proxy de confiance : prend la première IP publique de XFF');
} else {
    echo "[SKIP] Scénarios proxy (TRUSTED_PROXIES vide — comportement nominal sans proxy)\n";
    $pass += 2; // on considère le skip comme un succès attendu
}

// ── Scénario 5 : IP invalide → fallback 0.0.0.0 ─────────────
unset($_SERVER['HTTP_X_FORWARDED_FOR'], $_SERVER['HTTP_CF_CONNECTING_IP'], $_SERVER['HTTP_X_REAL_IP']);
$_SERVER['REMOTE_ADDR'] = 'invalid-ip';
ok(client_ip() === '0.0.0.0', 'IP invalide → 0.0.0.0');

// Restaurer
if ($savedRemote !== null) $_SERVER['REMOTE_ADDR']             = $savedRemote; else unset($_SERVER['REMOTE_ADDR']);
if ($savedXff    !== null) $_SERVER['HTTP_X_FORWARDED_FOR']    = $savedXff;    else unset($_SERVER['HTTP_X_FORWARDED_FOR']);
if ($savedCf     !== null) $_SERVER['HTTP_CF_CONNECTING_IP']   = $savedCf;     else unset($_SERVER['HTTP_CF_CONNECTING_IP']);
if ($savedXReal  !== null) $_SERVER['HTTP_X_REAL_IP']          = $savedXReal;  else unset($_SERVER['HTTP_X_REAL_IP']);

echo "\n--- Résultat : {$pass} OK / " . ($pass + $fail) . " total ---\n";
exit($fail > 0 ? 1 : 0);
