<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Nenad\Autosav\Core\Security\Class\CsrfProtection;

$root = dirname(__DIR__, 2);

foreach ([
    'AJAX_HEADER_VALUE',
    'AJAX_HEADER_NAME',
    'DASHBOARD_WIDGETS_PATH',
    'PAGINATION_DEFAULT',
    'MAINTENANCE_VIEW',
    'TERMS_CURRENT_VERSION',
    'GDPR_MAX_RESPONSE_DAYS',
    'MODULES_ENABLED',
] as $constant) {
    assert(defined($constant), "Constante manquante apres bootstrap : {$constant}");
}

$_SESSION['csrf_token'] = 'audit-csrf-token';
$_SESSION['csrf_token_expiry'] = time() + 60;
$_POST[CSRF_TOKEN_NAME] = 'audit-csrf-token';

assert(CsrfProtection::validate());
$rotatedToken = $_SESSION['csrf_token'];
assert($rotatedToken !== 'audit-csrf-token');
assert(CsrfProtection::wasValidatedForRequest());
assert(CsrfProtection::validateTokenValue('audit-csrf-token'));
assert($_SESSION['csrf_token'] === $rotatedToken);

$inlineHandlers = [];
$views = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/Modules'));
foreach ($views as $view) {
    if (!$view->isFile() || $view->getExtension() !== 'php' || !str_contains($view->getPathname(), DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR)) {
        continue;
    }
    $content = file_get_contents($view->getPathname()) ?: '';
    if (preg_match('/\son(?:click|submit|change|input)=/i', $content)) {
        $inlineHandlers[] = $view->getPathname();
    }
}
assert($inlineHandlers === [], 'Handlers inline CSP restants : ' . implode(', ', $inlineHandlers));

$backup = file_get_contents($root . '/Core/Services/Production/BackupService.php') ?: '';
$health = file_get_contents($root . '/public/health.php') ?: '';
$router = file_get_contents($root . '/Core/Router/Router.php') ?: '';
$lot32 = file_get_contents($root . '/database/migrations/2026_06_01_lot32_index_qualite_robustesse.sql') ?: '';

assert(is_file($root . '/public/assets/js/dashboard.js'));
assert(str_contains($backup, '--defaults-extra-file='));
assert(!str_contains($backup, "'-p' . escapeshellarg"));
assert(str_contains($health, 'if (!$protected)'));
assert(str_contains($router, 'moduleIsEnabled'));
assert(!str_contains($lot32, 'idx_rcu_utilisateur_societe'));
assert(!str_contains($lot32, 'idx_fut_utilisateur_societe'));
assert(!str_contains($lot32, 'idx_aus_utilisateur_societe'));

echo "AuditAnomalyRegressionTest SUCCESS\n";
