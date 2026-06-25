<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Validation\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Validation\Services\ValidationService;

class ValidationController extends BaseController
{
    private ValidationService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ValidationService();
    }

    public function index(): void
    {
        $this->requirePermission('validation.consulter');
        $this->requireExportAllowed();
        $filters = [
            'q' => (string) $this->get('q', ''),
            'societe_id' => (int) $this->get('societe_id', 0),
            'type' => (string) $this->get('type', ''),
            'decision' => (string) $this->get('decision', ''),
        ];
        $this->render('Validation/Views/index', $this->service->dashboard($filters) + [
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Validations / opérations sensibles',
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('validation.gerer');
        $this->render('Validation/Views/form_demande', [
            'demande' => null,
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Nouvelle demande de validation',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('validation.gerer');
        $this->validateCsrf();
        try {
            $id = $this->service->creerDemande($_POST, (int) $this->userId());
            $this->flash()->success('Demande de validation créée.');
            $this->redirect('/validations/' . $id);
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/validations/create');
        }
    }

    public function show(string $id): void
    {
        $this->requirePermission('validation.consulter');
        $this->requireExportAllowed();
        $demande = $this->service->demande((int) $id);
        if (!$demande) {
            $this->flash()->error('Demande introuvable.');
            $this->redirect('/validations');
        }
        $this->render('Validation/Views/show_demande', [
            'demande' => $demande,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Demande de validation',
        ]);
    }

    public function decide(string $id): void
    {
        $this->requirePermission('validation.gerer');
        $this->validateCsrf();
        try {
            $this->service->deciderDemande(
                (int) $id,
                (string) $this->post('decision', ''),
                (string) $this->post('commentaire', ''),
                (int) $this->userId()
            );
            $this->flash()->success('Décision enregistrée.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/validations/' . (int) $id);
    }

    public function delete(string $id): void
    {
        $this->requirePermission('validation.gerer');
        $this->validateCsrf();
        $this->service->supprimerDemande((int) $id, (int) $this->userId());
        $this->flash()->success('Demande supprimée logiquement.');
        $this->redirect('/validations');
    }

    public function rules(): void
    {
        $this->requirePermission('validation.consulter');
        $this->requireExportAllowed();
        $filters = [
            'q' => (string) $this->get('q', ''),
            'societe_id' => (int) $this->get('societe_id', 0),
            'type_operation' => (string) $this->get('type_operation', ''),
        ];
        $this->render('Validation/Views/rules', [
            'regles' => $this->service->regles($filters),
            'refs' => $this->service->refs(),
            'filters' => $filters,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Règles de validation',
        ]);
    }

    public function createRule(): void
    {
        $this->requirePermission('validation.gerer');
        $this->render('Validation/Views/form_rule', [
            'regle' => null,
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Nouvelle règle de validation',
        ]);
    }

    public function storeRule(): void
    {
        $this->requirePermission('validation.gerer');
        $this->validateCsrf();
        try {
            $id = $this->service->creerRegle($_POST, (int) $this->userId());
            $this->flash()->success('Règle créée.');
            $this->redirect('/validation-rules/' . $id . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/validation-rules/create');
        }
    }

    public function editRule(string $id): void
    {
        $this->requirePermission('validation.gerer');
        $regle = $this->service->regle((int) $id);
        if (!$regle) {
            $this->flash()->error('Règle introuvable.');
            $this->redirect('/validation-rules');
        }
        $this->render('Validation/Views/form_rule', [
            'regle' => $regle,
            'refs' => $this->service->refs(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Modifier règle de validation',
        ]);
    }

    public function updateRule(string $id): void
    {
        $this->requirePermission('validation.gerer');
        $this->validateCsrf();
        try {
            $this->service->modifierRegle((int) $id, $_POST, (int) $this->userId());
            $this->flash()->success('Règle mise à jour.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/validation-rules/' . (int) $id . '/edit');
    }

    public function deleteRule(string $id): void
    {
        $this->requirePermission('validation.gerer');
        $this->validateCsrf();
        $this->service->supprimerRegle((int) $id, (int) $this->userId());
        $this->flash()->success('Règle supprimée logiquement.');
        $this->redirect('/validation-rules');
    }

    public function exportJson(): never
    {
        $this->requirePermission('validation.consulter');
        $this->requireExportAllowed();
        $this->json(true, $this->service->export(), 'Export validations');
    }
}
