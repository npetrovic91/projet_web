#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * AUTOSAV — Moteur de maintenance production
 *
 * Avant ce fichier, bin/run_maintenance.php se contentait de COMPTER les
 * politiques actives dans sav_politiques_maintenance sans jamais rien
 * exécuter ("mode contrôle") — la table sav_politiques_maintenance
 * recense pourtant 10 politiques réelles (journaux système, audit,
 * emails, RGPD, tentatives de connexion, webhooks, accès données
 * sensibles, sessions, contextes utilisateurs, événements application),
 * chacune avec sa propre durée de conservation et table d'archive
 * (sav_executions_maintenance et sav_verrous_maintenance existaient déjà
 * dans le schéma pour tracer/verrouiller ces exécutions, mais aucun code
 * ne les utilisait).
 *
 * Ce script lit GÉNÉRIQUEMENT chaque politique active et due, archive
 * puis purge par lots (jamais en une seule requête géante sur un
 * hébergement mutualisé), respecte le mode simulation, et journalise
 * tout dans sav_executions_maintenance. Un verrou (sav_verrous_maintenance)
 * empêche deux exécutions concurrentes (double déclenchement cron).
 *
 * Tâches complémentaires (CORRECTIF MED-5 et nettoyages additionnels) :
 *   - Purge des buckets de rate limiting expirés
 *   - Nettoyage des fichiers de session PHP expirés
 *   - Purge des exports temporaires anciens
 *   - Contrôles qualité BDD (sav_controles_qualite_base)
 *
 * Usage : php bin/run_maintenance.php [--dry-run]
 * Cron  : 0 3 * * * php bin/run_maintenance.php >> storage/logs/cron_maintenance.log 2>&1
 */

require dirname(__DIR__) . '/bootstrap.php';

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Core\Middleware\RateLimitMiddleware;

$forceDryRun = in_array('--dry-run', $argv, true);
$startedAt = microtime(true);
$db = Database::getInstance();
$report = [];
$errors = [];

echo '[' . date('Y-m-d H:i:s') . '] AUTOSAV maintenance démarrée' . ($forceDryRun ? ' (--dry-run)' : '') . PHP_EOL;

// ── 0. Verrou anti-concurrence ────────────────────────────────────────────────
// Empêche deux exécutions simultanées (ex. double déclenchement cron, ou
// exécution manuelle pendant que le cron tourne déjà).
$lockCode = 'run_maintenance_global';
$lockId = 0;
$lockHolder = gethostname() . ':' . getmypid();
$lockAcquired = false;

try {
    $existing = $db->fetch(
        "SELECT vma_id FROM sav_verrous_maintenance
          WHERE vma_code = :code AND vma_libere_le IS NULL AND vma_expire_le > NOW()
          LIMIT 1",
        ['code' => $lockCode]
    );
    if ($existing !== null) {
        echo "[SKIP] Une maintenance est déjà en cours (verrou actif). Sortie sans rien faire." . PHP_EOL;
        exit(0);
    }
    $db->execute(
        "INSERT INTO sav_verrous_maintenance (vma_code, vma_verrouille_par, vma_verrouille_le, vma_expire_le, vma_message)
         VALUES (:code, :who, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE), 'run_maintenance.php')",
        ['code' => $lockCode, 'who' => $lockHolder]
    );
    $lockId = (int) $db->lastInsertId();
    $lockAcquired = true;
} catch (\Throwable $e) {
    // Si le verrouillage lui-même échoue (table absente, DB indisponible),
    // on continue sans verrou plutôt que de bloquer toute maintenance —
    // mais on le signale clairement.
    $errors[] = 'verrou: ' . $e->getMessage();
    echo '[WARN] Verrou de maintenance indisponible, exécution sans protection anti-concurrence : ' . $e->getMessage() . PHP_EOL;
}

try {
    // ── 1. Politiques de maintenance (archivage + purge génériques) ──────────
    $policies = $db->fetchAll(
        "SELECT * FROM sav_politiques_maintenance
          WHERE pmt_est_active = 1 AND pmt_supprime_le IS NULL
            AND (pmt_prochaine_execution_le IS NULL OR pmt_prochaine_execution_le <= NOW())
          ORDER BY pmt_id"
    );
    echo '[INFO] ' . count($policies) . ' politique(s) due(s) sur ' . PHP_EOL;

    $policyReport = [];
    foreach ($policies as $policy) {
        $code = (string) $policy['pmt_code'];
        try {
            $policyReport[$code] = executerPolitique($db, $policy, $forceDryRun);
            echo sprintf(
                "[OK] %s : %d archivée(s), %d purgée(s), %d analysée(s)%s" . PHP_EOL,
                $code,
                $policyReport[$code]['archivees'],
                $policyReport[$code]['purgees'],
                $policyReport[$code]['analysees'],
                $forceDryRun ? ' [SIMULATION]' : ''
            );
        } catch (\Throwable $e) {
            $errors[] = "{$code}: " . $e->getMessage();
            echo "[ERR] {$code} : " . $e->getMessage() . PHP_EOL;
        }
    }
    $report['politiques'] = $policyReport;

    // ── 2. Buckets de rate limiting expirés (CORRECTIF MED-5) ────────────────
    try {
        $deleted = RateLimitMiddleware::purgeExpiredBuckets(3600);
        $report['buckets_rate_limit_supprimes'] = $deleted;
        echo "[OK] Buckets rate limit purgés : {$deleted}" . PHP_EOL;
    } catch (\Throwable $e) {
        $errors[] = 'rate_limit: ' . $e->getMessage();
        echo '[ERR] Rate limit : ' . $e->getMessage() . PHP_EOL;
    }

    // ── 3. Sessions PHP expirées ───────────────────────────────────────────────
    try {
        $sessionPath = defined('SESSION_SAVE_PATH') ? SESSION_SAVE_PATH : (defined('STORAGE_PATH') ? STORAGE_PATH . '/sessions' : sys_get_temp_dir());
        $sessionTtl = defined('SESSION_LIFETIME_MINUTES') ? SESSION_LIFETIME_MINUTES * 60 : 1800;
        $sessionLimit = time() - max(3600, $sessionTtl * 2);

        $deleted = 0;
        foreach (glob($sessionPath . '/sess_*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $sessionLimit) {
                @unlink($file);
                $deleted++;
            }
        }
        $report['sessions_expirees_supprimees'] = $deleted;
        echo "[OK] Sessions expirées supprimées : {$deleted}" . PHP_EOL;
    } catch (\Throwable $e) {
        $errors[] = 'sessions: ' . $e->getMessage();
        echo '[ERR] Sessions : ' . $e->getMessage() . PHP_EOL;
    }

    // ── 4. Exports temporaires expirés (> 7 jours) ────────────────────────────
    try {
        $exportsPath = defined('EXPORTS_PATH') ? EXPORTS_PATH : (defined('STORAGE_PATH') ? STORAGE_PATH . '/exports' : '');
        $deleted = 0;
        if ($exportsPath !== '' && is_dir($exportsPath)) {
            $limit = time() - (7 * 86400);
            foreach (glob($exportsPath . '/*') ?: [] as $file) {
                if (is_file($file) && filemtime($file) < $limit) {
                    @unlink($file);
                    $deleted++;
                }
            }
        }
        $report['exports_expires_supprimes'] = $deleted;
        echo "[OK] Exports expirés supprimés : {$deleted}" . PHP_EOL;
    } catch (\Throwable $e) {
        $errors[] = 'exports: ' . $e->getMessage();
        echo '[ERR] Exports : ' . $e->getMessage() . PHP_EOL;
    }

    // ── 5. Contrôles qualité BDD (sav_controles_qualite_base) ────────────────
    try {
        $controls = $db->fetchAll(
            "SELECT cqb_id, cqb_code, cqb_nom, cqb_requete_sql, cqb_severite
               FROM sav_controles_qualite_base
              WHERE cqb_est_actif = 1
              ORDER BY cqb_id"
        );

        $alerts = [];
        foreach ($controls as $control) {
            try {
                $result = $db->fetchColumn((string) $control['cqb_requete_sql']);
                $count = (int) ($result ?: 0);
                if ($count > 0) {
                    $alerts[] = sprintf('[%s] %s : %d occurrence(s)', $control['cqb_severite'], $control['cqb_nom'], $count);
                    echo "[QC-{$control['cqb_severite']}] {$control['cqb_nom']} : {$count} occurrence(s)" . PHP_EOL;
                }
            } catch (\Throwable) {
                // Un contrôle qui échoue ne bloque pas les autres.
            }
        }
        $report['controles_qualite'] = count($controls);
        $report['alertes_qualite'] = $alerts;
    } catch (\Throwable $e) {
        $errors[] = 'qualite_bdd: ' . $e->getMessage();
        echo '[ERR] Contrôles qualité BDD : ' . $e->getMessage() . PHP_EOL;
    }
} finally {
    // ── Libération du verrou, quoi qu'il arrive ───────────────────────────────
    if ($lockAcquired) {
        try {
            $db->execute("UPDATE sav_verrous_maintenance SET vma_libere_le = NOW() WHERE vma_id = :id", ['id' => $lockId]);
        } catch (\Throwable $e) {
            $errors[] = 'liberation_verrou: ' . $e->getMessage();
        }
    }
}

// ── Rapport final ──────────────────────────────────────────────────────────────
$elapsed = round(microtime(true) - $startedAt, 2);
$status = $errors === [] ? 'SUCCESS' : 'PARTIAL';

$logEntry = [
    'status' => $status,
    'started_at' => date('c', (int) $startedAt),
    'elapsed_s' => $elapsed,
    'dry_run' => $forceDryRun,
    'report' => $report,
    'errors' => $errors,
];

echo '[' . date('Y-m-d H:i:s') . "] Maintenance terminée en {$elapsed}s — {$status}" . PHP_EOL;

try {
    $db->execute(
        "INSERT INTO sav_journaux_maintenance (jma_niveau, jma_message, jma_details_json, jma_cree_le)
         VALUES (:niveau, :message, :details, NOW())",
        [
            'niveau' => $status === 'SUCCESS' ? 'info' : 'warning',
            'message' => $status === 'SUCCESS' ? 'Maintenance applicative terminée.' : 'Maintenance terminée avec erreurs.',
            'details' => json_encode($logEntry, JSON_UNESCAPED_UNICODE),
        ]
    );
} catch (\Throwable) {
    // Ne jamais faire échouer le script de maintenance à cause du logger DB.
}

try {
    if (function_exists('logger')) {
        $channel = logger('maintenance');
        $status === 'SUCCESS' ? $channel->info('Maintenance applicative terminée', $logEntry) : $channel->warning('Maintenance terminée avec erreurs', $logEntry);
    }
} catch (\Throwable) {
    // Idem pour le logger fichier.
}

exit($errors === [] ? 0 : 1);

// ============================================================
// Exécution générique d'une politique de maintenance
// ============================================================

/**
 * Exécute une politique d'archivage/purge générique. Les noms de
 * table/colonne viennent de la base (sav_politiques_maintenance), pas
 * d'une entrée utilisateur, mais sont quand même validés par un format
 * strict avant d'être insérés dans du SQL dynamique (défense en
 * profondeur, cohérent avec BaseModel::quoteIdentifier()).
 *
 * @return array{archivees:int, purgees:int, analysees:int}
 */
function executerPolitique(Database $db, array $policy, bool $forceDryRun): array
{
    $code = (string) $policy['pmt_code'];
    $typeAction = (string) $policy['pmt_type_action'];
    $tableSource = validerIdentifiant((string) $policy['pmt_table_source']);
    $tableArchive = $policy['pmt_table_archive'] !== null ? validerIdentifiant((string) $policy['pmt_table_archive']) : null;
    $colonneDate = validerIdentifiant((string) $policy['pmt_colonne_date']);
    $retentionJours = max(1, (int) $policy['pmt_duree_conservation_jours']);
    $tailleLot = max(100, min(20000, (int) $policy['pmt_taille_lot']));
    $simulation = $forceDryRun || ((int) $policy['pmt_mode_simulation'] === 1);
    $archivageActif = (int) $policy['pmt_archivage_active'] === 1 && $tableArchive !== null;
    $purgeActif = (int) $policy['pmt_purge_active'] === 1;

    if (!in_array($typeAction, ['archiver_purger', 'purger'], true)) {
        // optimiser/controler : non gérés ici, on ne fait rien plutôt que
        // de mal interpréter une politique qu'on ne sait pas exécuter.
        return ['archivees' => 0, 'purgees' => 0, 'analysees' => 0];
    }

    $db->execute(
        "INSERT INTO sav_executions_maintenance
            (exm_uuid, exm_politique_maintenance_id, exm_code_politique, exm_type_action,
             exm_table_source, exm_table_archive, exm_date_limite, exm_mode_simulation,
             exm_statut, exm_debut_le, exm_lancee_par)
         VALUES (UUID(), :pid, :code, :type, :source, :archive, DATE_SUB(NOW(), INTERVAL :jours DAY), :simu,
                 'demarree', NOW(), :who)",
        [
            'pid' => $policy['pmt_id'],
            'code' => $code,
            'type' => $typeAction,
            'source' => $tableSource,
            'archive' => $tableArchive,
            'jours' => $retentionJours,
            'simu' => $simulation ? 1 : 0,
            'who' => gethostname() . ':' . getmypid(),
        ]
    );
    $execId = (int) $db->lastInsertId();

    $archivees = 0;
    $purgees = 0;
    $analysees = (int) ($db->fetchColumn(
        "SELECT COUNT(*) FROM `{$tableSource}` WHERE `{$colonneDate}` < DATE_SUB(NOW(), INTERVAL {$retentionJours} DAY)"
    ) ?: 0);

    $statut = 'terminee';
    $erreur = null;

    try {
        if ($archivageActif && !$simulation) {
            // Archivage par lot : on boucle jusqu'à ce qu'un lot ne déplace
            // plus rien, jamais une seule requête géante sur un
            // hébergement mutualisé à ressources limitées.
            do {
                $db->execute(
                    "INSERT INTO `{$tableArchive}`
                     SELECT * FROM `{$tableSource}`
                      WHERE `{$colonneDate}` < DATE_SUB(NOW(), INTERVAL {$retentionJours} DAY)
                      LIMIT {$tailleLot}"
                );
                $affected = $db->rowCount();
                $archivees += $affected;
            } while ($affected >= $tailleLot);
        } elseif ($archivageActif && $simulation) {
            $archivees = $analysees;
        }

        if ($purgeActif && !$simulation) {
            do {
                $db->execute(
                    "DELETE FROM `{$tableSource}`
                      WHERE `{$colonneDate}` < DATE_SUB(NOW(), INTERVAL {$retentionJours} DAY)
                      LIMIT {$tailleLot}"
                );
                $affected = $db->rowCount();
                $purgees += $affected;
            } while ($affected >= $tailleLot);
        } elseif ($purgeActif && $simulation) {
            $purgees = $analysees;
        }
    } catch (\Throwable $e) {
        $statut = 'echouee';
        $erreur = $e->getMessage();
    }

    $db->execute(
        "UPDATE sav_executions_maintenance
         SET exm_statut = :statut, exm_lignes_archivees = :archivees, exm_lignes_purgees = :purgees,
             exm_lignes_analysees = :analysees, exm_erreur = :erreur, exm_fin_le = NOW(),
             exm_duree_ms = TIMESTAMPDIFF(MICROSECOND, exm_debut_le, NOW()) DIV 1000
         WHERE exm_id = :id",
        [
            'statut' => $statut,
            'archivees' => $archivees,
            'purgees' => $purgees,
            'analysees' => $analysees,
            'erreur' => $erreur,
            'id' => $execId,
        ]
    );

    if ($erreur !== null) {
        throw new \RuntimeException($erreur);
    }

    if (!$simulation) {
        $prochaine = match ($policy['pmt_frequence']) {
            'horaire' => 'DATE_ADD(NOW(), INTERVAL 1 HOUR)',
            'hebdomadaire' => 'DATE_ADD(NOW(), INTERVAL 1 WEEK)',
            'mensuelle' => 'DATE_ADD(NOW(), INTERVAL 1 MONTH)',
            'manuelle' => null,
            default => 'DATE_ADD(NOW(), INTERVAL 1 DAY)', // quotidienne
        };

        if ($prochaine !== null) {
            $db->execute(
                "UPDATE sav_politiques_maintenance
                 SET pmt_derniere_execution_le = NOW(), pmt_prochaine_execution_le = {$prochaine}
                 WHERE pmt_id = :id",
                ['id' => $policy['pmt_id']]
            );
        } else {
            $db->execute(
                "UPDATE sav_politiques_maintenance SET pmt_derniere_execution_le = NOW() WHERE pmt_id = :id",
                ['id' => $policy['pmt_id']]
            );
        }
    }

    return ['archivees' => $archivees, 'purgees' => $purgees, 'analysees' => $analysees];
}

/**
 * Valide un identifiant SQL (table ou colonne) issu de la base elle-même.
 * Même si la source est "de confiance" (sav_politiques_maintenance,
 * éditable uniquement par un administrateur), ce script écrit du SQL
 * dynamique : défense en profondeur avant toute interpolation.
 */
function validerIdentifiant(string $identifiant): string
{
    $identifiant = trim($identifiant, '` ');
    if (!preg_match('/^[A-Za-z0-9_]+$/', $identifiant)) {
        throw new \InvalidArgumentException('Identifiant SQL invalide dans une politique de maintenance : ' . $identifiant);
    }
    return $identifiant;
}
