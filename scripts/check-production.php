<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$ok = true;
$warnings = [];
$errors = [];

if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    $ok = false;
    $errors[] = 'PHP 8.1 minimum requis ; version détectée : ' . PHP_VERSION . '.';
}

foreach (['pdo', 'json', 'mbstring', 'openssl', 'session', 'filter'] as $extension) {
    if (!extension_loaded($extension)) {
        $ok = false;
        $errors[] = "Extension PHP manquante : {$extension}.";
    }
}

foreach (['pdo_mysql', 'fileinfo', 'intl', 'zip', 'gd'] as $extension) {
    if (!extension_loaded($extension)) {
        $warnings[] = "Extension PHP recommandée absente : {$extension}.";
    }
}

if (!is_file($root . '/.env')) {
    $warnings[] = 'Aucun fichier .env trouvé. Copiez .env.example vers .env et renseignez les secrets de production.';
} else {
    $envContent = file_get_contents($root . '/.env') ?: '';
    preg_match('/^APP_ENV=(.*)$/m', $envContent, $appEnvMatch);
    preg_match('/^ENCRYPTION_KEY=(.*)$/m', $envContent, $keyMatch);
    $appEnv = trim($appEnvMatch[1] ?? 'production', " \t\n\r\0\x0B\"'");
    $encryptionKey = trim($keyMatch[1] ?? '', " \t\n\r\0\x0B\"'");
    // "remplacer" ajouté : .env.production.template utilise ce préfixe pour ses placeholders.
    $placeholder = preg_match('/^(base64:)?(generer_|changer_|change_|remplacer|CHANGE_ME|autosav-change-me|autosav-dev-only-key)/i', $encryptionKey) === 1;
    if ($appEnv === 'production' && ($encryptionKey === '' || $placeholder)) {
        $ok = false;
        $errors[] = 'ENCRYPTION_KEY absente ou placeholder dans .env en production.';
    }
    if (str_starts_with($encryptionKey, 'base64:')) {
        $decoded = base64_decode(substr($encryptionKey, 7), true);
        if ($decoded === false || strlen($decoded) < 32) {
            $ok = false;
            $errors[] = 'ENCRYPTION_KEY base64 invalide ou inférieure à 32 octets.';
        }
    } elseif ($encryptionKey !== '' && !$placeholder && strlen($encryptionKey) < 32) {
        $ok = false;
        $errors[] = 'ENCRYPTION_KEY doit contenir au minimum 32 caractères, ou utiliser base64:<32 octets>.';
    }
}

foreach (['logs', 'sessions', 'cache', 'uploads', 'exports', 'backups'] as $dir) {
    $path = $root . '/storage/' . $dir;
    if (!is_dir($path)) {
        $ok = false;
        $errors[] = "Dossier manquant : storage/{$dir}.";
        continue;
    }
    if (!is_writable($path)) {
        $warnings[] = "Dossier non inscriptible par PHP : storage/{$dir}.";
    }
}

foreach (['public/index.php', 'bootstrap.php', 'config/database.php', 'config/urls.php', 'Core/Router/Router.php'] as $file) {
    if (!is_file($root . '/' . $file)) {
        $ok = false;
        $errors[] = "Fichier critique manquant : {$file}.";
    }
}

$urls = $root . '/config/urls.php';
if (is_file($urls)) {
    $content = file_get_contents($urls) ?: '';
    $routes = preg_match_all("/'[A-Z]+\\s+[^']+'\\s*=>/", $content);
    if ($routes < 300) {
        $warnings[] = "Nombre de routes inférieur au lot35 attendu : {$routes}.";
    }
}

// Fraîcheur des tâches planifiées (cron). Ce contrôle est informatif : il ne
// peut pas vérifier qu'un cron est installé côté hébergeur, seulement que les
// scripts qu'il est censé déclencher ont effectivement tourné récemment.
$cronChecks = [
    'storage/logs/cron_backup.log'   => ['label' => 'Sauvegarde BDD (bin/backup_database.php)', 'max_age_hours' => 26],
    'storage/logs/cron_rotate_logs.log' => ['label' => 'Rotation des logs (bin/rotate_logs.php)', 'max_age_hours' => 26],
    'storage/logs/cron_maintenance.log' => ['label' => 'Maintenance (bin/run_maintenance.php)', 'max_age_hours' => 26],
    'storage/logs/cron_health.log'   => ['label' => 'Healthcheck (bin/health_check.php)', 'max_age_hours' => 1],
];
foreach ($cronChecks as $relativePath => $meta) {
    $path = $root . '/' . $relativePath;
    if (!is_file($path)) {
        $warnings[] = "Cron jamais exécuté : {$meta['label']} — {$relativePath} introuvable. " .
            "Vérifiez que la tâche planifiée est installée côté hébergeur (voir deploy/cron.example).";
        continue;
    }
    $ageHours = (time() - (filemtime($path) ?: 0)) / 3600;
    if ($ageHours > $meta['max_age_hours']) {
        $warnings[] = sprintf(
            "Cron en retard : %s — dernière exécution il y a %.1f h (attendu < %d h).",
            $meta['label'],
            $ageHours,
            $meta['max_age_hours']
        );
    }
}

if ($errors) {
    fwrite(STDERR, "AUTOSAV production check — ERREURS\n");
    foreach ($errors as $message) {
        fwrite(STDERR, "[ERREUR] {$message}\n");
    }
}

if ($warnings) {
    fwrite(STDOUT, "AUTOSAV production check — AVERTISSEMENTS\n");
    foreach ($warnings as $message) {
        fwrite(STDOUT, "[WARN] {$message}\n");
    }
}

if (!$errors && !$warnings) {
    fwrite(STDOUT, "AUTOSAV production check — OK\n");
}

exit($ok ? 0 : 1);
