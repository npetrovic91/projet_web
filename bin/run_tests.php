#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * AUTOSAV — Runner pour tests/Unit/*.php
 *
 * Ces tests sont des scripts autonomes (assert() + exit code), pas des
 * classes PHPUnit : chacun est exécuté comme un processus PHP séparé,
 * avec tests/_assert_handler.php en auto_prepend_file pour garantir que
 * les échecs s'affichent même quand APP_DEBUG=false (display_errors=0)
 * masque normalement toute sortie d'erreur en production.
 *
 * Usage : php bin/run_tests.php
 * Code de sortie : 0 si tous les tests passent, 1 sinon (utilisable en CI).
 */

$root = dirname(__DIR__);
$testsDir = $root . '/tests/Unit';
$handler = $root . '/tests/_assert_handler.php';
$phpBinary = PHP_BINARY;

$files = glob($testsDir . '/*.php') ?: [];
sort($files, SORT_STRING);

if (!$files) {
    fwrite(STDERR, "Aucun fichier de test trouvé dans {$testsDir}.\n");
    exit(1);
}

$failures = [];
$passed = 0;

foreach ($files as $file) {
    $name = basename($file);
    $command = sprintf(
        '%s -d auto_prepend_file=%s %s 2>&1',
        escapeshellarg($phpBinary),
        escapeshellarg($handler),
        escapeshellarg($file)
    );

    exec($command, $output, $exitCode);

    if ($exitCode === 0) {
        $passed++;
        echo "[OK]   {$name}\n";
    } else {
        $failures[] = $name;
        echo "[FAIL] {$name}\n";
        foreach ($output as $line) {
            echo "       {$line}\n";
        }
    }
    $output = [];
}

$total = count($files);
echo "\n--- {$passed}/{$total} tests passés ---\n";

if ($failures) {
    echo "Échecs : " . implode(', ', $failures) . "\n";
    exit(1);
}

exit(0);
