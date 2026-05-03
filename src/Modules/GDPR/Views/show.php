<section class="content-header">
  <div class="container-fluid"><h1><?= htmlspecialchars($page_title ?? 'Demande RGPD') ?></h1></div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-7">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Demande</h3></div>
          <div class="card-body">
            <p><strong>Utilisateur :</strong> <?= htmlspecialchars($request['use_email']) ?></p>
            <p><strong>Type :</strong> <?= htmlspecialchars($request['grq_type']) ?></p>
            <p><strong>Statut :</strong> <?= htmlspecialchars($request['grq_status']) ?></p>
            <p><strong>Message :</strong><br><?= nl2br(htmlspecialchars($request['grq_message'] ?? '')) ?></p>
            <?php if (!empty($request['grq_response'])): ?>
              <p><strong>Reponse :</strong><br><?= nl2br(htmlspecialchars($request['grq_response'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($request['grq_rejection_reason'])): ?>
              <p><strong>Motif de rejet :</strong><br><?= nl2br(htmlspecialchars($request['grq_rejection_reason'])) ?></p>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-md-5">
        <div class="card">
          <div class="card-header"><h3 class="card-title">Actions</h3></div>
          <div class="card-body">
            <form method="post" action="/gdpr/<?= (int) $request['grq_id'] ?>/accept" class="mb-3">
              <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
              <label>Reponse</label>
              <textarea class="form-control mb-2" name="response">Demande acceptee et prise en charge.</textarea>
              <button class="btn btn-success">Accepter</button>
            </form>
            <form method="post" action="/gdpr/<?= (int) $request['grq_id'] ?>/reject" class="mb-3">
              <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
              <label>Motif de rejet</label>
              <textarea class="form-control mb-2" name="reason"></textarea>
              <button class="btn btn-warning">Rejeter</button>
            </form>
            <a class="btn btn-info btn-block mb-3" href="/gdpr/<?= (int) $request['grq_id'] ?>/export">Exporter les donnees</a>
            <form method="post" action="/gdpr/<?= (int) $request['grq_id'] ?>/anonymize">
              <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
              <label>Motif d anonymisation</label>
              <textarea class="form-control mb-2" name="reason">Anonymisation sur demande RGPD apres controle.</textarea>
              <button class="btn btn-danger btn-block">Anonymiser l utilisateur</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
