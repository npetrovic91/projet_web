<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Users\Models\UserComplementModel;
use Nenad\Autosav\Modules\Users\Services\UserService;

class UserController extends BaseController
{
    private UserService $users;

    public function __construct()
    {
        parent::__construct();
        $this->users = new UserService();
    }

    public function index(): void
    {
        $this->requirePermission('users.read');
        $filtres = [
            'search' => (string) $this->get('search', $this->get('recherche', '')),
            'statut' => (string) $this->get('statut', $this->get('status', '')),
            'societe_id' => (int) $this->get('societe_id', $this->get('company_id', 0)),
        ];
        $pagination = $this->users->lister($filtres, $this->idUtilisateurCourant(), max(1, (int) $this->get('page', 1)), 25);
        $form = $this->users->preparerFormulaire(null, $this->idUtilisateurCourant());
        $this->render('Users/Views/index', [
            'page_title' => 'Utilisateurs',
            'users' => $pagination['users'],
            'utilisateurs' => $pagination['utilisateurs'],
            'pagination' => $pagination,
            'filtres' => $filtres,
            'societes' => $form['societes'],
            'canCreateUser' => has_permission(['utilisateur.creer', 'users.create', 'users.manage', 'admin.users']),
            'csrf_token' => csrf_token(),
        ], 'main');
    }

    public function create(): void
    {
        $this->requirePermission('utilisateur.creer');
        $form = $this->users->preparerFormulaire(null, $this->idUtilisateurCourant());
        $this->render('Users/Views/create', [
            'page_title' => 'Nouvel utilisateur',
            'user' => [],
            'utilisateur' => [],
            'societes' => $form['societes'],
            'roles' => $form['roles'],
            'fonctions' => $form['fonctions'],
            'departements' => $form['departements'] ?? [],
            'services' => $form['services'] ?? [],
            'equipes' => $form['equipes'] ?? [],
            'competences' => $form['competences'] ?? [],
            'niveaux_competences' => $form['niveaux_competences'] ?? [],
            'certifications' => $form['certifications'] ?? [],
            'managers' => $form['managers'],
            'errors' => [],
            'erreurs' => [],
            'csrf_token' => csrf_token(),
        ], 'main');
    }

    public function store(): void
    {
        $this->requirePermission('utilisateur.creer');
        $this->validateCsrf();
        $result = $this->users->enregistrer(null, $_POST, $this->idUtilisateurCourant());
        if (($result['success'] ?? false) === true) {
            if (!empty($result['temporary_password'])) {
                $this->flash('warning', 'Mot de passe temporaire généré : ' . $result['temporary_password'] . ' — à communiquer par canal sécurisé.');
            } else {
                $this->flash('success', 'Utilisateur créé.');
            }
            $this->redirect('/users/' . (int) $result['id']);
        }
        $form = $this->users->preparerFormulaire(null, $this->idUtilisateurCourant());
        $this->render('Users/Views/create', [
            'page_title' => 'Nouvel utilisateur',
            'user' => $_POST,
            'utilisateur' => $_POST,
            'societes' => $form['societes'],
            'roles' => $form['roles'],
            'fonctions' => $form['fonctions'],
            'departements' => $form['departements'] ?? [],
            'services' => $form['services'] ?? [],
            'equipes' => $form['equipes'] ?? [],
            'competences' => $form['competences'] ?? [],
            'niveaux_competences' => $form['niveaux_competences'] ?? [],
            'certifications' => $form['certifications'] ?? [],
            'managers' => $form['managers'],
            'errors' => $result['errors'] ?? [],
            'erreurs' => $result['erreurs'] ?? [],
            'csrf_token' => csrf_token(),
        ], 'main', 422);
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $idUser = (int) $id;
        if (!$this->users->canViewUser($this->idUtilisateurCourant(), $idUser)) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $fiche = $this->users->fiche($idUser);
        if (!$fiche) {
            http_response_code(404);
            echo 'Utilisateur introuvable.';
            return;
        }
        $this->render('Users/Views/show', [
            'page_title' => $this->nomComplet($fiche['user']) ?: 'Utilisateur',
            'fiche' => $fiche,
            'user' => $fiche['user'],
            'utilisateur' => $fiche['user'],
            'csrf_token' => csrf_token(),
        ], 'main');
    }

    public function edit(string $id): void
    {
        $this->requirePermission('utilisateur.modifier');
        $idUser = (int) $id;
        if (!$this->users->canManageUser($this->idUtilisateurCourant(), $idUser)) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $form = $this->users->preparerFormulaire($idUser, $this->idUtilisateurCourant());
        if (!$form['fiche']) {
            http_response_code(404);
            echo 'Utilisateur introuvable.';
            return;
        }
        $this->render('Users/Views/edit', [
            'page_title' => 'Modifier utilisateur',
            'fiche' => $form['fiche'],
            'user' => $form['fiche']['user'],
            'utilisateur' => $form['fiche']['user'],
            'societes' => $form['societes'],
            'roles' => $form['roles'],
            'fonctions' => $form['fonctions'],
            'departements' => $form['departements'] ?? [],
            'services' => $form['services'] ?? [],
            'equipes' => $form['equipes'] ?? [],
            'competences' => $form['competences'] ?? [],
            'niveaux_competences' => $form['niveaux_competences'] ?? [],
            'certifications' => $form['certifications'] ?? [],
            'managers' => $form['managers'],
            'errors' => [],
            'erreurs' => [],
            'csrf_token' => csrf_token(),
        ], 'main');
    }

    public function update(string $id): void
    {
        $this->requirePermission('utilisateur.modifier');
        $this->validateCsrf();
        $idUser = (int) $id;
        if (!$this->users->canManageUser($this->idUtilisateurCourant(), $idUser)) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $result = $this->users->enregistrer($idUser, $_POST, $this->idUtilisateurCourant());
        if (($result['success'] ?? false) === true) {
            $this->flash('success', 'Utilisateur modifié.');
            $this->redirect('/users/' . $idUser);
        }
        $form = $this->users->preparerFormulaire($idUser, $this->idUtilisateurCourant());
        $fiche = $form['fiche'] ?? ['user' => []];
        $this->render('Users/Views/edit', [
            'page_title' => 'Modifier utilisateur',
            'fiche' => $fiche,
            'user' => array_merge($fiche['user'] ?? [], $_POST),
            'utilisateur' => array_merge($fiche['user'] ?? [], $_POST),
            'societes' => $form['societes'],
            'roles' => $form['roles'],
            'fonctions' => $form['fonctions'],
            'departements' => $form['departements'] ?? [],
            'services' => $form['services'] ?? [],
            'equipes' => $form['equipes'] ?? [],
            'competences' => $form['competences'] ?? [],
            'niveaux_competences' => $form['niveaux_competences'] ?? [],
            'certifications' => $form['certifications'] ?? [],
            'managers' => $form['managers'],
            'errors' => $result['errors'] ?? [],
            'erreurs' => $result['erreurs'] ?? [],
            'csrf_token' => csrf_token(),
        ], 'main', 422);
    }

    public function deactivate(string $id): void
    {
        $this->requirePermission('utilisateur.bloquer');
        $this->validateCsrf();
        $idUser = (int) $id;
        $this->assertManage($idUser);
        $this->users->desactiver($idUser, $this->idUtilisateurCourant());
        $this->flash('success', 'Utilisateur désactivé.');
        $this->redirect('/users/' . $idUser);
    }

    public function reactivate(string $id): void
    {
        $this->requirePermission('utilisateur.bloquer');
        $this->validateCsrf();
        $idUser = (int) $id;
        $this->assertManage($idUser);
        $this->users->reactiver($idUser, $this->idUtilisateurCourant());
        $this->flash('success', 'Utilisateur réactivé.');
        $this->redirect('/users/' . $idUser);
    }

    public function history(string $id): void
    {
        $this->show($id);
    }

    public function resendVerification(string $id): void
    {
        $this->requirePermission('utilisateur.modifier');
        $this->validateCsrf();
        $this->assertManage((int) $id);
        $this->flash('info', 'Réenvoi de vérification à traiter dans le lot Auth/Emails.');
        $this->redirect('/users/' . (int) $id);
    }

    public function inventoryAdd(string $id): void { $this->moduleNonAligne($id, 'Inventaire utilisateur'); }
    public function inventoryReturn(string $id, string $iid = ''): void { $this->moduleNonAligne($id, 'Retour inventaire utilisateur'); }
    public function leaveBalanceUpdate(string $id): void { $this->moduleNonAligne($id, 'Solde congés'); }
    public function leaveRequestAdd(string $id): void { $this->moduleNonAligne($id, 'Demande de congé'); }
    public function absenceAdd(string $id): void { $this->moduleNonAligne($id, 'Absence'); }
    public function warningAdd(string $id): void { $this->moduleNonAligne($id, 'Avertissement'); }

    // ════════════════════════════════════════════════════════════════
    // LOT40 — Infos complémentaires / Comptes bancaires / Mandats / Véhicules
    // ════════════════════════════════════════════════════════════════

    public function saveInfosCompl(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $utiId = (int) $id;
        $this->assertManage($utiId);
        $model = new UserComplementModel();
        $ok = $model->sauvegarderInfosComplementaires($utiId, $_POST, $this->idUtilisateurCourant());
        if ($this->isAjax()) { header('Content-Type: application/json; charset=UTF-8'); echo json_encode(['success' => $ok]); return; }
        $ok ? $this->flash()->success('Informations complémentaires enregistrées.') : $this->flash()->error('Erreur lors de l\'enregistrement.');
        $this->redirect('/users/' . $utiId . '#infos-complementaires');
    }

    public function addCompteBancaire(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $utiId = (int) $id;
        $this->assertManage($utiId);
        $model = new UserComplementModel();
        $newId = $model->ajouterCompteBancaire($utiId, $_POST, $this->idUtilisateurCourant());
        if ($this->isAjax()) { header('Content-Type: application/json; charset=UTF-8'); echo json_encode(['success' => $newId > 0, 'id' => $newId]); return; }
        $this->flash()->success('Compte bancaire ajouté.');
        $this->redirect('/users/' . $utiId . '#infos-bancaires');
    }

    public function deleteCompteBancaire(string $id, string $cid): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $utiId = (int) $id;
        $this->assertManage($utiId);
        $model = new UserComplementModel();
        $ok = $model->supprimerCompteBancaire((int) $cid, $utiId, $this->idUtilisateurCourant());
        if ($this->isAjax()) { header('Content-Type: application/json; charset=UTF-8'); echo json_encode(['success' => $ok]); return; }
        $this->flash()->success('Compte bancaire supprimé.');
        $this->redirect('/users/' . $utiId . '#infos-bancaires');
    }

    public function addMandat(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $utiId = (int) $id;
        $this->assertManage($utiId);
        $model = new UserComplementModel();
        $newId = $model->ajouterMandat($utiId, $_POST, $this->idUtilisateurCourant());
        if ($this->isAjax()) { header('Content-Type: application/json; charset=UTF-8'); echo json_encode(['success' => $newId > 0, 'id' => $newId]); return; }
        $this->flash()->success('Mandat ajouté.');
        $this->redirect('/users/' . $utiId . '#infos-bancaires');
    }

    public function deleteMandat(string $id, string $mid): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $utiId = (int) $id;
        $this->assertManage($utiId);
        $model = new UserComplementModel();
        $ok = $model->supprimerMandat((int) $mid, $utiId, $this->idUtilisateurCourant());
        if ($this->isAjax()) { header('Content-Type: application/json; charset=UTF-8'); echo json_encode(['success' => $ok]); return; }
        $this->flash()->success('Mandat supprimé.');
        $this->redirect('/users/' . $utiId . '#infos-bancaires');
    }

    public function addVehicule(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $utiId = (int) $id;
        $this->assertManage($utiId);
        $model = new UserComplementModel();
        $newId = $model->ajouterVehicule($utiId, $_POST, $this->idUtilisateurCourant());
        if ($this->isAjax()) { header('Content-Type: application/json; charset=UTF-8'); echo json_encode(['success' => $newId > 0, 'id' => $newId]); return; }
        $this->flash()->success('Véhicule ajouté au parc.');
        $this->redirect('/users/' . $utiId . '#parc-sav');
    }

    public function deleteVehicule(string $id, string $vid): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $utiId = (int) $id;
        $this->assertManage($utiId);
        $model = new UserComplementModel();
        $ok = $model->supprimerVehicule((int) $vid, $utiId, $this->idUtilisateurCourant());
        if ($this->isAjax()) { header('Content-Type: application/json; charset=UTF-8'); echo json_encode(['success' => $ok]); return; }
        $this->flash()->success('Véhicule retiré du parc.');
        $this->redirect('/users/' . $utiId . '#parc-sav');
    }

    private function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    private function moduleNonAligne(string $id, string $libelle): void
    {
        $this->requirePermission('utilisateur.modifier');
        $this->validateCsrf();
        $this->assertManage((int) $id);
        $this->flash('warning', $libelle . ' : fonctionnalité dépendante d’un module métier non encore aligné sur la base SQL.');
        $this->redirect('/users/' . (int) $id);
    }

    private function assertManage(int $idUser): void
    {
        if (!$this->users->canManageUser($this->idUtilisateurCourant(), $idUser)) {
            http_response_code(403);
            echo 'Accès refusé.';
            exit;
        }
    }

    private function idUtilisateurCourant(): int
    {
        return (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
    }

    private function nomComplet(array $user): string
    {
        return trim((string) ($user['pui_prenom'] ?? $user['use_firstname'] ?? '') . ' ' . (string) ($user['pui_nom'] ?? $user['use_lastname'] ?? ''));
    }
}