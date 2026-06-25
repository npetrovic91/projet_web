<?php
declare(strict_types=1);
$job = $job ?? [];
$companyTypes = $companyTypes ?? [];
$errors = $errors ?? [];
$action = $action ?? '/jobs/store';
$csrf = $csrf_token ?? '';
$isEdit = !empty($job['job_id'] ?? $job['fon_id'] ?? null);
$isSuperAdmin = (bool) ($isSuperAdmin ?? false);
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$v = static fn(string $key, string $fallback = ''): string => (string) ($job[$key] ?? $job[$fallback] ?? '');
$currentType = (int) ($job['job_company_type_id'] ?? $job['fon_type_societe_id'] ?? 0);
$isGlobal = (int) ($job['job_is_global'] ?? 0) === 1 || empty($job['job_company_id'] ?? $job['fon_societe_proprietaire_id'] ?? null);
?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1><i class="fas fa-tools mr-2"></i><?= $e($page_title ?? 'Métier') ?></h1></div>
      <div class="col-sm-6 text-right"><a class="btn btn-secondary btn-sm" href="/jobs"><i class="fas fa-arrow-left mr-1"></i>Retour</a></div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= $e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="alert alert-info">
      Dans le modèle SQL actuel, un métier est une <strong>fonction métier</strong>. Ce module est donc un alias compatible vers <code>sav_fonctions</code>.
    </div>

    <div class="card card-primary card-outline">
      <div class="card-body">
        <form method="post" action="<?= $e($action) ?>">
          <input type="hidden" name="_csrf_token" value="<?= $e($csrf) ?>">
          <div class="row">
            <div class="col-md-4 form-group">
              <label>Code <span class="text-danger">*</span></label>
              <input class="form-control" name="job_code" required maxlength="100" style="text-transform:uppercase" value="<?= $e($v('job_code', 'fon_code')) ?>" placeholder="EX: TECHNICIEN">
            </div>
            <div class="col-md-8 form-group">
              <label>Libellé <span class="text-danger">*</span></label>
              <input class="form-control" name="job_label" required maxlength="120" value="<?= $e($v('job_label', 'fon_nom')) ?>" placeholder="Ex: Technicien">
            </div>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea class="form-control" name="job_description" rows="4"><?= $e($v('job_description', 'fon_description')) ?></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 form-group">
              <label>Type de société cible</label>
              <select class="form-control" name="job_company_type_id">
                <option value="">— Tous les types / non précisé —</option>
                <?php foreach ($companyTypes as $type): ?>
                  <option value="<?= (int) $type['tso_id'] ?>" <?= $currentType === (int) $type['tso_id'] ? 'selected' : '' ?>>
                    <?= $e($type['tso_nom']) ?><?= !empty($type['tso_code']) ? ' (' . $e($type['tso_code']) . ')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php if ($isSuperAdmin): ?>
              <div class="col-md-6 form-group d-flex align-items-end">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="job_is_global" value="1" id="job_is_global" <?= $isGlobal ? 'checked' : '' ?>>
                  <label class="form-check-label" for="job_is_global">Métier global du noyau</label>
                </div>
              </div>
            <?php endif; ?>
          </div>
          <button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i>Enregistrer</button>
        </form>
      </div>
    </div>
  </div>
</section>
