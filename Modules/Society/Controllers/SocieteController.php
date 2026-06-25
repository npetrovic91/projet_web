<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Society\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Society\Services\ServiceAccesSocietes;
use Nenad\Autosav\Modules\Society\Services\ServiceSocietes;

class SocieteController extends BaseController
{
    private ServiceSocietes $societes;
    private ServiceAccesSocietes $acces;

    public function __construct()
    {
        parent::__construct();
        $this->societes = new ServiceSocietes();
        $this->acces = new ServiceAccesSocietes();
    }

    public function index(): void
    {
        $this->requireAuth();
        if (!$this->acces->peutLister()) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }

        $filtres = [
            'type' => (string) $this->get('type', ''),
            'recherche' => (string) $this->get('search', $this->get('recherche', '')),
            'statut' => (string) $this->get('status', $this->get('statut', '')),
            'active' => (string) $this->get('is_active', $this->get('active', '')),
        ];

        $pagination = $this->societes->lister($filtres, max(1, (int) $this->get('page', 1)), 25);
        $this->render('Society/Views/liste', [
            'page_title' => 'Sociétés',
            'societes' => $pagination['lignes'],
            'companies' => $pagination['lignes'],
            'types' => $this->societes->types(),
            'filtres' => $filtres,
            'filters' => $filtres,
            'pagination' => $pagination,
            'canCreateSociete' => $this->acces->peutCreer(),
        ], 'main');
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $idSociete = (int) $id;
        if (!$this->acces->peutVoirSociete($idSociete)) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $this->requireSuperAdminJustification($idSociete);
        $fiche = $this->societes->ficheComplete($idSociete);
        if (!$fiche) {
            http_response_code(404);
            echo 'Société introuvable.';
            return;
        }
        // LOT40 — référentiels pour les selects des onglets infos complémentaires
        $db = \Nenad\Autosav\Core\Database\Database::getInstance();
        $refNatures = $db->fetchAll('SELECT * FROM sav_natures_clients WHERE nac_supprime_le IS NULL ORDER BY nac_code ASC');
        $refModes   = $db->fetchAll('SELECT * FROM sav_modes_reglement WHERE mrg_supprime_le IS NULL ORDER BY mrg_code ASC');
        $refRemises = $db->fetchAll('SELECT * FROM sav_codes_remise WHERE cre_supprime_le IS NULL ORDER BY cre_code ASC');
        $refTva     = $db->fetchAll('SELECT * FROM sav_taux_tva WHERE tva_supprime_le IS NULL ORDER BY tva_taux ASC');

        $this->render('Society/Views/profil', [
            'page_title'             => $fiche['societe']['soc_nom'] ?? $fiche['societe']['com_name'] ?? 'Société',
            'societe'                => $fiche['societe'],
            'company'                => $fiche['societe'],
            'marques'                => $fiche['marques'],
            'brands'                 => $fiche['marques'],
            'relations'              => $fiche['relations'],
            'enfants'                => $fiche['enfants'],
            // LOT40
            'infos_complementaires'  => $fiche['infos_complementaires'] ?? [],
            'comptes_bancaires'      => $fiche['comptes_bancaires'] ?? [],
            'mandats'                => $fiche['mandats'] ?? [],
            'vehicules'              => $fiche['vehicules'] ?? [],
            'natures_clients'        => $refNatures,
            'modes_reglement'        => $refModes,
            'codes_remise'           => $refRemises,
            'taux_tva'               => $refTva,
            'marques_disponibles'    => $this->societes->marquesDisponibles(),
            // fin LOT40
            'societes_disponibles'   => $this->societes->societesActives(),
            'csrf_token'             => csrf_token(),
            'canEdit'                => $this->acces->peutModifier($idSociete),
        ], 'main');
    }

    public function create(): void
    {
        $this->requireAuth();
        if (!$this->acces->peutCreer()) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $this->render('Society/Views/creation', [
            'page_title' => 'Nouvelle société',
            'societe' => [],
            'company' => [],
            'types' => $this->societes->types(),
            'holdings' => $this->societes->holdings(),
            'parents' => $this->societes->societesActives(),
            'importateurs' => $this->societes->societesActives('importateur'),
            'mode' => 'creation',
            'errors' => [],
            'erreurs' => [],
            'csrf_token' => csrf_token(),
        ], 'main');
    }

    public function store(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        if (!$this->acces->peutCreer()) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $resultat = $this->societes->enregistrer(null, $_POST, $this->idUtilisateurCourant());
        if ($resultat['success']) {
            $this->redirect('/companies/' . (int) $resultat['id']);
        }
        $this->render('Society/Views/creation', [
            'page_title' => 'Nouvelle société',
            'societe' => $_POST,
            'company' => $_POST,
            'types' => $this->societes->types(),
            'holdings' => $this->societes->holdings(),
            'parents' => $this->societes->societesActives(),
            'importateurs' => $this->societes->societesActives('importateur'),
            'mode' => 'creation',
            'errors' => $resultat['errors'],
            'erreurs' => $resultat['erreurs'],
            'csrf_token' => csrf_token(),
        ], 'main', 422);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $idSociete = (int) $id;
        if (!$this->acces->peutModifier($idSociete)) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $this->requireSuperAdminJustification($idSociete);
        $fiche = $this->societes->ficheComplete($idSociete);
        if (!$fiche) {
            http_response_code(404);
            echo 'Société introuvable.';
            return;
        }
        $societeAvecImportateur = $fiche['societe'];
        $societeAvecImportateur['soc_importateur_id'] = $this->societes->importateurDeConcession($idSociete);

        $this->render('Society/Views/modification', [
            'page_title' => 'Modifier société',
            'societe' => $societeAvecImportateur,
            'company' => $societeAvecImportateur,
            'types' => $this->societes->types(),
            'holdings' => $this->societes->holdings(),
            'parents' => $this->societes->societesActives(),
            'importateurs' => $this->societes->societesActives('importateur'),
            'mode' => 'modification',
            'errors' => [],
            'erreurs' => [],
            'csrf_token' => csrf_token(),
            'canEdit' => $this->acces->peutModifier($idSociete),
        ], 'main');
    }

    public function update(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $idSociete = (int) $id;
        if (!$this->acces->peutModifier($idSociete)) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $resultat = $this->societes->enregistrer($idSociete, $_POST, $this->idUtilisateurCourant());
        if ($resultat['success']) {
            $this->redirect('/companies/' . $idSociete);
        }
        $this->render('Society/Views/modification', [
            'page_title' => 'Modifier société',
            'societe' => array_merge($_POST, ['com_id' => $idSociete]),
            'company' => array_merge($_POST, ['com_id' => $idSociete]),
            'types' => $this->societes->types(),
            'holdings' => $this->societes->holdings(),
            'parents' => $this->societes->societesActives(),
            'importateurs' => $this->societes->societesActives('importateur'),
            'mode' => 'modification',
            'errors' => $resultat['errors'],
            'erreurs' => $resultat['erreurs'],
            'csrf_token' => csrf_token(),
        ], 'main', 422);
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $idSociete = (int) $id;
        if (!$this->acces->peutSupprimer($idSociete)) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $this->societes->supprimer($idSociete, $this->idUtilisateurCourant());
        $this->redirect('/companies');
    }

    public function deactivate(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $idSociete = (int) $id;
        if (!$this->acces->peutModifier($idSociete)) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $this->societes->desactiver($idSociete, $this->idUtilisateurCourant());
        $this->redirect('/companies/' . $idSociete);
    }

    public function restore(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $idSociete = (int) $id;
        if (!$this->acces->peutModifier($idSociete)) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $this->societes->reactiver($idSociete, $this->idUtilisateurCourant());
        $this->redirect('/companies/' . $idSociete);
    }

    public function addRelation(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $parent = (int) ($_POST['parent_id'] ?? $_POST['id_parent'] ?? 0);
        $enfant = (int) ($_POST['child_id'] ?? $_POST['id_enfant'] ?? 0);
        $type = (string) ($_POST['relation_type'] ?? $_POST['type_relation'] ?? 'groupe');
        if (!$this->acces->peutModifier($enfant)) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $this->societes->ajouterRelation($parent, $enfant, $type, $this->idUtilisateurCourant());
        $this->redirect('/companies/' . $enfant);
    }

    public function removeRelation(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->societes->retirerRelation((int) $id, $this->idUtilisateurCourant());
        $this->redirect('/companies');
    }

    public function attachBrand(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $idSociete = (int) ($_POST['company_id'] ?? $_POST['societe_id'] ?? 0);
        if (!$this->acces->peutModifier($idSociete)) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $this->societes->attacherMarque($idSociete, (int) ($_POST['brand_id'] ?? $_POST['marque_id']), $this->idUtilisateurCourant(), !empty($_POST['is_primary']) || !empty($_POST['principale']));
        $this->redirect('/companies/' . $idSociete);
    }

    public function detachBrand(): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $idSociete = (int) ($_POST['company_id'] ?? $_POST['societe_id'] ?? 0);
        if (!$this->acces->peutModifier($idSociete)) {
            http_response_code(403);
            echo 'Accès refusé.';
            return;
        }
        $this->societes->detacherMarque($idSociete, (int) ($_POST['brand_id'] ?? $_POST['marque_id']), $this->idUtilisateurCourant());
        $this->redirect('/companies/' . $idSociete);
    }

    public function types(): void
    {
        $this->requireTypeAdministration();
        $this->render('Society/Views/types', [
            'page_title' => 'Types de sociétés',
            'types' => $this->societes->typesAdministrables(),
        ], 'main');
    }

    public function createType(): void
    {
        $this->requireTypeAdministration();
        $this->render('Society/Views/type_form', [
            'page_title' => 'Créer un type de société',
            'type' => [],
        ], 'main');
    }

    public function editType(string $id): void
    {
        $this->requireTypeAdministration();
        $type = $this->societes->typeAdministrable((int) $id);
        if (!$type) {
            $this->flash()->error('Type de société introuvable.');
            $this->redirect('/companies/types');
        }
        $this->render('Society/Views/type_form', [
            'page_title' => 'Modifier un type de société',
            'type' => $type,
        ], 'main');
    }

    public function storeType(): void
    {
        $this->saveType(null);
    }

    public function updateType(string $id): void
    {
        $this->saveType((int) $id);
    }

    public function deleteType(string $id): void
    {
        $this->requireTypeAdministration();
        $this->validateCsrf();
        try {
            $this->societes->supprimerType((int) $id, $this->idUtilisateurCourant());
            $this->flash()->success('Type de société supprimé.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/companies/types');
    }

    private function saveType(?int $id): void
    {
        $this->requireTypeAdministration();
        $this->validateCsrf();
        try {
            $this->societes->enregistrerType($_POST, $id, $this->idUtilisateurCourant());
            $this->flash()->success($id === null ? 'Type de société créé.' : 'Type de société modifié.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/companies/types');
    }

    private function requireTypeAdministration(): void
    {
        $this->requireAuth();
        $this->requireRole(['super_administrateur', 'super_admin']);
    }

    // ════════════════════════════════════════════════════════════════
    // LOT40 — Infos complémentaires / Comptes bancaires / Mandats / Véhicules
    // ════════════════════════════════════════════════════════════════

    public function saveInfosCompl(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $socId = (int) $id;
        if (!$this->acces->peutModifier($socId)) { http_response_code(403); echo 'Accès refusé.'; return; }
        $ok = $this->societes->sauvegarderInfosComplementaires($socId, $_POST, $this->idUtilisateurCourant());
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => $ok]);
            return;
        }
        if ($ok) { $this->flash()->success('Informations complémentaires enregistrées.'); }
        else      { $this->flash()->error('Erreur lors de l\'enregistrement.'); }
        $this->redirect('/companies/' . $socId . '#infos-complementaires');
    }

    public function addCompteBancaire(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $socId = (int) $id;
        if (!$this->acces->peutModifier($socId)) { http_response_code(403); echo 'Accès refusé.'; return; }
        $newId = $this->societes->ajouterCompteBancaire($socId, $_POST, $this->idUtilisateurCourant());
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => $newId > 0, 'id' => $newId]);
            return;
        }
        $this->flash()->success('Compte bancaire ajouté.');
        $this->redirect('/companies/' . $socId . '#infos-bancaires');
    }

    public function deleteCompteBancaire(string $id, string $cid): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $socId = (int) $id;
        if (!$this->acces->peutModifier($socId)) { http_response_code(403); echo 'Accès refusé.'; return; }
        $ok = $this->societes->supprimerCompteBancaire((int) $cid, $socId, $this->idUtilisateurCourant());
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => $ok]);
            return;
        }
        $this->flash()->success('Compte bancaire supprimé.');
        $this->redirect('/companies/' . $socId . '#infos-bancaires');
    }

    public function addMandat(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $socId = (int) $id;
        if (!$this->acces->peutModifier($socId)) { http_response_code(403); echo 'Accès refusé.'; return; }
        $newId = $this->societes->ajouterMandat($socId, $_POST, $this->idUtilisateurCourant());
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => $newId > 0, 'id' => $newId]);
            return;
        }
        $this->flash()->success('Mandat ajouté.');
        $this->redirect('/companies/' . $socId . '#infos-bancaires');
    }

    public function updateMandat(string $id, string $mid): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $socId = (int) $id;
        if (!$this->acces->peutModifier($socId)) { http_response_code(403); echo 'Accès refusé.'; return; }
        $ok = $this->societes->mettreAJourMandat((int) $mid, $socId, $_POST, $this->idUtilisateurCourant());
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => $ok]);
            return;
        }
        $this->flash()->success('Mandat mis à jour.');
        $this->redirect('/companies/' . $socId . '#infos-bancaires');
    }

    public function deleteMandat(string $id, string $mid): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $socId = (int) $id;
        if (!$this->acces->peutModifier($socId)) { http_response_code(403); echo 'Accès refusé.'; return; }
        $ok = $this->societes->supprimerMandat((int) $mid, $socId, $this->idUtilisateurCourant());
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => $ok]);
            return;
        }
        $this->flash()->success('Mandat supprimé.');
        $this->redirect('/companies/' . $socId . '#infos-bancaires');
    }

    public function addVehicule(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $socId = (int) $id;
        if (!$this->acces->peutModifier($socId)) { http_response_code(403); echo 'Accès refusé.'; return; }
        $newId = $this->societes->ajouterVehicule($socId, $_POST, $this->idUtilisateurCourant());
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => $newId > 0, 'id' => $newId]);
            return;
        }
        $this->flash()->success('Véhicule ajouté au parc.');
        $this->redirect('/companies/' . $socId . '#parc-sav');
    }

    public function deleteVehicule(string $id, string $vid): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $socId = (int) $id;
        if (!$this->acces->peutModifier($socId)) { http_response_code(403); echo 'Accès refusé.'; return; }
        $ok = $this->societes->supprimerVehicule((int) $vid, $socId, $this->idUtilisateurCourant());
        if ($this->isAjax()) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => $ok]);
            return;
        }
        $this->flash()->success('Véhicule retiré du parc.');
        $this->redirect('/companies/' . $socId . '#parc-sav');
    }

    private function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    private function idUtilisateurCourant(): int
    {
        return (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
    }
}