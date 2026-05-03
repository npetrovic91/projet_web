<?php declare(strict_types=1);
/**
 * Vue : Supervision Sécurité
 * Variables : $stats, $topFailedIps, $attempts, $activeIpBlocks,
 *             $activeEmailBlocks, $unblockHistory, $csrfToken, $flash,
 *             $currentPage, $perPage, $totalAttempts, $filterIp, $filterEmail
 */

// Helpers locaux
$esc = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$dt  = fn(?string $d): string => $d ? date('d/m/Y H:i:s', strtotime($d)) : '—';
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-shield-alt mr-2 text-danger"></i>
                    Supervision Sécurité
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/admin">Administration</a></li>
                    <li class="breadcrumb-item active">Sécurité</li>
                </ol>
                <a class="btn btn-sm btn-outline-danger float-sm-right mr-2" href="/admin/security/report.pdf" target="_blank" rel="noopener">
                    <i class="fas fa-file-pdf mr-1"></i> Rapport PDF
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Flash messages -->
        <?php if (!empty($flash)): ?>
            <?php foreach ($flash as $type => $messages): ?>
                <?php foreach ((array) $messages as $msg): ?>
                    <?php $cls = match($type) { 'success' => 'success', 'error' => 'danger', 'warning' => 'warning', default => 'info' }; ?>
                    <div class="alert alert-<?= $cls ?> alert-dismissible fade show">
                        <?= $esc($msg) ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ======================== STATISTIQUES ======================== -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= number_format($stats['total_attempts'] ?? 0) ?></h3>
                        <p>Tentatives (<?= $stats['window_hours'] ?>h)</p>
                    </div>
                    <div class="icon"><i class="fas fa-sign-in-alt"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= number_format($stats['failed_attempts'] ?? 0) ?></h3>
                        <p>Échecs (<?= $stats['window_hours'] ?>h)</p>
                    </div>
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= number_format($stats['active_ip_blocks'] ?? 0) ?></h3>
                        <p>IP bloquées actives</p>
                    </div>
                    <div class="icon"><i class="fas fa-ban"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3><?= number_format($stats['active_email_blocks'] ?? 0) ?></h3>
                        <p>Emails bloqués actifs</p>
                    </div>
                    <div class="icon"><i class="fas fa-envelope-open-text"></i></div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- ========== IP BLOQUÉES ========== -->
            <div class="col-lg-6">
                <div class="card card-danger card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-ban mr-1"></i>IP Bloquées
                            <span class="badge badge-danger ml-1"><?= count($activeIpBlocks) ?></span>
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>IP</th>
                                        <th>Raison</th>
                                        <th>Expire</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($activeIpBlocks)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-3">
                                            Aucune IP bloquée actuellement.
                                        </td></tr>
                                    <?php else: ?>
                                        <?php foreach ($activeIpBlocks as $block): ?>
                                        <tr>
                                            <td><code><?= $esc($block['ibl_ip']) ?></code></td>
                                            <td>
                                                <span class="badge badge-<?= $block['ibl_type'] === 'auto' ? 'danger' : 'dark' ?>">
                                                    <?= $block['ibl_type'] === 'auto' ? 'Auto' : 'Manuel' ?>
                                                </span>
                                                <small class="d-block text-muted">
                                                    <?= $esc(mb_substr($block['ibl_reason'] ?? '', 0, 60)) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($block['ibl_expires_at']): ?>
                                                    <small><?= $dt($block['ibl_expires_at']) ?></small>
                                                <?php else: ?>
                                                    <span class="badge badge-dark">Permanent</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button"
                                                        class="btn btn-xs btn-warning"
                                                        onclick="showUnblockModal('ip', <?= (int)$block['ibl_id'] ?>, '<?= $esc($block['ibl_ip']) ?>')">
                                                    <i class="fas fa-unlock"></i> Débloquer
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== EMAILS BLOQUÉS ========== -->
            <div class="col-lg-6">
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-envelope-open-text mr-1"></i>Emails Bloqués
                            <span class="badge badge-warning ml-1"><?= count($activeEmailBlocks) ?></span>
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Email</th>
                                        <th>Tentatives</th>
                                        <th>Expire</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($activeEmailBlocks)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-3">
                                            Aucun email bloqué actuellement.
                                        </td></tr>
                                    <?php else: ?>
                                        <?php foreach ($activeEmailBlocks as $block): ?>
                                        <tr>
                                            <td><small><?= $esc($block['ebl_email']) ?></small></td>
                                            <td>
                                                <span class="badge badge-danger">
                                                    <?= (int)$block['ebl_attempt_count'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($block['ebl_expires_at']): ?>
                                                    <small><?= $dt($block['ebl_expires_at']) ?></small>
                                                <?php else: ?>
                                                    <span class="badge badge-dark">Permanent</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button"
                                                        class="btn btn-xs btn-warning"
                                                        onclick="showUnblockModal('email', <?= (int)$block['ebl_id'] ?>, '<?= $esc($block['ebl_email']) ?>')">
                                                    <i class="fas fa-unlock"></i> Débloquer
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== TENTATIVES DE CONNEXION ========== -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list mr-1"></i>Journal des tentatives de connexion
                </h3>
                <div class="card-tools">
                    <!-- Filtres rapides -->
                    <form method="GET" action="/admin/security" class="form-inline">
                        <input type="text" name="ip" class="form-control form-control-sm mr-1"
                               placeholder="Filtrer IP"
                               value="<?= $esc($filterIp ?? '') ?>">
                        <input type="text" name="email" class="form-control form-control-sm mr-1"
                               placeholder="Filtrer email"
                               value="<?= $esc($filterEmail ?? '') ?>">
                        <button type="submit" class="btn btn-sm btn-primary mr-1">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="/admin/security" class="btn btn-sm btn-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <th>Date/Heure</th>
                                <th>IP</th>
                                <th>Email</th>
                                <th>Résultat</th>
                                <th>Motif d'échec</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($attempts)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">
                                    Aucune tentative trouvée.
                                </td></tr>
                            <?php else: ?>
                                <?php foreach ($attempts as $attempt): ?>
                                <tr class="<?= $attempt['lat_success'] ? 'table-success' : 'table-danger' ?>">
                                    <td><small><?= $dt($attempt['lat_created_at']) ?></small></td>
                                    <td><code><?= $esc($attempt['lat_ip']) ?></code></td>
                                    <td><small><?= $esc($attempt['lat_email']) ?></small></td>
                                    <td>
                                        <?php if ($attempt['lat_success']): ?>
                                            <span class="badge badge-success"><i class="fas fa-check"></i> Succès</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger"><i class="fas fa-times"></i> Échec</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= $esc($attempt['lat_failure_reason'] ?? '—') ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($totalAttempts > $perPage): ?>
            <div class="card-footer">
                <?php
                $totalPages = (int) ceil($totalAttempts / $perPage);
                $baseUrl    = '/admin/security?page=';
                if ($filterIp)    $baseUrl .= '&ip=' . urlencode($filterIp);
                if ($filterEmail) $baseUrl .= '&email=' . urlencode($filterEmail);
                ?>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $baseUrl . ($currentPage - 1) ?>">«</a>
                            </li>
                        <?php endif; ?>
                        <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
                            <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="<?= $baseUrl . $p ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= $baseUrl . ($currentPage + 1) ?>">»</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <small class="text-muted">
                    <?= $totalAttempts ?> tentative(s) au total
                </small>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /.container-fluid -->
</section>

<!-- ========== MODAL DE DÉBLOCAGE ========== -->
<div class="modal fade" id="unblockModal" tabindex="-1" role="dialog" aria-labelledby="unblockModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="unblockModalLabel">
                    <i class="fas fa-unlock mr-2"></i>Confirmer le déblocage
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fermer">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" id="unblockForm">
                <input type="hidden" name="<?= CSRF_FORM_FIELD ?>"
                       value="<?= $esc($csrfToken) ?>">
                <div class="modal-body">
                    <p>Vous allez débloquer : <strong id="unblockTarget"></strong></p>
                    <div class="form-group">
                        <label for="unblockReason">
                            Raison du déblocage <span class="text-danger">*</span>
                        </label>
                        <textarea name="reason"
                                  id="unblockReason"
                                  class="form-control"
                                  rows="3"
                                  required
                                  maxlength="512"
                                  placeholder="Saisir la raison du déblocage (obligatoire)..."></textarea>
                        <small class="form-text text-muted">
                            Cette raison sera enregistrée dans le journal d'audit (R27).
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-unlock mr-1"></i>Confirmer le déblocage
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showUnblockModal(type, id, target) {
    var form = document.getElementById('unblockForm');
    form.action = '/admin/unblock/' + type + '/' + id;
    document.getElementById('unblockTarget').textContent = target;
    document.getElementById('unblockReason').value = '';
    $('#unblockModal').modal('show');
}
</script>

SECTION 8 — ENDPOINT AJAX CGU
src/Modules/Ajax/Controllers/TermsAjaxController.php
