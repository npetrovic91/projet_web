<?php
declare(strict_types=1);
// B-05 : fallback défensif — e() est globalement définie par AuthHelper.php
// mais ce guard rend le widget autonome en contexte CLI ou test unitaire.
if (!function_exists('e')) {
    function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/** @var array $widget_context */
$summary = is_array($widget_context['company_level_summary'] ?? null) ? $widget_context['company_level_summary'] : [];
?>
<div class="card card-outline card-success">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-network-wired mr-2"></i>Réseau sociétés / niveaux</h3></div>
    <div class="card-body p-0">
        <?php if (empty($summary)): ?>
            <div class="text-center py-3 text-muted">Aucune société liée au contexte courant.</div>
        <?php else: ?>
            <table class="table table-sm mb-0">
                <tbody>
                <?php foreach (array_slice($summary, 0, 5) as $soc): ?>
                <tr>
                    <td><?= e((string)$soc['societe_nom']) ?></td>
                    <td class="text-right"><span class="badge badge-primary"><?= (int)$soc['utilisateurs_total'] ?> utilisateur(s)</span></td>
                    <td class="text-right"><span class="badge badge-secondary">Niv. <?= (int)$soc['niveau_applicatif_max'] ?>/<?= (int)$soc['niveau_competence_max'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div class="card-footer text-right p-2"><a href="/companies" class="btn btn-sm btn-outline-success"><i class="fas fa-list mr-1"></i>Voir le réseau</a></div>
</div>
