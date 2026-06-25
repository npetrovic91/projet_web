<?php
declare(strict_types=1);
$parametres = $data['parametres'] ?? [];
?>
<section class="content-header"><div class="container-fluid"><h1>Gestion globale de l’application</h1></div></section>
<section class="content"><div class="container-fluid">
    <div class="mb-3">
        <a class="btn btn-primary" href="<?= url('/super-admin') ?>">Tableau de bord super-admin</a>
        <a class="btn btn-outline-secondary" href="<?= url('/super-admin/export.json') ?>">Exporter JSON</a>
        <a class="btn btn-outline-info" href="<?= url('/autotests') ?>">Autotests modules</a>
    </div>
    <div class="card"><div class="card-header"><h3 class="card-title">Paramètres critiques</h3></div><div class="card-body table-responsive p-0">
        <table class="table table-sm table-striped"><thead><tr><th>Domaine</th><th>Clé</th><th>Valeur</th><th>Secret</th><th>Système</th><th>Modifié le</th></tr></thead><tbody>
        <?php foreach ($parametres as $p): ?>
            <tr>
                <td><?= htmlspecialchars((string)$p['pap_domaine'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)$p['pap_cle'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><code><?= (int)$p['pap_est_secret'] === 1 ? '[SECRET MASQUÉ]' : htmlspecialchars((string)($p['pap_valeur_json'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                <td><?= (int)$p['pap_est_secret'] === 1 ? 'Oui' : 'Non' ?></td>
                <td><?= (int)$p['pap_est_systeme'] === 1 ? 'Oui' : 'Non' ?></td>
                <td><?= htmlspecialchars((string)($p['pap_modifie_le'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table>
    </div></div>
</div></section>
