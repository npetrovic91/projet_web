<?php declare(strict_types=1);
$h = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$permission = $permission ?? [];
$value = static fn(string $key, mixed $default = '') => $permission[$key] ?? $default;
?>
<section class="content-header"><div class="container-fluid d-flex justify-content-between align-items-center"><h1><?= $h($page_title ?? 'Permission') ?></h1><a href="/permissions" class="btn btn-outline-secondary btn-sm">Retour</a></div></section>
<section class="content"><div class="container-fluid">
  <?php if (!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= $h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <form method="post" action="<?= $h($action) ?>">
    <input type="hidden" name="_csrf_token" value="<?= $h($csrf_token ?? '') ?>">
    <div class="card card-primary card-outline">
      <div class="card-body">
        <div class="row">
          <div class="form-group col-md-5"><label>Code permission</label><input name="per_code" class="form-control" required value="<?= $h($value('per_code')) ?>" placeholder="ex: utilisateur.creer"></div>
          <div class="form-group col-md-4"><label>Module</label><select name="per_module_id" class="form-control"><option value="">Noyau / global</option><?php foreach (($modules ?? []) as $m): ?><option value="<?= (int)$m['mod_id'] ?>" <?= ((string)$value('per_module_id') === (string)$m['mod_id']) ? 'selected' : '' ?>><?= $h($m['mod_nom']) ?></option><?php endforeach; ?></select></div>
          <div class="form-group col-md-3"><label>Statut</label><select name="per_statut_id" class="form-control"><?php foreach (($statuts ?? []) as $st): ?><option value="<?= (int)$st['sta_id'] ?>" <?= ((string)$value('per_statut_id', 1) === (string)$st['sta_id']) ? 'selected' : '' ?>><?= $h($st['sta_libelle']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-group"><label>Description</label><textarea name="per_description" class="form-control" rows="4"><?= $h($value('per_description')) ?></textarea></div>
      </div>
      <div class="card-footer text-right"><button class="btn btn-primary" type="submit">Enregistrer</button></div>
    </div>
  </form>
</div></section>
