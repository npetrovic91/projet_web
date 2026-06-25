<?php defined('AUTOSAV_ROOT') or die; ?>
<div class="container-fluid py-3">
  <h1 class="h3 mb-3">Nouvel email groupé</h1>
  <form method="post" action="/bulk-mail/store">
    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
    <div class="row">
      <div class="col-lg-8">
        <div class="card mb-3"><div class="card-header"><strong>Contenu</strong></div><div class="card-body">
          <div class="form-group">
            <label>Modèle email</label>
            <select class="form-control" name="modele_id" id="modele_id">
              <option value="">— Aucun, saisir un contenu manuel —</option>
              <?php foreach (($modeles ?? []) as $m): ?>
                <option value="<?= (int)$m['mel_id'] ?>"><?= htmlspecialchars((string)$m['mel_code']) ?> — <?= htmlspecialchars((string)$m['mel_sujet']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Sujet manuel</label>
            <input class="form-control" name="subject" placeholder="Ignoré si un modèle est sélectionné">
          </div>
          <div class="form-group">
            <label>Corps manuel</label>
            <textarea class="form-control" name="body_html" rows="12" placeholder="Ignoré si un modèle est sélectionné"></textarea>
            <small class="form-text text-muted">Variables : <code>{{firstname}}</code>, <code>{{lastname}}</code>, <code>{{fullname}}</code>, <code>{{email}}</code>, <code>{{societe}}</code>, <code>{{unsubscribe_url}}</code>.</small>
          </div>
        </div></div>
      </div>
      <div class="col-lg-4">
        <div class="card mb-3"><div class="card-header"><strong>Ciblage</strong></div><div class="card-body">
          <div class="form-group">
            <label>Destinataires</label>
            <select class="form-control" name="target">
              <option value="all">Tous les utilisateurs actifs avec société</option>
              <option value="by_company">Par société</option>
              <option value="by_role">Par rôle</option>
              <option value="by_role_and_company">Par rôle et société</option>
            </select>
          </div>
          <div class="form-group">
            <label>Société</label>
            <select class="form-control" name="filter_company_id">
              <option value="">— Toutes —</option>
              <?php foreach (($companies ?? []) as $c): ?>
                <option value="<?= (int)$c['soc_id'] ?>"><?= htmlspecialchars((string)$c['soc_nom']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Rôle</label>
            <select class="form-control" name="filter_role_id">
              <option value="">— Tous —</option>
              <?php foreach (($roles ?? []) as $r): ?>
                <option value="<?= (int)$r['rol_id'] ?>"><?= htmlspecialchars((string)$r['rol_nom']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Limite d’envoi par requête</label>
            <input class="form-control" type="number" min="1" max="500" name="max_recipients" value="200">
          </div>
        </div></div>
        <button class="btn btn-primary btn-block" data-confirm="L’envoi sera immédiat et journalisé. Continuer ?">Envoyer maintenant</button>
        <a class="btn btn-secondary btn-block" href="/bulk-mail">Annuler</a>
      </div>
    </div>
  </form>
</div>
