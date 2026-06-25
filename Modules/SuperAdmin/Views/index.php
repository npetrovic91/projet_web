<?php
declare(strict_types=1);
$indicateurs = $data['indicateurs'] ?? [];
$maintenance = $data['maintenance'] ?? [];
$modules = $data['modules'] ?? [];
$alertes = $data['alertes'] ?? [];
?>
<section class="content-header"><div class="container-fluid"><h1>Super-admin — Gestion de l’application</h1></div></section>
<section class="content"><div class="container-fluid">
    <div class="alert alert-info">Centre de contrôle global réservé au super-admin. Cette page agrège les modules existants sans les remplacer.</div>
    <div class="row">
        <?php foreach ($indicateurs as $libelle => $valeur): ?>
            <div class="col-md-3 col-sm-6"><div class="small-box bg-light"><div class="inner">
                <h3><?= htmlspecialchars((string)$valeur, ENT_QUOTES, 'UTF-8') ?></h3>
                <p><?= htmlspecialchars(str_replace('_', ' ', $libelle), ENT_QUOTES, 'UTF-8') ?></p>
            </div></div></div>
        <?php endforeach; ?>
    </div>
    <div class="card"><div class="card-header"><h3 class="card-title">État de maintenance</h3></div><div class="card-body">
        <span class="badge <?= !empty($maintenance['actif']) ? 'badge-danger' : 'badge-success' ?>">
            <?= !empty($maintenance['actif']) ? 'Maintenance active' : 'Application ouverte' ?>
        </span>
        <a class="btn btn-sm btn-outline-primary ml-2" href="<?= url('/settings/system') ?>">Gérer les paramètres système</a>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('/autotests') ?>">Ouvrir les autotests</a>
    </div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">Modules installés</h3></div><div class="card-body table-responsive p-0">
        <table class="table table-sm table-striped"><thead><tr><th>Code</th><th>Nom</th><th>Version</th><th>Noyau</th><th>Sociétés activées</th></tr></thead><tbody>
        <?php foreach ($modules as $module): ?>
            <tr>
                <td><?= htmlspecialchars((string)$module['mod_code'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)$module['mod_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($module['mod_version'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int)$module['mod_est_noyau'] === 1 ? 'Oui' : 'Non' ?></td>
                <td><?= (int)$module['societes_activees'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">Dernières alertes / journaux</h3></div><div class="card-body table-responsive p-0">
        <table class="table table-sm"><thead><tr><th>Date</th><th>Source</th><th>Niveau</th><th>Catégorie</th><th>Message</th></tr></thead><tbody>
        <?php foreach ($alertes as $alerte): ?>
            <tr><td><?= htmlspecialchars((string)$alerte['cree_le'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$alerte['source'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$alerte['niveau'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$alerte['categorie'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars((string)$alerte['message'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </div></div>
</div></section>
