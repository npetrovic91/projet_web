<?php
declare(strict_types=1);
?>
<section class="content-header"><div class="container-fluid"><h1>Autotests des composants</h1></div></section>
<section class="content"><div class="container-fluid">
    <div class="alert alert-info">Chaque module et le noyau Core sont controles. Les methodes publiques sont inventoriees sans lancer d'action destructive.</div>
    <div class="row">
        <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)($resume['composants'] ?? $resume['modules']) ?></h3><p>Composants audites</p></div></div></div>
        <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)$resume['classes'] ?></h3><p>Classes</p></div></div></div>
        <div class="col-md-3"><div class="small-box bg-light"><div class="inner"><h3><?= (int)$resume['methodes_publiques'] ?></h3><p>Fonctions publiques</p></div></div></div>
        <div class="col-md-3"><div class="small-box <?= !empty($resume['success']) ? 'bg-success' : 'bg-danger' ?>"><div class="inner"><h3><?= !empty($resume['success']) ? 'SUCCESS' : 'NOTOK' ?></h3><p>Etat global</p></div></div></div>
    </div>
    <div class="mb-3"><a class="btn btn-outline-secondary" href="<?= url('/autotests/export.json') ?>">Exporter JSON</a></div>
    <div class="card"><div class="card-header"><h3 class="card-title">Pages d'autotest par composant</h3></div><div class="card-body table-responsive p-0">
        <table class="table table-sm table-striped"><thead><tr><th>Composant</th><th>Type</th><th>Page autotest</th><th>Fichiers PHP</th><th>Classes</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($modules as $module): ?>
            <tr>
                <td><strong><?= htmlspecialchars((string)$module['nom'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td><?= ($module['type'] ?? 'module') === 'core' ? 'Noyau' : 'Module' ?></td>
                <td><?= !empty($module['page_autotest']) ? '<span class="badge badge-success">SUCCESS</span>' : '<span class="badge badge-danger">NOTOK</span>' ?></td>
                <td><?= (int)($module['fichiers_php'] ?? 0) ?></td>
                <td><?= count($module['classes'] ?? []) ?></td>
                <td><a class="btn btn-sm btn-primary" href="<?= url('/autotests/module/' . rawurlencode((string)$module['nom'])) ?>">Tester le composant</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div></div>
</div></section>
