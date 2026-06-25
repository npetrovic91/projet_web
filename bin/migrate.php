#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * AUTOSAV — Runner de migrations SQL
 *
 * Applique, dans l'ordre alphabétique, tous les fichiers
 * database/migrations/*.sql qui n'ont pas encore été appliqués sur la
 * base courante (suivi dans la table sav_migrations).
 *
 * Usage :
 *   php bin/migrate.php            Applique les migrations en attente.
 *   php bin/migrate.php --dry-run  Liste ce qui serait appliqué, sans rien exécuter.
 *
 * Les fichiers contenant des blocs DELIMITER (déclencheurs) sont exécutés
 * via le client `mysql` (seul outil qui comprend DELIMITER nativement).
 * Les fichiers simples (sans DELIMITER) sont exécutés directement via PDO.
 */

require dirname(__DIR__) . '/bootstrap.php';

use Nenad\Autosav\Core\Database\Database;

$dryRun = in_array('--dry-run', $argv, true);

$migrationsDir = DATABASE_PATH . '/migrations';
if (!is_dir($migrationsDir)) {
    fwrite(STDERR, "Dossier de migrations introuvable : {$migrationsDir}\n");
    exit(1);
}

$pdo = Database::getInstance()->getPdo();

$pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS sav_migrations (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        filename VARCHAR(255) NOT NULL,
        checksum CHAR(64) NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_sav_migrations_filename (filename)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Suivi des migrations SQL appliquées (bin/migrate.php)'
SQL);

$applied = [];
foreach ($pdo->query('SELECT filename, checksum FROM sav_migrations') as $row) {
    $applied[$row['filename']] = $row['checksum'];
}

$files = glob($migrationsDir . '/*.sql') ?: [];
sort($files, SORT_STRING);

if (!$files) {
    echo "Aucun fichier de migration trouvé dans {$migrationsDir}.\n";
    exit(0);
}

$pending = [];
foreach ($files as $file) {
    $name = basename($file);
    $checksum = hash('sha256', (string) file_get_contents($file));

    if (isset($applied[$name])) {
        if ($applied[$name] !== $checksum) {
            fwrite(STDERR, "[ATTENTION] {$name} a déjà été appliqué mais son contenu a changé depuis "
                . "(checksum différent). Ne modifiez jamais une migration déjà appliquée : créez-en une nouvelle.\n");
        }
        continue;
    }

    $pending[] = ['file' => $file, 'name' => $name, 'checksum' => $checksum];
}

if (!$pending) {
    echo "Base à jour : aucune migration en attente.\n";
    exit(0);
}

echo count($pending) . " migration(s) en attente :\n";
foreach ($pending as $migration) {
    echo "  - {$migration['name']}\n";
}

if ($dryRun) {
    echo "\n--dry-run : rien n'a été exécuté.\n";
    exit(0);
}

$mysqlBinary = trim((string) (getenv('MYSQL_BINARY') ?: 'mysql'));

foreach ($pending as $migration) {
    echo "\nApplication de {$migration['name']}...\n";
    $sql = (string) file_get_contents($migration['file']);

    try {
        if (str_contains($sql, 'DELIMITER')) {
            runViaMysqlClient($migration['file'], $mysqlBinary);
        } else {
            runViaPdo($pdo, $sql);
        }
    } catch (\Throwable $e) {
        fwrite(STDERR, "ÉCHEC sur {$migration['name']} : {$e->getMessage()}\n");
        fwrite(STDERR, "Migration interrompue : les fichiers suivants n'ont pas été appliqués.\n");
        exit(1);
    }

    $stmt = $pdo->prepare('INSERT INTO sav_migrations (filename, checksum) VALUES (:filename, :checksum)');
    $stmt->execute(['filename' => $migration['name'], 'checksum' => $migration['checksum']]);
    echo "  OK ({$migration['name']} enregistré dans sav_migrations).\n";
}

echo "\nToutes les migrations en attente ont été appliquées.\n";
exit(0);

/**
 * Exécute un fichier simple (sans DELIMITER) statement par statement via PDO.
 * Découpage naïf sur ';' en fin de ligne : suffisant pour des migrations
 * standard (CREATE/ALTER/DROP TABLE, INSERT simples) sans procédures stockées.
 */
function runViaPdo(\PDO $pdo, string $sql): void
{
    $statements = array_filter(
        array_map('trim', preg_split('/;\s*(\r?\n|$)/', $sql) ?: []),
        static fn (string $s) => $s !== '' && !str_starts_with($s, '--')
    );

    $pdo->beginTransaction();
    try {
        foreach ($statements as $statement) {
            if ($statement === '' || str_starts_with(ltrim($statement), '--')) {
                continue;
            }
            $pdo->exec($statement);
        }
        $pdo->commit();
    } catch (\Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Exécute un fichier contenant des blocs DELIMITER (déclencheurs) via le
 * client `mysql` en ligne de commande, seul à comprendre cette directive.
 */
function runViaMysqlClient(string $file, string $mysqlBinary): void
{
    $host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : '127.0.0.1');
    $port = getenv('DB_PORT') ?: (defined('DB_PORT') ? (string) DB_PORT : '3306');
    $user = getenv('DB_USERNAME') ?: (defined('DB_USER') ? DB_USER : '');
    $pass = getenv('DB_PASSWORD') ?: (defined('DB_PASS') ? DB_PASS : '');
    $name = getenv('DB_DATABASE') ?: (defined('DB_NAME') ? DB_NAME : '');

    $defaultsFile = tempnam(sys_get_temp_dir(), 'autosav_migrate_');
    if ($defaultsFile === false) {
        throw new \RuntimeException('Impossible de créer le fichier temporaire mysql.');
    }
    $escapedPassword = str_replace(["\\", '"', "\r", "\n"], ["\\\\", '\\"', '', ''], (string) $pass);
    file_put_contents($defaultsFile, "[client]\npassword=\"{$escapedPassword}\"\n");
    chmod($defaultsFile, 0600);

    $command = sprintf(
        '%s --defaults-extra-file=%s -h%s -P%s -u%s %s < %s 2>&1',
        escapeshellcmd($mysqlBinary),
        escapeshellarg($defaultsFile),
        escapeshellarg((string) $host),
        escapeshellarg((string) $port),
        escapeshellarg((string) $user),
        escapeshellarg((string) $name),
        escapeshellarg($file)
    );

    exec($command, $output, $code);
    @unlink($defaultsFile);

    if ($code !== 0) {
        throw new \RuntimeException("client mysql a échoué (code {$code}) : " . implode("\n", $output));
    }
}
