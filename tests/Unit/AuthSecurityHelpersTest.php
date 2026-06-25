<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

assert(function_exists('csrf_field'));
assert(str_contains(csrf_field(), '<input'));

$_SERVER['REMOTE_ADDR'] = '198.51.100.10';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
assert(client_ip() === '198.51.100.10');

echo "AuthSecurityHelpersTest SUCCESS\n";
