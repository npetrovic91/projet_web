<?php defined('AUTOSAV_ROOT') or die;
$e = static fn(mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$isEdit = !empty($row);
$pk = ['departements'=>'dep_id','secteurs'=>'sec_id','services'=>'srv_id','equipes'=>'equ_id'][$type];
$prefix = ['departements'=>'dep','secteurs'=>'sec','services'=>'srv','equipes'=>'equ'][$type];
$id = (int)($row[$pk] ?? 0);
$action = $isEdit ? "/organisation/{$type}/{$id}/update" : "/organisation/{$type}/store";
$label = $labels[$type] ?? 'Structure';
$selectedSocieteId = (int)($row[$prefix . '_societe_id'] ?? $societe_id ?? 0);
$selectedUserIds = array_map('intval', $selected_user_ids ?? []);
$responsableUserId = (int)($responsable_user_id ?? 0);
$assignable = in_array($type, ['departements', 'services', 'equipes'], true);
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><?= $isEdit ? 'Modifier' : 'Creer' ?> - <?= $e($label) ?></h1>
    <a class="btn btn-outline-secondary" href="/organisation/<?= $e($type) ?>">Retour</a>
  </div>
  <form method="post" action="<?= $e($action) ?>" class="card card-body">
    <?= $csrfField ?? csrf_field() ?>
    <?php if ($assignable): ?><input type="hidden" name="affectations_present" value="1"><?php endif; ?>
    <div class="row">
      <div class="col-md-6 form-group">
        <label>Societe *</label>
        <select class="form-control" name="societe_id" required>
          <option value="">Selectionner</option>
          <?php foreach (($societes ?? []) as $s): ?>
            <option value="<?= (int)$s['soc_id'] ?>" <?= ($selectedSocieteId === (int)$s['soc_id']) ? 'selected' : '' ?>><?= $e(($s['soc_code'] ? $s['soc_code'].' - ' : '').$s['soc_nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 form-group">
        <label>Code</label>
        <input class="form-control" name="code" value="<?= $e($row[$prefix.'_code'] ?? '') ?>" maxlength="50">
      </div>
      <div class="col-md-3 form-group">
        <label>Statut</label>
        <select class="form-control" name="statut_id">
          <option value="">Aucun</option>
          <?php foreach (($statuts ?? []) as $st): ?>
            <option value="<?= (int)$st['sta_id'] ?>" <?= ((int)($row[$prefix.'_statut_id'] ?? 0) === (int)$st['sta_id']) ? 'selected' : '' ?>><?= $e(($st['sta_domaine'] ?? '').' / '.($st['sta_libelle'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Nom *</label>
      <input class="form-control" name="nom" value="<?= $e($row[$prefix.'_nom'] ?? '') ?>" required maxlength="120">
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea class="form-control" name="description" rows="4"><?= $e($row[$prefix.'_description'] ?? '') ?></textarea>
    </div>

    <?php if ($assignable): ?>
      <hr>
      <div class="row">
        <div class="col-md-5 form-group">
          <label>Responsable</label>
          <select class="form-control" name="responsable_user_id">
            <option value="">Aucun responsable</option>
            <?php foreach (($utilisateurs ?? []) as $u): $uid = (int)($u['utilisateur_id'] ?? 0); $userLabel = trim((string)($u['nom_complet'] ?? '')) ?: ($u['uti_identifiant'] ?? $u['uti_email'] ?? ('#'.$uid)); ?>
              <option value="<?= $uid ?>" <?= $responsableUserId === $uid ? 'selected' : '' ?>><?= $e($userLabel) ?><?= !empty($u['uti_email']) ? ' - '.$e($u['uti_email']) : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-7 form-group">
          <label>Personnes affectees</label>
          <select class="form-control" name="utilisateur_ids[]" multiple size="8">
            <?php foreach (($utilisateurs ?? []) as $u): $uid = (int)($u['utilisateur_id'] ?? 0); $userLabel = trim((string)($u['nom_complet'] ?? '')) ?: ($u['uti_identifiant'] ?? $u['uti_email'] ?? ('#'.$uid)); ?>
              <option value="<?= $uid ?>" <?= in_array($uid, $selectedUserIds, true) ? 'selected' : '' ?>><?= $e($userLabel) ?><?= !empty($u['uti_email']) ? ' - '.$e($u['uti_email']) : '' ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    <?php endif; ?>

    <div class="text-right"><button class="btn btn-primary">Enregistrer</button></div>
  </form>
</div>
