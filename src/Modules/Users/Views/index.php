<?php
$filters = $filters ?? [];
$users = $users ?? [];
?>
<section class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h1><?= htmlspecialchars($page_title ?? 'Utilisateurs') ?></h1>
    <a class="btn btn-primary" href="/users/create">Nouvel utilisateur</a>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="card card-outline card-primary">
      <div class="card-header"><h3 class="card-title">Filtres</h3></div>
      <div class="card-body">
        <form method="get" action="/users" class="row">
          <div class="col-md-4 form-group">
            <label>Recherche</label>
            <input class="form-control" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
          </div>
          <div class="col-md-3 form-group">
            <label>Role</label>
            <select class="form-control" name="role_code">
              <option value="">Tous</option>
              <?php foreach (($roles ?? []) as $role): ?>
                <option value="<?= htmlspecialchars($role['rol_code']) ?>" <?= ($filters['role_code'] ?? '') === $role['rol_code'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($role['rol_label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 form-group">
            <label>Statut</label>
            <select class="form-control" name="is_active">
              <option value="">Tous</option>
              <option value="1" <?= ($filters['is_active'] ?? '') === '1' ? 'selected' : '' ?>>Actif</option>
              <option value="0" <?= ($filters['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Inactif</option>
            </select>
          </div>
          <div class="col-md-2 form-group d-flex align-items-end">
            <button class="btn btn-secondary btn-block" type="submit">Filtrer</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3 class="card-title"><?= (int) ($total ?? 0) ?> utilisateur(s)</h3>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover mb-0">
            <thead>
              <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Role principal</th>
                <th>Entreprise active</th>
                <th>Statut</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
              <tr>
                <td><?= htmlspecialchars($user['use_lastname'] . ' ' . $user['use_firstname']) ?></td>
                <td><?= htmlspecialchars($user['use_email']) ?></td>
                <td><?= htmlspecialchars($user['primary_role_label'] ?? 'Sans role') ?></td>
                <td><?= htmlspecialchars($user['active_company_name'] ?? '-') ?></td>
                <td><?= !empty($user['use_is_active']) ? 'Actif' : 'Inactif' ?></td>
                <td class="text-right">
                  <a class="btn btn-sm btn-outline-info" href="/users/<?= (int) $user['use_id'] ?>">Voir</a>
                  <a class="btn btn-sm btn-outline-secondary" href="/users/<?= (int) $user['use_id'] ?>/edit">Modifier</a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if ($users === []): ?>
              <tr><td colspan="6" class="text-center text-muted p-4">Aucun utilisateur trouve.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
