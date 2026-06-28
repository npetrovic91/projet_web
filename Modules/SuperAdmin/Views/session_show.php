<?php
declare(strict_types=1);
/** @var array $session */

$estSensible = static function (string $cle): bool {
    $cle = mb_strtolower($cle);
    foreach (['token', 'csrf', 'password', 'mot_de_passe', 'secret'] as $motif) {
        if (str_contains($cle, $motif)) {
            return true;
        }
    }
    return false;
};

$rendreValeur = static function (mixed $valeur) use (&$rendreValeur, $estSensible): string {
    if (is_array($valeur)) {
        $html = '<table class="table table-sm table-borderless mb-0">';
        foreach ($valeur as $cle => $sousValeur) {
            $cleTexte = (string) $cle;
            $valeurAffichee = $estSensible($cleTexte) ? '<span class="text-muted">(masqué)</span>' : $rendreValeur($sousValeur);
            $html .= '<tr><td class="text-muted" style="width:200px">' . htmlspecialchars($cleTexte, ENT_QUOTES, 'UTF-8') . '</td><td>' . $valeurAffichee . '</td></tr>';
        }
        $html .= '</table>';
        return $html;
    }
    if (is_bool($valeur)) {
        return $valeur ? '<span class="badge badge-success">true</span>' : '<span class="badge badge-secondary">false</span>';
    }
    if ($valeur === null) {
        return '<span class="text-muted">NULL</span>';
    }
    return '<code>' . htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8') . '</code>';
};
?>
<section class="content-header"><div class="container-fluid"><h1>Session <code><?= htmlspecialchars((string) $session['id'], ENT_QUOTES, 'UTF-8') ?></code></h1></div></section>
<section class="content"><div class="container-fluid">
    <div class="alert alert-info">Lecture seule. Les clés contenant <code>token</code>, <code>csrf</code>, <code>password</code> ou <code>secret</code> sont masquées par précaution, même pour le super-administrateur.</div>

    <div class="row mb-3">
        <div class="col-md-4"><div class="small-box bg-light"><div class="inner">
            <h3><?= htmlspecialchars(date('Y-m-d H:i:s', (int) $session['modifie_le']), ENT_QUOTES, 'UTF-8') ?></h3>
            <p>Dernière activité</p>
        </div></div></div>
        <div class="col-md-4"><div class="small-box bg-light"><div class="inner">
            <h3><?= (int) $session['taille_octets'] ?> o</h3>
            <p>Taille du fichier</p>
        </div></div></div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Contenu décodé</h3></div>
        <div class="card-body table-responsive p-0">
            <?php if ($session['donnees'] === []): ?>
                <p class="text-muted p-3 mb-0">Session vide.</p>
            <?php else: ?>
                <?= $rendreValeur($session['donnees']) ?>
            <?php endif; ?>
        </div>
    </div>

    <a class="btn btn-secondary mt-3" href="<?= url('/super-admin/sessions') ?>">Retour à la liste</a>
</div></section>
