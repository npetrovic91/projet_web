<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Abonnements\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Abonnements\Services\AbonnementService;

class AbonnementController extends BaseController
{
    private AbonnementService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AbonnementService();
    }

    public function index(): void
    {
        $this->requirePermission('abonnements.read');
        $filters = [
            'q' => trim((string)$this->get('q', '')),
            'societe_id' => (int)$this->get('societe_id', $this->activeCompanyId() ?? 0) ?: null,
            'formule_id' => (int)$this->get('formule_id', 0) ?: null,
            'statut_abonnement_id' => (int)$this->get('statut_abonnement_id', 0) ?: null,
        ];
        $this->render('Abonnements/Views/index', [
            'pageTitle' => 'Abonnements sociétés',
            'stats' => $this->service->tableauDeBord($filters['societe_id'])['stats'],
            'abonnements' => $this->service->abonnements($filters),
            'filters' => $filters,
            'refs' => $this->service->references(),
        ]);
    }

    /**
     * Cahier des charges (ACC-001) : souscription en une étape (abonnement
     * + espace applicatif) pour une société non abonnée, avec affichage de
     * l'historique conservé (relations, contacts, emails reçus) comme
     * preuve qu'aucune donnée n'est perdue.
     */
    public function souscrireForm(): void
    {
        $this->requirePermission('abonnements.manage');
        $societeId = (int) $this->get('societe_id', 0);
        if ($societeId <= 0) {
            $this->flash()->error('Société invalide.');
            $this->redirect('/abonnements');
        }
        $this->render('Abonnements/Views/souscrire', [
            'pageTitle' => 'Souscrire un abonnement',
            'societeId' => $societeId,
            'historique' => $this->service->historiqueConserve($societeId),
            'refs' => $this->service->references(),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function souscrire(): void
    {
        $this->requirePermission('abonnements.manage');
        $this->validateCsrf();

        $societeId = (int) $this->request->post('societe_id', 0);
        $formuleId = (int) $this->request->post('fab_formule_id', 0);
        if ($societeId <= 0 || $formuleId <= 0) {
            $this->flash()->error('Société et formule sont obligatoires.');
            $this->redirect('/abonnements/souscrire?societe_id=' . $societeId);
        }

        $resultat = $this->service->souscrireSociete($societeId, $formuleId, $this->userId(), client_ip());
        if ($resultat['success']) {
            $this->flash()->success(sprintf(
                'Société abonnée. Historique conservé : %d relation(s), %d contact(s), %d email(s) reçu(s).',
                $resultat['historique']['relations'],
                $resultat['historique']['contacts'],
                $resultat['historique']['emails_recus']
            ));
        } else {
            $this->flash()->error('Souscription impossible : ' . implode(' ', $resultat['errors']));
        }
        $this->redirect('/abonnements');
    }

    public function exportJson(): void
    {
        $this->requirePermission('abonnements.read');
        $societeId = (int)$this->get('societe_id', $this->activeCompanyId() ?? 0) ?: null;
        $this->json(true, $this->service->export($societeId), 'Export abonnements / espaces / modules sociétés.');
    }

    public function create(): void
    {
        $this->requirePermission('abonnements.manage');
        $this->render('Abonnements/Views/abonnement_form', [
            'pageTitle' => 'Créer un abonnement société',
            'abonnement' => null,
            'refs' => $this->service->references(),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('abonnements.manage');
        $abonnement = $this->service->abonnement((int)$id);
        if (!$abonnement) {
            $this->flash()->error('Abonnement introuvable.');
            $this->redirect('/abonnements');
        }
        $this->render('Abonnements/Views/abonnement_form', [
            'pageTitle' => 'Modifier un abonnement société',
            'abonnement' => $abonnement,
            'refs' => $this->service->references(),
        ]);
    }

    public function store(): void { $this->saveAbonnement(null); }
    public function update(string $id): void { $this->saveAbonnement((int)$id); }

    public function delete(string $id): void
    {
        $this->requirePermission('abonnements.manage');
        $this->validateCsrf();
        $resultat = $this->service->supprimerAbonnement((int)$id, $this->userId(), client_ip());
        $resultat['success'] ? $this->flash()->success($resultat['message']) : $this->flash()->error(implode(' ', $resultat['errors']));
        $this->redirect('/abonnements');
    }

    public function formules(): void
    {
        $this->requirePermission('abonnements.read');
        $filters = ['q' => trim((string)$this->get('q', ''))];
        $this->render('Abonnements/Views/formules', [
            'pageTitle' => 'Formules d’abonnement',
            'formules' => $this->service->formules($filters),
            'filters' => $filters,
            'refs' => $this->service->references(),
        ]);
    }

    public function createFormule(): void
    {
        $this->requirePermission('abonnements.manage');
        $this->render('Abonnements/Views/formule_form', [
            'pageTitle' => 'Créer une formule d’abonnement',
            'formule' => null,
            'refs' => $this->service->references(),
        ]);
    }

    public function editFormule(string $id): void
    {
        $this->requirePermission('abonnements.manage');
        $formule = $this->service->formule((int)$id);
        if (!$formule) {
            $this->flash()->error('Formule introuvable.');
            $this->redirect('/abonnements/formules');
        }
        $this->render('Abonnements/Views/formule_form', [
            'pageTitle' => 'Modifier une formule d’abonnement',
            'formule' => $formule,
            'refs' => $this->service->references(),
        ]);
    }

    public function storeFormule(): void { $this->saveFormule(null); }
    public function updateFormule(string $id): void { $this->saveFormule((int)$id); }

    public function deleteFormule(string $id): void
    {
        $this->requirePermission('abonnements.manage');
        $this->validateCsrf();
        $resultat = $this->service->supprimerFormule((int)$id, $this->userId(), client_ip());
        $resultat['success'] ? $this->flash()->success($resultat['message']) : $this->flash()->error(implode(' ', $resultat['errors']));
        $this->redirect('/abonnements/formules');
    }

    public function espaces(): void
    {
        $this->requirePermission('abonnements.read');
        $filters = [
            'societe_id' => (int)$this->get('societe_id', $this->activeCompanyId() ?? 0) ?: null,
            'bloque' => trim((string)$this->get('bloque', '')),
        ];
        $this->render('Abonnements/Views/espaces', [
            'pageTitle' => 'Espaces applicatifs',
            'espaces' => $this->service->espaces($filters),
            'filters' => $filters,
            'refs' => $this->service->references(),
        ]);
    }

    public function createEspace(): void
    {
        $this->requirePermission('abonnements.manage');
        $this->render('Abonnements/Views/espace_form', [
            'pageTitle' => 'Créer un espace applicatif',
            'espace' => null,
            'refs' => $this->service->references(),
        ]);
    }

    public function editEspace(string $id): void
    {
        $this->requirePermission('abonnements.manage');
        $espace = $this->service->espace((int)$id);
        if (!$espace) {
            $this->flash()->error('Espace applicatif introuvable.');
            $this->redirect('/abonnements/espaces');
        }
        $this->render('Abonnements/Views/espace_form', [
            'pageTitle' => 'Modifier un espace applicatif',
            'espace' => $espace,
            'refs' => $this->service->references(),
        ]);
    }

    public function storeEspace(): void { $this->saveEspace(null); }
    public function updateEspace(string $id): void { $this->saveEspace((int)$id); }

    public function deleteEspace(string $id): void
    {
        $this->requirePermission('abonnements.manage');
        $this->validateCsrf();
        $resultat = $this->service->supprimerEspace((int)$id, $this->userId(), client_ip());
        $resultat['success'] ? $this->flash()->success($resultat['message']) : $this->flash()->error(implode(' ', $resultat['errors']));
        $this->redirect('/abonnements/espaces');
    }

    public function modulesSocietes(): void
    {
        $this->requirePermission('abonnements.read');
        $filters = [
            'societe_id' => (int)$this->get('societe_id', $this->activeCompanyId() ?? 0) ?: null,
            'module_id' => (int)$this->get('module_id', 0) ?: null,
        ];
        $this->render('Abonnements/Views/modules_societes', [
            'pageTitle' => 'Modules activés par société',
            'modulesSocietes' => $this->service->modulesSocietes($filters),
            'filters' => $filters,
            'refs' => $this->service->references(),
        ]);
    }

    public function createModuleSociete(): void
    {
        $this->requirePermission('abonnements.manage');
        $this->render('Abonnements/Views/module_societe_form', [
            'pageTitle' => 'Activer un module pour une société',
            'moduleSociete' => null,
            'refs' => $this->service->references(),
        ]);
    }

    public function editModuleSociete(string $id): void
    {
        $this->requirePermission('abonnements.manage');
        $moduleSociete = $this->service->moduleSociete((int)$id);
        if (!$moduleSociete) {
            $this->flash()->error('Activation module/société introuvable.');
            $this->redirect('/abonnements/modules-societes');
        }
        $this->render('Abonnements/Views/module_societe_form', [
            'pageTitle' => 'Modifier l’activation module/société',
            'moduleSociete' => $moduleSociete,
            'refs' => $this->service->references(),
        ]);
    }

    public function storeModuleSociete(): void { $this->saveModuleSociete(null); }
    public function updateModuleSociete(string $id): void { $this->saveModuleSociete((int)$id); }

    public function deleteModuleSociete(string $id): void
    {
        $this->requirePermission('abonnements.manage');
        $this->validateCsrf();
        $resultat = $this->service->supprimerModuleSociete((int)$id, $this->userId(), client_ip());
        $resultat['success'] ? $this->flash()->success($resultat['message']) : $this->flash()->error(implode(' ', $resultat['errors']));
        $this->redirect('/abonnements/modules-societes');
    }

    private function saveAbonnement(?int $id): void
    {
        $this->requirePermission('abonnements.manage');
        $this->validateCsrf();
        $resultat = $this->service->enregistrerAbonnement($_POST, $id, $this->userId(), client_ip());
        if ($resultat['success']) {
            $this->flash()->success($resultat['message']);
            $this->redirect('/abonnements');
        }
        $this->flash()->error(implode(' ', $resultat['errors']));
        $this->redirect($id ? '/abonnements/' . $id . '/edit' : '/abonnements/create');
    }

    private function saveFormule(?int $id): void
    {
        $this->requirePermission('abonnements.manage');
        $this->validateCsrf();
        $resultat = $this->service->enregistrerFormule($_POST, $id, $this->userId(), client_ip());
        if ($resultat['success']) {
            $this->flash()->success($resultat['message']);
            $this->redirect('/abonnements/formules');
        }
        $this->flash()->error(implode(' ', $resultat['errors']));
        $this->redirect($id ? '/abonnements/formules/' . $id . '/edit' : '/abonnements/formules/create');
    }

    private function saveEspace(?int $id): void
    {
        $this->requirePermission('abonnements.manage');
        $this->validateCsrf();
        $resultat = $this->service->enregistrerEspace($_POST, $id, $this->userId(), client_ip());
        if ($resultat['success']) {
            $this->flash()->success($resultat['message']);
            $this->redirect('/abonnements/espaces');
        }
        $this->flash()->error(implode(' ', $resultat['errors']));
        $this->redirect($id ? '/abonnements/espaces/' . $id . '/edit' : '/abonnements/espaces/create');
    }

    private function saveModuleSociete(?int $id): void
    {
        $this->requirePermission('abonnements.manage');
        $this->validateCsrf();
        $resultat = $this->service->enregistrerModuleSociete($_POST, $id, $this->userId(), client_ip());
        if ($resultat['success']) {
            $this->flash()->success($resultat['message']);
            $this->redirect('/abonnements/modules-societes');
        }
        $this->flash()->error(implode(' ', $resultat['errors']));
        $this->redirect($id ? '/abonnements/modules-societes/' . $id . '/edit' : '/abonnements/modules-societes/create');
    }
}
