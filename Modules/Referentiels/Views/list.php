<?php defined('AUTOSAV_ROOT') or die;
$e = static fn(mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$headers = match ($type) {
  'pays' => ['Code ISO2', 'Code ISO3', 'Nom', 'Actif', 'Modifié'],
  'devises' => ['Code ISO', 'Nom', 'Symbole', 'Décimales', 'Active'],
  'fuseaux' => ['Nom IANA', 'Libellé', 'Actif', 'Modifié'],
  'tva' => ['Pays', 'Code', 'Nom', 'Taux', 'Période', 'Statut'],
  'statuts' => ['Domaine', 'Code', 'Libellé', 'Entité', 'Initial / Final', 'Transitions'],
  'transitions' => ['Domaine', 'Source', 'Cible', 'Permission', 'Options', 'Active'],
  default => [],
};
$idOf = static fn(array $r, string $type): int => match ($type) {
  'pays' => (int)($r['pay_id'] ?? 0), 'devises' => (int)($r['dev_id'] ?? 0), 'fuseaux' => (int)($r['fuh_id'] ?? 0),
  'tva' => (int)($r['tva_id'] ?? 0), 'statuts' => (int)($r['sta_id'] ?? 0), 'transitions' => (int)($r['tst_id'] ?? 0), default => 0,
};
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 mb-0"><?= $e($title ?? 'Référentiel') ?></h1>
      <p class="text-muted mb-0">Données de référence centralisées, sans valeurs codées en dur.</p>
    </div>
    <div>
      <a class="btn btn-outline-secondary" href="/referentiels">Référentiels</a>
      <a class="btn btn-primary" href="/referentiels/<?= $e($type) ?>/create">Créer</a>
    </div>
  </div>

  <div class="card card-outline card-info mb-3">
    <div class="card-body">
      <form method="get" class="row g-2">
        <div class="col-md-<?= in_array($type, ['statuts', 'transitions'], true) ? '4' : ($type === 'tva' ? '4' : '10') ?>">
          <label>Recherche</label>
          <input class="form-control" name="q" value="<?= $e($filters['q'] ?? '') ?>" placeholder="Code, nom, libellé…">
        </div>
        <?php if (in_array($type, ['statuts', 'transitions'], true)): ?>
          <div class="col-md-4">
            <label>Domaine</label>
            <select class="form-control" name="domaine">
              <option value="">Tous</option>
              <?php foreach (($domaines ?? []) as $domain): $name = (string)($domain['domaine'] ?? ''); ?>
                <option value="<?= $e($name) ?>" <?= (($filters['domaine'] ?? '') === $name) ? 'selected' : '' ?>><?= $e($name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
        <?php if ($type === 'tva'): ?>
          <div class="col-md-4">
            <label>Pays</label>
            <select class="form-control" name="pays_id">
              <option value="">Tous</option>
              <?php foreach (($pays ?? []) as $p): ?>
                <option value="<?= (int)$p['pay_id'] ?>" <?= ((string)($filters['pays_id'] ?? '') === (string)$p['pay_id']) ? 'selected' : '' ?>><?= $e($p['pay_nom'] ?? '') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary btn-block">Filtrer</button></div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body p-0 table-responsive">
      <table class="table table-sm table-hover mb-0">
        <thead><tr><?php foreach ($headers as $h): ?><th><?= $e($h) ?></th><?php endforeach; ?><th class="text-right">Actions</th></tr></thead>
        <tbody>
        <?php foreach (($rows ?? []) as $r): $id = $idOf($r, $type); ?>
          <tr>
            <?php if ($type === 'pays'): ?>
              <td><code><?= $e($r['pay_code_iso2'] ?? '') ?></code></td><td><code><?= $e($r['pay_code_iso3'] ?? '') ?></code></td><td><?= $e($r['pay_nom'] ?? '') ?></td><td><?= !empty($r['pay_est_actif']) ? 'Oui' : 'Non' ?></td><td><?= $e($r['pay_modifie_le'] ?? $r['pay_cree_le'] ?? '') ?></td>
            <?php elseif ($type === 'devises'): ?>
              <td><code><?= $e($r['dev_code_iso'] ?? '') ?></code></td><td><?= $e($r['dev_nom'] ?? '') ?></td><td><?= $e($r['dev_symbole'] ?? '') ?></td><td><?= (int)($r['dev_nombre_decimales'] ?? 0) ?></td><td><?= !empty($r['dev_est_active']) ? 'Oui' : 'Non' ?></td>
            <?php elseif ($type === 'fuseaux'): ?>
              <td><code><?= $e($r['fuh_nom_iana'] ?? '') ?></code></td><td><?= $e($r['fuh_libelle'] ?? '') ?></td><td><?= !empty($r['fuh_est_actif']) ? 'Oui' : 'Non' ?></td><td><?= $e($r['fuh_modifie_le'] ?? $r['fuh_cree_le'] ?? '') ?></td>
            <?php elseif ($type === 'tva'): ?>
              <td><?= $e($r['pay_nom'] ?? '') ?> <small class="text-muted"><?= $e($r['pay_code_iso2'] ?? '') ?></small></td><td><code><?= $e($r['tva_code'] ?? '') ?></code></td><td><?= $e($r['tva_nom'] ?? '') ?></td><td><?= $e($r['tva_taux'] ?? '') ?> %</td><td><?= $e($r['tva_debute_le'] ?? '') ?> → <?= $e($r['tva_termine_le'] ?? '—') ?></td><td><?= $e($r['statut_libelle'] ?? '-') ?></td>
            <?php elseif ($type === 'statuts'): ?>
              <td><code><?= $e($r['sta_domaine'] ?? '') ?></code></td><td><code><?= $e($r['sta_code'] ?? '') ?></code></td><td><?= $e($r['sta_libelle'] ?? '') ?></td><td><?= $e($r['sta_entite_table'] ?? '') ?></td><td><?= !empty($r['sta_est_initial']) ? 'Initial ' : '' ?><?= !empty($r['sta_est_final']) ? 'Final' : '' ?></td><td>↗ <?= (int)($r['transitions_sortantes'] ?? 0) ?> / ↘ <?= (int)($r['transitions_entrantes'] ?? 0) ?></td>
            <?php elseif ($type === 'transitions'): ?>
              <td><code><?= $e($r['tst_domaine'] ?? '') ?></code></td><td><?= $e($r['source_libelle'] ?? '') ?> <small><?= $e($r['source_code'] ?? '') ?></small></td><td><?= $e($r['cible_libelle'] ?? '') ?> <small><?= $e($r['cible_code'] ?? '') ?></small></td><td><code><?= $e($r['tst_permission_code'] ?? '') ?></code></td><td><?= !empty($r['tst_motif_obligatoire']) ? 'Motif ' : '' ?><?= !empty($r['tst_validation_requise']) ? 'Validation ' : '' ?><?= !empty($r['tst_notification_requise']) ? 'Notification' : '' ?></td><td><?= !empty($r['tst_est_active']) ? 'Oui' : 'Non' ?></td>
            <?php endif; ?>
            <td class="text-right">
              <a class="btn btn-sm btn-outline-primary" href="/referentiels/<?= $e($type) ?>/<?= $id ?>/edit">Modifier</a>
              <form method="post" action="/referentiels/<?= $e($type) ?>/<?= $id ?>/delete" class="d-inline" data-confirm="Supprimer logiquement cet élément ?">
                <input type="hidden" name="_csrf_token" value="<?= $e($csrf_token ?? '') ?>">
                <button class="btn btn-sm btn-outline-danger">Supprimer</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><tr><td colspan="<?= count($headers)+1 ?>" class="text-center text-muted py-4">Aucun élément.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
