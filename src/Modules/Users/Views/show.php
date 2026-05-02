<?php $user = $user ?? []; ?>
<section class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h1><?= htmlspecialchars($page_title ?? 'Utilisateur') ?></h1>
    <div>
      <a class="btn btn-secondary" href="/users">Retour</a>
      <a class="btn btn-primary" href="/users/<?= (int) $user['use_id'] ?>/edit">Modifier</a>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-4">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Identite</h3></div>
          <div class="card-body">
            <p><strong><?= htmlspecialchars($user['use_firstname'] . ' ' . $user['use_lastname']) ?></strong></p>
            <p><?= htmlspecialchars($user['use_email']) ?></p>
            <p>Role : <?= htmlspecialchars($user['primary_role_label'] ?? 'Sans role') ?></p>
            <p>Statut : <?= !empty($user['use_is_active']) ? 'Actif' : 'Inactif' ?></p>
            <p>Email : <?= !empty($user['use_email_verified_at']) ? 'verifie' : 'en attente' ?></p>
          </div>
          <div class="card-footer">
            <?php if (!empty($user['use_is_active'])): ?>
              <form method="post" action="/users/<?= (int) $user['use_id'] ?>/deactivate">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <button class="btn btn-outline-danger btn-block">Desactiver</button>
              </form>
            <?php else: ?>
              <form method="post" action="/users/<?= (int) $user['use_id'] ?>/reactivate">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <button class="btn btn-outline-success btn-block">Reactiver</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Entreprises rattachees</h3></div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead><tr><th>Entreprise</th><th>Type</th><th>Depuis</th><th>Principal</th></tr></thead>
              <tbody>
                <?php foreach (($user['companies'] ?? []) as $company): ?>
                  <tr>
                    <td><?= htmlspecialchars($company['com_name']) ?></td>
                    <td><?= htmlspecialchars($company['type_label'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($company['ucm_joined_at'] ?? '-') ?></td>
                    <td><?= !empty($company['ucm_is_primary']) ? 'Oui' : 'Non' ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h3 class="card-title">Hierarchie</h3></div>
          <div class="card-body">
            <h4>Managers</h4>
            <ul>
              <?php foreach (($managers ?? []) as $manager): ?>
                <li><?= htmlspecialchars($manager['use_firstname'] . ' ' . $manager['use_lastname']) ?><?= !empty($manager['company_name']) ? ' - ' . htmlspecialchars($manager['company_name']) : '' ?></li>
              <?php endforeach; ?>
              <?php if (($managers ?? []) === []): ?><li>Aucun manager defini.</li><?php endif; ?>
            </ul>
            <h4>Subordonnes directs</h4>
            <ul>
              <?php foreach (($subordinates ?? []) as $subordinate): ?>
                <li><a href="/users/<?= (int) $subordinate['uhi_user_id'] ?>"><?= htmlspecialchars($subordinate['use_firstname'] . ' ' . $subordinate['use_lastname']) ?></a></li>
              <?php endforeach; ?>
              <?php if (($subordinates ?? []) === []): ?><li>Aucun subordonne direct.</li><?php endif; ?>
            </ul>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><h3 class="card-title">Historique professionnel</h3></div>
          <div class="card-body p-0">
            <table class="table table-sm mb-0">
              <thead><tr><th>Entreprise</th><th>Poste</th><th>Debut</th><th>Fin</th></tr></thead>
              <tbody>
                <?php foreach (($history ?? []) as $entry): ?>
                  <tr>
                    <td><?= htmlspecialchars($entry['com_name']) ?></td>
                    <td><?= htmlspecialchars($entry['uch_job_title'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($entry['uch_started_at'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($entry['uch_ended_at'] ?? 'En cours') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
