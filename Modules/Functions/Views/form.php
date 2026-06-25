<?php
declare(strict_types=1);
$function = $function ?? [];
$companyTypes = $companyTypes ?? [];
$publicationScopes = $publicationScopes ?? ['interne' => 'Interne société'];
$errors = $errors ?? [];
$action = $action ?? '/functions/store';
$csrf = $csrf_token ?? '';
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$v = static fn(string $key, string $fallback = ''): string => (string) ($function[$key] ?? ($fallback !== '' ? ($function[$fallback] ?? '') : ''));
$currentType = (int) ($function['fon_type_societe_id'] ?? $function['fnc_company_type_id'] ?? 0);
$currentScope = (string) ($function['fon_portee_code'] ?? $function['fnc_scope_code'] ?? $function['fnc_scope'] ?? 'interne');
if (!isset($publicationScopes[$currentScope])) {
    $currentScope = array_key_first($publicationScopes) ?: 'interne';
}
?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1><i class="fas fa-briefcase mr-2"></i><?= $e($page_title ?? 'Fonction métier') ?></h1></div>
      <div class="col-sm-6 text-right"><a class="btn btn-secondary btn-sm" href="/functions"><i class="fas fa-arrow-left mr-1"></i>Retour</a></div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="card card-primary card-outline">
      <div class="card-body">
        <form method="post" action="<?= $e($action) ?>">
          <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
          <div class="row">
            <div class="col-md-4 form-group">
              <label>Code <span class="text-danger">*</span></label>
              <input class="form-control" name="fon_code" required maxlength="100" style="text-transform:uppercase" value="<?= $e($v('fon_code', 'fnc_code')) ?>" placeholder="EX: TECHNICIEN">
            </div>
            <div class="col-md-8 form-group">
              <label>Nom <span class="text-danger">*</span></label>
              <input class="form-control" name="fon_nom" required maxlength="120" value="<?= $e($v('fon_nom', 'fnc_label')) ?>" placeholder="Ex: Technicien">
            </div>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea class="form-control" name="fon_description" rows="4"><?= $e($v('fon_description', 'fnc_description')) ?></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 form-group">
              <label>Type de société cible</label>
              <select class="form-control" name="fon_type_societe_id">
                <option value="">— Tous les types / non précisé —</option>
                <?php foreach ($companyTypes as $type): ?>
                  <option value="<?= (int) $type['tso_id'] ?>" <?= $currentType === (int) $type['tso_id'] ? 'selected' : '' ?>>
                    <?= $e($type['tso_nom']) ?><?= !empty($type['tso_code']) ? ' (' . $e($type['tso_code']) . ')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6 form-group">
              <label>Portée de publication</label>
              <select class="form-control" name="fon_portee_code">
                <?php foreach ($publicationScopes as $code => $label): ?>
                  <option value="<?= $e($code) ?>" <?= $currentScope === (string) $code ? 'selected' : '' ?>><?= $e($label) ?></option>
                <?php endforeach; ?>
              </select>
              <small class="form-text text-muted">Concession : interne uniquement. Importateur : réseau rattaché. Constructeur/marque : réseau visible par importateurs et concessions rattachés.</small>
            </div>
          </div>
          <div class="alert alert-info small mb-3">
            Une fonction décrit le métier réel d’un utilisateur. Elle ne remplace pas les rôles applicatifs ni les permissions techniques.
          </div>
          <button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Enregistrer</button>
        </form>
      </div>
    </div>
  </div>
</section>
