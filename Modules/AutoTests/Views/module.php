<?php
declare(strict_types=1);
$resultat = $resultat ?? ['nom' => 'Composant', 'statut' => 'NOTOK', 'message' => 'Resultat indisponible.', 'classes' => []];
?>
<section class="content-header"><div class="container-fluid"><h1>Autotest - <?= htmlspecialchars((string)$resultat['nom'], ENT_QUOTES, 'UTF-8') ?></h1></div></section>
<section class="content"><div class="container-fluid">
    <div class="alert <?= ($resultat['statut'] ?? 'NOTOK') === 'SUCCESS' ? 'alert-success' : 'alert-danger' ?>">
        <strong><?= htmlspecialchars((string)$resultat['statut'], ENT_QUOTES, 'UTF-8') ?></strong> - <?= htmlspecialchars((string)$resultat['message'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div class="mb-3 text-muted"><?= (int)($resultat['fichiers_php'] ?? 0) ?> fichiers PHP analyses dans ce composant.</div>
    <div class="mb-3"><a class="btn btn-outline-secondary" href="<?= url('/autotests') ?>">Retour aux autotests</a></div>
    <?php foreach (($resultat['classes'] ?? []) as $classe): ?>
        <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><?= htmlspecialchars((string)$classe['classe'], ENT_QUOTES, 'UTF-8') ?></h3>
            <span>
                <span class="badge <?= $classe['statut'] === 'SUCCESS' ? 'badge-success' : 'badge-danger' ?>"><?= htmlspecialchars((string)$classe['statut'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if ($classe['statut'] !== 'SUCCESS'): ?>
                    <a class="btn btn-xs btn-danger ml-2" href="<?= url($classe['debug_url']) ?>">Demander le debogage</a>
                <?php endif; ?>
            </span>
        </div><div class="card-body p-0">
            <table class="table table-sm mb-0"><thead><tr><th>Fonction publique</th><th>Statut</th><th>Message</th><th>Debogage</th></tr></thead><tbody>
            <?php foreach (($classe['methodes_publiques'] ?? []) as $methode): ?>
                <tr>
                    <td><code><?= htmlspecialchars((string)$methode['nom'], ENT_QUOTES, 'UTF-8') ?>()</code></td>
                    <td><span class="badge <?= $methode['statut'] === 'SUCCESS' ? 'badge-success' : 'badge-danger' ?>"><?= htmlspecialchars((string)$methode['statut'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars((string)$methode['message'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?php if ($methode['statut'] !== 'SUCCESS'): ?><a class="btn btn-xs btn-danger" href="<?= url($methode['debug_url']) ?>">Demander le debogage</a><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($classe['methodes_publiques'])): ?><tr><td colspan="4" class="text-muted">Aucune fonction publique propre detectee.</td></tr><?php endif; ?>
            </tbody></table>
        </div></div>
    <?php endforeach; ?>
</div></section>
