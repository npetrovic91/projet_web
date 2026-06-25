<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$htaccess = file_get_contents($root . '/.htaccess');

assert($htaccess !== false, '.htaccess introuvable.');
assert(!is_file($root . '/diagnostic_login_demo.php'), 'Le diagnostic login ne doit pas etre livre en production.');
assert(!is_file($root . '/docs/DIAGNOSTIC_LOGIN_DEMO.md'), 'La documentation du diagnostic ne doit pas etre livree en production.');
assert(str_contains($htaccess, 'diagnostic_login_demo\.php'), '.htaccess doit bloquer explicitement le diagnostic.');
assert(str_contains($htaccess, '[F,L,NC]'), '.htaccess doit refuser le diagnostic, pas seulement le rediriger.');

echo "StandaloneLoginDiagnosticTest SUCCESS\n";
