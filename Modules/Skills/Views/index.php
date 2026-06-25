<?php
declare(strict_types=1);
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$items = $items ?? [];
$csrf = $csrf_token ?? '';
$isSuperAdmin = !empty($isSuperAdmin);
$activeCompanyId = (int) ($companyId ?? $activeCompanyId ?? 0);
$publicationScopes = $publicationScopes ?? ['interne' => 'Interne société'];
$scopeBadge = static function (string $scope): string {
    return match ($scope) {
        'plateforme' => '<span class="badge badge-dark">Plateforme</span>',
        'reseau' => '<span class="badge badge-info">Réseau</span>',
        default => '<span class="badge badge-secondary">Interne</span>',
    };
};
?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1><i class="fas fa-star mr-2"></i>Compétences métier</h1></div>
      <div class="col-sm-6 text-right">
        <?php if (!empty($publicationScopes)): ?>
          <button class="btn btn-sm btn-info" data-toggle="modal" data-target="#modal-skill-add"><i class="fas fa-plus mr-1"></i>Nouvelle compétence</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="alert alert-light border small">
      Source SQL : <code>sav_competences</code>. Gestion réservée aux profils <strong>PDG</strong> et <strong>chef de service</strong>, avec portée interne ou réseau selon le type de société.
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title"><?= count($items) ?> compétence(s)</h3></div>
      <div class="card-body p-0">
        <table class="table table-striped table-hover table-sm mb-0">
          <thead class="thead-light">
            <tr><th>Code</th><th>Nom</th><th>Description</th><th>Portée</th><th>Statut</th><th class="text-right" style="width:110px">Actions</th></tr>
          </thead>
          <tbody>
          <?php foreach ($items as $item): ?>
            <?php
              $isActive = !empty($item['skl_is_active']);
              $ownerId = (int) ($item['cmp_societe_proprietaire_id'] ?? $item['skl_owner_company_id'] ?? 0);
              $scope = (string) ($item['cmp_portee_code'] ?? $item['skl_scope_code'] ?? $item['skl_scope'] ?? ($ownerId > 0 ? 'interne' : 'plateforme'));
              $canEdit = $isSuperAdmin || ($ownerId > 0 && $ownerId === $activeCompanyId);
            ?>
            <tr class="<?= $isActive ? '' : 'text-muted' ?>">
              <td><code><?= $e($item['skl_code'] ?? '') ?></code></td>
              <td><strong><?= $e($item['skl_name'] ?? '') ?></strong></td>
              <td><small><?= $e($item['skl_description'] ?? '—') ?></small></td>
              <td><?= $scopeBadge($scope) ?><?php if (!empty($item['skl_company_name'])): ?><small class="text-muted ml-1"><?= $e($item['skl_company_name']) ?></small><?php endif; ?></td>
              <td><?= $isActive ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>' ?></td>
              <td class="text-right text-nowrap">
                <?php if ($canEdit): ?>
                  <a class="btn btn-xs btn-outline-secondary" href="/admin/skills/<?= (int) $item['skl_id'] ?>/edit" title="Modifier"><i class="fas fa-edit"></i></a>
                  <form method="post" action="/admin/skills/<?= (int) $item['skl_id'] ?>/toggle" style="display:inline">
                    <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                    <button type="submit" class="btn btn-xs <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?>" title="<?= $isActive ? 'Désactiver' : 'Activer' ?>"><i class="fas fa-<?= $isActive ? 'ban' : 'check' ?>"></i></button>
                  </form>
                <?php else: ?>
                  <span class="text-muted small">Lecture</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if ($items === []): ?><tr><td colspan="6" class="text-center text-muted p-3">Aucune compétence.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<div class="modal fade" id="modal-skill-add" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header bg-info text-white"><h5 class="modal-title"><i class="fas fa-star mr-2"></i>Nouvelle compétence</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
    <form method="post" action="/admin/skills/store">
      <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
      <div class="modal-body">
        <div class="form-group"><label>Code <span class="text-danger">*</span></label><input class="form-control form-control-sm" name="cmp_code" required placeholder="EX: DIAGNOSTIC_ELECTRONIQUE" style="text-transform:uppercase"></div>
        <div class="form-group"><label>Nom <span class="text-danger">*</span></label><input class="form-control form-control-sm" name="cmp_nom" required></div>
        <div class="form-group"><label>Description</label><textarea class="form-control form-control-sm" name="cmp_description" rows="3"></textarea></div>
        <div class="form-group"><label>Portée de publication</label><select class="form-control form-control-sm" name="cmp_portee_code"><?php foreach ($publicationScopes as $code => $label): ?><option value="<?= $e($code) ?>"><?= $e($label) ?></option><?php endforeach; ?></select><small class="form-text text-muted">La portée réseau dépend des rattachements constructeur/marque/importateur/concession.</small></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Annuler</button><button type="submit" class="btn btn-info">Créer</button></div>
    </form>
  </div></div>
</div>
