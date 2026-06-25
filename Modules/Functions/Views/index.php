<?php
declare(strict_types=1);
$functions = $functions ?? [];
$csrf = $csrf_token ?? '';
$isSuperAdmin = (bool) ($isSuperAdmin ?? false);
$activeCompanyId = (int) ($companyId ?? $activeCompanyId ?? 0);
$publicationScopes = $publicationScopes ?? [];
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
      <div class="col-sm-6"><h1><i class="fas fa-briefcase mr-2"></i>Fonctions métier</h1></div>
      <div class="col-sm-6 text-right">
        <?php if (!empty($publicationScopes)): ?>
          <a class="btn btn-primary btn-sm" href="/functions/create"><i class="fas fa-plus mr-1"></i>Nouvelle fonction</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <?php $flash = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); ?>
    <?php foreach ($flash as $type => $messages): $class = $type === 'error' ? 'danger' : $type; ?>
      <?php foreach ((array) $messages as $message): ?>
        <div class="alert alert-<?= $e($class) ?> alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= $e($message) ?></div>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="alert alert-light border small">
      Gestion réservée aux profils <strong>PDG</strong> et <strong>chef de service</strong>. Les éléments réseau sont visibles uniquement dans le périmètre rattaché.
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title"><?= count($functions) ?> fonction(s)</h3></div>
      <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0">
          <thead>
            <tr>
              <th>Code</th>
              <th>Nom</th>
              <th>Portée</th>
              <th>Type société</th>
              <th>Statut</th>
              <th class="text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($functions as $function): ?>
              <?php
                $active = (bool) ($function['fnc_is_active'] ?? true);
                $ownerId = (int) ($function['fon_societe_proprietaire_id'] ?? $function['fnc_owner_company_id'] ?? 0);
                $scope = (string) ($function['fon_portee_code'] ?? $function['fnc_scope_code'] ?? $function['fnc_scope'] ?? ($ownerId > 0 ? 'interne' : 'plateforme'));
                $canEdit = $isSuperAdmin || ($ownerId > 0 && $ownerId === $activeCompanyId);
              ?>
              <tr class="<?= $active ? '' : 'text-muted' ?>">
                <td><code><?= $e($function['fon_code'] ?? $function['fnc_code'] ?? '') ?></code></td>
                <td>
                  <strong><?= $e($function['fon_nom'] ?? $function['fnc_label'] ?? '') ?></strong>
                  <?php if (!empty($function['fon_description'] ?? $function['fnc_description'] ?? '')): ?>
                    <br><small class="text-muted"><?= $e($function['fon_description'] ?? $function['fnc_description']) ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <?= $scopeBadge($scope) ?>
                  <small class="text-muted ml-1"><?= $e($function['fnc_company_name'] ?? $function['societe_proprietaire_nom'] ?? ($scope === 'plateforme' ? 'Global' : 'Société')) ?></small>
                </td>
                <td><?= $e($function['fnc_company_type_name'] ?? '—') ?></td>
                <td><?= $active ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-secondary">Inactif</span>' ?></td>
                <td class="text-right text-nowrap">
                  <?php if ($canEdit): ?>
                    <a class="btn btn-sm btn-outline-primary" href="/functions/<?= (int) $function['fon_id'] ?>/edit"><i class="fas fa-edit"></i></a>
                    <form method="post" action="/functions/<?= (int) $function['fon_id'] ?>/toggle" style="display:inline" data-confirm="Changer le statut de cette fonction ?">
                      <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
                      <button class="btn btn-sm <?= $active ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit"><i class="fas fa-<?= $active ? 'ban' : 'check' ?>"></i></button>
                    </form>
                  <?php else: ?>
                    <span class="text-muted small">Lecture</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$functions): ?>
              <tr><td colspan="6" class="text-center text-muted p-4">Aucune fonction métier.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
