#!/usr/bin/env php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Nenad\Autosav\Core\Database\Database;

$result = ['status' => 'SUCCESS', 'checked_at' => date('c'), 'executed' => 0, 'message' => 'Maintenance applicative prête. Les politiques sont lues depuis sav_politiques_maintenance.'];

try {
    $db = Database::getInstance();
    $policies = $db->fetchAll("SELECT pmt_id, pmt_code, pmt_type_action, pmt_table_source FROM sav_politiques_maintenance WHERE pmt_est_active = 1 AND pmt_supprime_le IS NULL LIMIT 50");
    $result['policies_detected'] = count($policies);
    $result['policies'] = $policies;
    $db->execute("INSERT INTO sav_journaux_maintenance (jma_niveau, jma_message, jma_details_json, jma_cree_le) VALUES ('info', 'Cron maintenance exécuté en mode contrôle.', :details, NOW())", [
        'details' => json_encode(['mode' => 'controle', 'policies_detected' => count($policies)], JSON_UNESCAPED_UNICODE),
    ]);
} catch (Throwable $e) {
    $result = ['status' => 'NOTOK', 'message' => $e->getMessage(), 'checked_at' => date('c')];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(($result['status'] ?? 'NOTOK') === 'SUCCESS' ? 0 : 1);
