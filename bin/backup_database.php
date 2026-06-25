#!/usr/bin/env php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Nenad\Autosav\Core\Services\Production\BackupService;

$result = (new BackupService())->createDatabaseBackup();
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(($result['status'] ?? 'NOTOK') === 'SUCCESS' ? 0 : 1);
