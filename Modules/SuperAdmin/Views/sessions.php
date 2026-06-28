<?php
declare(strict_types=1);
/** @var array $sessions */
?>
<section class="content-header"><div class="container-fluid"><h1>Sessions actives</h1></div></section>
<section class="content"><div class="container-fluid">
    <div class="alert alert-info">Lecture seule — réservé au super-administrateur. Liste les fichiers de session PHP présents dans <code>storage/sessions/</code> (jusqu'à expiration/nettoyage par <code>bin/run_maintenance.php</code>).</div>

    <div class="card">
        <div class="card-header"><h3 class="card-title"><?= count($sessions) ?> session(s)</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-sm table-striped mb-0">
                <thead><tr><th>Identifiant</th><th>Utilisateur</th><th>Rôle(s)</th><th>Société active</th><th>Dernière activité</th><th>Taille</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($sessions as $session): ?>
                    <?php $resume = $session['resume']; ?>
                    <tr>
                        <td><code><?= htmlspecialchars(substr((string) $session['id'], 0, 16), ENT_QUOTES, 'UTF-8') ?>…</code></td>
                        <td>
                            <?php if (!empty($resume['utilisateur_id'])): ?>
                                #<?= (int) $resume['utilisateur_id'] ?> <?= htmlspecialchars((string) ($resume['utilisateur_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                <span class="text-muted">anonyme</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(implode(', ', (array) ($resume['role_codes'] ?? [])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= $resume['societe_active_id'] !== null ? (int) $resume['societe_active_id'] : '' ?></td>
                        <td><?= htmlspecialchars(date('Y-m-d H:i:s', (int) $session['modifie_le']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $session['taille_octets'] ?> o</td>
                        <td><a class="btn btn-sm btn-outline-primary" href="<?= url('/super-admin/sessions/' . rawurlencode((string) $session['id'])) ?>">Voir</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($sessions === []): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">Aucune session active trouvée.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div></section>
