<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Organisation\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Organisation\Services\OrganisationService;

class OrganisationController extends BaseController
{
    private OrganisationService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new OrganisationService();
    }

    public function index(): void
    {
        $this->requirePermission('organisation.read');
        $societeId = $this->selectedSocieteId();
        $this->render('Organisation/Views/index', [
            'pageTitle' => 'Organisation interne',
            'data' => $this->service->tableauDeBord($societeId),
            'societe_id' => $societeId,
            'societes' => $this->service->societes(),
            'types' => $this->service->typeLabels(),
        ]);
    }

    public function exportJson(): void
    {
        $this->requirePermission('organisation.read');
        $this->json(true, $this->service->export($this->selectedSocieteId()), 'Export de l’organisation interne.');
    }

    public function list(string $type): void
    {
        $this->requirePermission('organisation.read');
        $type = $this->normaliserType($type);
        $filters = [
            'q' => trim((string)$this->get('q', '')),
            'societe_id' => (int)$this->get('societe_id', $this->activeCompanyId() ?? 0),
        ];
        if ($filters['societe_id'] <= 0) { unset($filters['societe_id']); }
        $labels = $this->service->typeLabels();
        $this->render('Organisation/Views/list', [
            'pageTitle' => $labels[$type] ?? 'Structures',
            'type' => $type,
            'title' => $labels[$type] ?? 'Structures',
            'rows' => $this->service->entites($type, $filters),
            'filters' => $filters,
            'societes' => $this->service->societes(),
        ]);
    }

    public function create(string $type): void
    {
        $this->requirePermission('organisation.manage');
        $type = $this->normaliserType($type);
        $societeId = $this->selectedSocieteId();
        $this->render('Organisation/Views/form', [
            'pageTitle' => 'Créer une structure',
            'type' => $type,
            'row' => null,
            'societes' => $this->service->societes(),
            'societe_id' => $societeId,
            'utilisateurs' => $this->service->utilisateurs($societeId),
            'selected_user_ids' => [],
            'responsable_user_id' => null,
            'statuts' => $this->service->statuts(),
            'labels' => $this->service->typeLabels(),
        ]);
    }

    public function edit(string $type, string $id): void
    {
        $this->requirePermission('organisation.manage');
        $type = $this->normaliserType($type);
        $row = $this->service->trouverEntite($type, (int)$id);
        if (!$row) {
            $this->flash()->error('Structure introuvable.');
            $this->redirect('/organisation/' . $type);
        }
        $this->render('Organisation/Views/form', [
            'pageTitle' => 'Modifier une structure',
            'type' => $type,
            'row' => $row,
            'societes' => $this->service->societes(),
            'societe_id' => (int)($row[$this->societeColumn($type)] ?? 0),
            'utilisateurs' => $this->service->utilisateurs((int)($row[$this->societeColumn($type)] ?? 0) ?: null),
            'selected_user_ids' => array_column($this->service->affectations($type, (int)$id), 'utilisateur_id'),
            'responsable_user_id' => (int)($row[$this->responsableColumn($type)] ?? 0) ?: null,
            'statuts' => $this->service->statuts(),
            'labels' => $this->service->typeLabels(),
        ]);
    }

    public function store(string $type): void
    {
        $this->save($type, null);
    }

    public function update(string $type, string $id): void
    {
        $this->save($type, (int)$id);
    }

    public function delete(string $type, string $id): void
    {
        $this->requirePermission('organisation.manage');
        $this->validateCsrf();
        $type = $this->normaliserType($type);
        try {
            $this->service->supprimerEntite($type, (int)$id, $this->userId(), client_ip());
            $this->flash()->success('Structure supprimée logiquement.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/organisation/' . $type);
    }

    public function liaisons(): void
    {
        $this->requirePermission('organisation.read');
        $societeId = $this->selectedSocieteId();
        $type = trim((string)$this->get('type', ''));
        $liaisonLabels = $this->service->liaisonLabels();
        if ($type === '' || !isset($liaisonLabels[$type])) {
            $type = 'departements-secteurs';
        }
        $this->render('Organisation/Views/liaisons', [
            'pageTitle' => 'Liaisons hiérarchiques',
            'rows' => $this->service->liaisons(['type' => (string)$this->get('filtre_type', '')], $societeId),
            'societes' => $this->service->societes(),
            'societe_id' => $societeId,
            'liaison_type' => $type,
            'liaisonLabels' => $liaisonLabels,
            'options' => $this->service->optionsPourLiaison($type, $societeId),
            'statuts' => $this->service->statuts(),
        ]);
    }

    public function storeLiaison(): void
    {
        $this->requirePermission('organisation.manage');
        $this->validateCsrf();
        $type = $this->normaliserLiaison((string)$this->post('type_liaison', ''));
        try {
            $this->service->enregistrerLiaison($type, $_POST, $this->userId(), client_ip());
            $this->flash()->success('Liaison créée.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/organisation/liaisons?type=' . urlencode($type));
    }

    public function deleteLiaison(string $type, string $id): void
    {
        $this->requirePermission('organisation.manage');
        $this->validateCsrf();
        $type = $this->normaliserLiaison($type);
        try {
            $this->service->supprimerLiaison($type, (int)$id, $this->userId(), client_ip());
            $this->flash()->success('Liaison supprimée logiquement.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/organisation/liaisons');
    }

    private function save(string $type, ?int $id): void
    {
        $this->requirePermission('organisation.manage');
        $this->validateCsrf();
        $type = $this->normaliserType($type);
        try {
            $this->service->enregistrerEntite($type, $_POST, $id, $this->userId(), client_ip());
            $this->flash()->success($id ? 'Structure modifiée.' : 'Structure créée.');
            $this->redirect('/organisation/' . $type);
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect($id ? '/organisation/' . $type . '/' . $id . '/edit' : '/organisation/' . $type . '/create');
        }
    }

    private function selectedSocieteId(): ?int
    {
        $id = (int)$this->get('societe_id', $this->activeCompanyId() ?? 0);
        return $id > 0 ? $id : null;
    }

    private function normaliserType(string $type): string
    {
        $type = trim($type);
        $allowed = $this->service->typeLabels();
        if (!isset($allowed[$type])) {
            throw new \InvalidArgumentException('Type de structure inconnu.');
        }
        return $type;
    }

    private function societeColumn(string $type): string
    {
        return [
            'departements' => 'dep_societe_id',
            'secteurs' => 'sec_societe_id',
            'services' => 'srv_societe_id',
            'equipes' => 'equ_societe_id',
        ][$type] ?? 'societe_id';
    }

    private function responsableColumn(string $type): string
    {
        return [
            'departements' => 'dep_responsable_utilisateur_id',
            'services' => 'srv_responsable_utilisateur_id',
            'equipes' => 'equ_responsable_utilisateur_id',
        ][$type] ?? 'responsable_utilisateur_id';
    }

    private function normaliserLiaison(string $type): string
    {
        $type = trim($type);
        $allowed = $this->service->liaisonLabels();
        if (!isset($allowed[$type])) {
            throw new \InvalidArgumentException('Type de liaison inconnu.');
        }
        return $type;
    }
}
