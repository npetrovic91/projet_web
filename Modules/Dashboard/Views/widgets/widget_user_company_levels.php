<?php
declare(strict_types=1);
// B-05 : fallback défensif — e() est globalement définie par AuthHelper.php
// mais ce guard rend le widget autonome en contexte CLI ou test unitaire.
if (!function_exists('e')) {
    function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

/**
 * Widget central demandé : tri utilisateur → société d’appartenance → niveaux.
 * @var array $widget_context
 */
$rows = is_array($widget_context['user_company_levels'] ?? null) ? $widget_context['user_company_levels'] : [];
?>
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-layer-group mr-2"></i>Utilisateurs / sociétés / niveaux
        </h3>
    </div>
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="text-center py-3 text-muted">
                <i class="fas fa-users-slash fa-2x mb-2 d-block text-light"></i>
                Aucune affectation utilisateur active pour le contexte courant.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Société</th>
                        <th>Niveau applicatif</th>
                        <th>Niveau compétence</th>
                        <th>Certif.</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_slice($rows, 0, 8) as $row): ?>
                        <tr>
                            <td>
                                <div class="font-weight-bold small"><?= e((string)$row['utilisateur_nom']) ?></div>
                                <div class="text-muted" style="font-size:.78rem;"><?= e((string)($row['uti_email'] ?? '')) ?></div>
                            </td>
                            <td>
                                <div class="small"><?= e((string)$row['societe_nom']) ?></div>
                                <?php if (!empty($row['services_noms']) || !empty($row['equipes_noms'])): ?>
                                    <div class="text-muted" style="font-size:.75rem;">
                                        <?= e((string)($row['services_noms'] ?: $row['equipes_noms'])) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-info"><?= e((string)$row['niveau_applicatif_label']) ?></span>
                                <?php if (!empty($row['roles_noms'])): ?>
                                    <div class="text-muted" style="font-size:.72rem;"><?= e((string)$row['roles_noms']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-secondary">
                                    <?= e((string)$row['niveau_competence_nom']) ?>
                                </span>
                                <?php if (!empty($row['competences_resume'])): ?>
                                    <div class="text-muted" style="font-size:.72rem;"><?= e((string)$row['competences_resume']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-success"><?= (int)$row['certifications_total'] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-footer small text-muted">
        Tri appliqué : utilisateur, société d’appartenance, niveau applicatif, niveau compétence.
    </div>
</div>
