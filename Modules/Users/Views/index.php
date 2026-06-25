<?php
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$users = $users ?? $utilisateurs ?? [];
$filtres = $filtres ?? [];
?>
<div class="card card-outline card-primary">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0">Utilisateurs</h3>
        <?php if (($canCreateUser ?? false) || (function_exists('has_permission') && has_permission(['utilisateur.creer', 'users.create', 'users.manage', 'admin.users']))): ?>
            <a href="<?= url('/users/create') ?>" class="btn btn-primary btn-sm">Nouvel utilisateur</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <form method="get" action="<?= url('/users') ?>" class="row g-2 mb-3">
            <div class="col-md-4"><input class="form-control" name="search" placeholder="Recherche nom, prénom, email" value="<?= $e($filtres['search'] ?? '') ?>"></div>
            <div class="col-md-3">
                <select class="form-control" name="societe_id">
                    <option value="0">Toutes les sociétés</option>
                    <?php foreach (($societes ?? []) as $societe): ?>
                        <option value="<?= (int) $societe['soc_id'] ?>" <?= (int) ($filtres['societe_id'] ?? 0) === (int) $societe['soc_id'] ? 'selected' : '' ?>><?= $e($societe['soc_nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-control" name="statut">
                    <option value="">Tous statuts</option>
                    <?php foreach (['actif'=>'Actif','inactif'=>'Inactif','suspendu'=>'Suspendu','bloque_securite'=>'Bloqué sécurité'] as $code => $label): ?>
                        <option value="<?= $e($code) ?>" <?= ($filtres['statut'] ?? '') === $code ? 'selected' : '' ?>><?= $e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-secondary w-100" type="submit">Filtrer</button></div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead><tr><th>Utilisateur</th><th>Sociétés</th><th>Rôles</th><th>Statut</th><th>Dernière connexion</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Aucun utilisateur trouvé.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong><?= $e(trim(($user['pui_prenom'] ?? '') . ' ' . ($user['pui_nom'] ?? '')) ?: ($user['uti_email'] ?? '')) ?></strong><br><small class="text-muted"><?= $e($user['uti_email'] ?? '') ?></small></td>
                        <td><?= $e($user['societes_noms'] ?? $user['societe_active_nom'] ?? '—') ?></td>
                        <td><?= $e($user['roles_noms'] ?? '—') ?></td>
                        <td><span class="badge <?= ($user['statut_code'] ?? '') === 'actif' ? 'bg-success' : 'bg-secondary' ?>"><?= $e($user['statut_libelle'] ?? $user['statut_code'] ?? '—') ?></span></td>
                        <td><?= $e($user['uti_derniere_connexion_le'] ?? '—') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= url('/users/' . (int) $user['uti_id']) ?>">Voir</a>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= url('/users/' . (int) $user['uti_id'] . '/edit') ?>">Modifier</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
