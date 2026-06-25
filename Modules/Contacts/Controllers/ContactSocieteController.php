<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Contacts\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Contacts\Services\ContactSocieteService;

class ContactSocieteController extends BaseController
{
    private ContactSocieteService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ContactSocieteService();
    }

    public function index(): void
    {
        $this->requirePermission('contacts.read');
        $societeId = $this->selectedSocieteId();
        $filters = [
            'q' => trim((string)$this->get('q', '')),
            'societe_id' => $societeId,
            'type_contact_id' => (int)$this->get('type_contact_id', 0) ?: null,
            'statut_id' => (int)$this->get('statut_id', 0) ?: null,
            'mode' => trim((string)$this->get('mode', '')),
        ];
        $this->render('Contacts/Views/index', [
            'pageTitle' => 'Contacts sociétés',
            'stats' => $this->service->tableauDeBord($societeId)['stats'],
            'contacts' => $this->service->contacts($filters),
            'filters' => $filters,
            'refs' => $this->service->references($societeId),
        ]);
    }

    public function exportJson(): void
    {
        $this->requirePermission('contacts.read');
        $this->json(true, $this->service->export($this->selectedSocieteId()), 'Export des contacts sociétés.');
    }

    public function create(): void
    {
        $this->requirePermission('contacts.manage');
        $societeId = $this->selectedSocieteId();
        $this->render('Contacts/Views/form', [
            'pageTitle' => 'Créer un contact société',
            'contact' => null,
            'refs' => $this->service->references($societeId),
            'societe_id' => $societeId,
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('contacts.manage');
        $contact = $this->service->contact((int)$id);
        if (!$contact) {
            $this->flash()->error('Contact introuvable.');
            $this->redirect('/contacts-societes');
        }
        $this->render('Contacts/Views/form', [
            'pageTitle' => 'Modifier un contact société',
            'contact' => $contact,
            'refs' => $this->service->references((int)$contact['cts_societe_id']),
            'societe_id' => (int)$contact['cts_societe_id'],
        ]);
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update(string $id): void
    {
        $this->save((int)$id);
    }

    public function delete(string $id): void
    {
        $this->requirePermission('contacts.manage');
        $this->validateCsrf();
        try {
            $this->service->supprimerContact((int)$id, $this->userId(), client_ip());
            $this->flash()->success('Contact supprimé logiquement.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/contacts-societes');
    }

    public function types(): void
    {
        $this->requirePermission('contacts.read');
        $filters = ['q' => trim((string)$this->get('q', ''))];
        $this->render('Contacts/Views/types', [
            'pageTitle' => 'Types de contacts',
            'types' => $this->service->types($filters),
            'filters' => $filters,
        ]);
    }

    public function createType(): void
    {
        $this->requirePermission('contacts.manage');
        $this->render('Contacts/Views/type_form', [
            'pageTitle' => 'Créer un type de contact',
            'type' => null,
            'statuts' => $this->service->references()['statuts'],
        ]);
    }

    public function editType(string $id): void
    {
        $this->requirePermission('contacts.manage');
        $type = $this->service->type((int)$id);
        if (!$type) {
            $this->flash()->error('Type de contact introuvable.');
            $this->redirect('/contacts-societes/types');
        }
        $this->render('Contacts/Views/type_form', [
            'pageTitle' => 'Modifier un type de contact',
            'type' => $type,
            'statuts' => $this->service->references()['statuts'],
        ]);
    }

    public function storeType(): void
    {
        $this->saveType(null);
    }

    public function updateType(string $id): void
    {
        $this->saveType((int)$id);
    }

    public function deleteType(string $id): void
    {
        $this->requirePermission('contacts.manage');
        $this->validateCsrf();
        try {
            $this->service->supprimerType((int)$id, $this->userId(), client_ip());
            $this->flash()->success('Type de contact supprimé logiquement.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/contacts-societes/types');
    }

    private function save(?int $id): void
    {
        $this->requirePermission('contacts.manage');
        $this->validateCsrf();
        try {
            $savedId = $this->service->enregistrerContact($_POST, $id, $this->userId(), client_ip());
            $this->flash()->success($id ? 'Contact modifié.' : 'Contact créé.');
            $this->redirect('/contacts-societes/' . $savedId . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect($id ? '/contacts-societes/' . $id . '/edit' : '/contacts-societes/create');
        }
    }

    private function saveType(?int $id): void
    {
        $this->requirePermission('contacts.manage');
        $this->validateCsrf();
        try {
            $this->service->enregistrerType($_POST, $id, $this->userId(), client_ip());
            $this->flash()->success($id ? 'Type de contact modifié.' : 'Type de contact créé.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/contacts-societes/types');
    }

    private function selectedSocieteId(): ?int
    {
        $id = (int)$this->get('societe_id', $this->activeCompanyId() ?? 0);
        return $id > 0 ? $id : null;
    }
}
