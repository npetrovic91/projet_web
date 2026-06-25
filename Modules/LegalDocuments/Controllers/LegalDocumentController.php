<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\LegalDocuments\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\LegalDocuments\Services\LegalDocumentService;

class LegalDocumentController extends BaseController
{
    private LegalDocumentService $documents;

    public function __construct()
    {
        parent::__construct();
        $this->documents = new LegalDocumentService();
    }

    public function index(): void
    {
        $this->requirePermission('legal_documents.read');
        $data = $this->documents->dashboard([
            'q' => (string)$this->get('q', ''),
            'type' => (string)$this->get('type', ''),
            'company_id' => (int)$this->get('company_id', 0),
        ]);
        $this->render('LegalDocuments/Views/index', $data + [
            'filters' => ['q' => (string)$this->get('q', ''), 'type' => (string)$this->get('type', ''), 'company_id' => (int)$this->get('company_id', 0)],
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Documents juridiques',
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('legal_documents.manage');
        $data = $this->documents->dashboard([]);
        $this->render('LegalDocuments/Views/form', $data + [
            'document' => null,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Nouveau document juridique',
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('legal_documents.manage');
        $this->validateCsrf();
        try {
            $id = $this->documents->creer($_POST, (int)$this->userId());
            $this->flash()->success('Document juridique créé.');
            $this->redirect('/legal-documents/' . $id);
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect('/legal-documents/create');
        }
    }

    public function show(string $id): void
    {
        $this->requirePermission('legal_documents.read');
        $document = $this->documents->trouver((int)$id);
        if (!$document) {
            $this->flash()->error('Document introuvable.');
            $this->redirect('/legal-documents');
        }
        $data = $this->documents->dashboard([]);
        $this->render('LegalDocuments/Views/show', [
            'document' => $document,
            'links' => $this->documents->liensSocietes((int)$id),
            'acceptations' => $this->documents->acceptations((int)$id),
            'societes' => $data['societes'],
            'statuts' => $data['statuts'],
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Document juridique',
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('legal_documents.manage');
        $document = $this->documents->trouver((int)$id);
        if (!$document) {
            $this->flash()->error('Document introuvable.');
            $this->redirect('/legal-documents');
        }
        $data = $this->documents->dashboard([]);
        $this->render('LegalDocuments/Views/form', $data + [
            'document' => $document,
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Modifier document juridique',
        ]);
    }

    public function update(string $id): void
    {
        $this->requirePermission('legal_documents.manage');
        $this->validateCsrf();
        try {
            $this->documents->modifier((int)$id, $_POST, (int)$this->userId());
            $this->flash()->success('Document mis à jour.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/legal-documents/' . (int)$id . '/edit');
    }

    public function delete(string $id): void
    {
        $this->requirePermission('legal_documents.manage');
        $this->validateCsrf();
        $this->documents->supprimer((int)$id, (int)$this->userId());
        $this->flash()->success('Document supprimé logiquement.');
        $this->redirect('/legal-documents');
    }

    public function linkCompany(string $id): void
    {
        $this->requirePermission('legal_documents.manage');
        $this->validateCsrf();
        $this->documents->lierSociete(
            (int)$id,
            (int)$this->post('ldj_societe_id', 0),
            !empty($this->post('ldj_est_obligatoire')),
            (int)$this->post('ldj_priorite', 100),
            ((int)$this->post('ldj_statut_id', 0)) ?: null
        );
        $this->flash()->success('Société liée au document.');
        $this->redirect('/legal-documents/' . (int)$id);
    }

    public function accept(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();
        $document = $this->documents->trouver((int)$id);
        if (!$document) {
            $this->flash()->error('Document introuvable.');
            $this->redirect('/legal-documents');
        }
        $this->documents->accepter((int)$id, (int)$this->userId(), (string)($document['dju_version'] ?? '1'), client_ip(), (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $this->flash()->success('Acceptation enregistrée.');
        $this->redirect('/legal-documents/' . (int)$id);
    }
}
