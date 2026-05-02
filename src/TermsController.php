<section class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h1><?= htmlspecialchars($page_title ?? 'Fonctions') ?></h1>
    <a href="/functions/create" class="btn btn-primary">Nouvelle fonction</a>
  </div>
</section>
<section class="content">
  <div class="container-fluid">
    <div class="card">
      <div class="card-body p-0">
        <table class="table table-striped mb-0">
          <thead><tr><th>Code</th><th>Libelle</th><th>Portee</th><th>Statut</th><th class="text-right">Actions</th></tr></thead>
          <tbody>
          <?php foreach (($functions ?? []) as $function): ?>
            <tr>
              <td><?= htmlspecialchars($function['fnc_code']) ?></td>
              <td><?= htmlspecialchars($function['fnc_label']) ?></td>
              <td><?= !empty($function['fnc_is_global']) ? 'Globale' : htmlspecialchars($function['fnc_company_name'] ?? 'Structure') ?></td>
              <td><?= !empty($function['fnc_is_active']) ? 'Active' : 'Inactive' ?></td>
              <td class="text-right">
                <a class="btn btn-sm btn-outline-secondary" href="/functions/<?= (int) $function['fnc_id'] ?>/edit">Modifier</a>
                <form class="d-inline" method="post" action="/functions/<?= (int) $function['fnc_id'] ?>/toggle">
                  <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                  <button class="btn btn-sm btn-outline-warning"><?= !empty($function['fnc_is_active']) ? 'Desactiver' : 'Activer' ?></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (($functions ?? []) === []): ?>
            <tr><td colspan="5" class="text-center text-muted p-4">Aucune fonction disponible.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</section>
