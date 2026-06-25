<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Referentiels\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Referentiels\Services\ReferentielService;

class ReferentielController extends BaseController
{
    private ReferentielService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ReferentielService();
    }

    public function index(): void
    {
        $this->requirePermission('referentiels.read');
        $this->requireExportAllowed();
        $this->render('Referentiels/Views/index', [
            'data' => $this->service->tableauDeBord(),
            'pageTitle' => 'Référentiels système',
        ]);
    }

    public function exportJson(): void
    {
        $this->requirePermission('referentiels.read');
        $this->requireExportAllowed();
        $this->json(true, $this->service->export(), 'Export des référentiels système.');
    }

    public function pays(): void { $this->liste('pays', 'Pays'); }
    public function devises(): void { $this->liste('devises', 'Devises'); }
    public function fuseaux(): void { $this->liste('fuseaux', 'Fuseaux horaires'); }
    public function tva(): void { $this->liste('tva', 'Taux de TVA'); }
    public function statuts(): void { $this->liste('statuts', 'Statuts'); }
    public function transitions(): void { $this->liste('transitions', 'Transitions de statuts'); }

    public function create(string $type): void
    {
        $this->requirePermission('referentiels.manage');
        $this->render('Referentiels/Views/form', $this->formData($type, null) + [
            'pageTitle' => 'Créer un référentiel',
        ]);
    }

    public function edit(string $type, string $id): void
    {
        $this->requirePermission('referentiels.manage');
        $row = $this->service->trouver($this->normaliserType($type), (int) $id);
        if (!$row) {
            $this->flash()->error('Élément introuvable.');
            $this->redirect('/referentiels/' . $this->normaliserType($type));
        }
        $this->render('Referentiels/Views/form', $this->formData($type, $row) + [
            'pageTitle' => 'Modifier un référentiel',
        ]);
    }

    public function store(string $type): void
    {
        $this->save($type, null);
    }

    public function update(string $type, string $id): void
    {
        $this->save($type, (int) $id);
    }

    public function delete(string $type, string $id): void
    {
        $this->requirePermission('referentiels.manage');
        $this->validateCsrf();
        $type = $this->normaliserType($type);
        try {
            $this->service->supprimer($type, (int) $id, (int) $this->userId(), client_ip());
            $this->flash()->success('Suppression logique effectuée.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/referentiels/' . $type);
    }

    private function save(string $type, ?int $id): void
    {
        $this->requirePermission('referentiels.manage');
        $this->validateCsrf();
        $type = $this->normaliserType($type);
        try {
            $savedId = $this->service->enregistrer($type, $_POST, $id, (int) $this->userId(), client_ip());
            $this->flash()->success($id ? 'Élément mis à jour.' : 'Élément créé.');
            $this->redirect('/referentiels/' . $type . '/' . $savedId . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect($id ? '/referentiels/' . $type . '/' . $id . '/edit' : '/referentiels/' . $type . '/create');
        }
    }

    private function liste(string $type, string $title): void
    {
        $this->requirePermission('referentiels.read');
        $this->requireExportAllowed();
        $filters = [
            'q' => (string) $this->get('q', ''),
            'domaine' => (string) $this->get('domaine', ''),
            'pays_id' => (string) $this->get('pays_id', ''),
        ];
        $rows = match ($type) {
            'pays' => $this->service->pays($filters),
            'devises' => $this->service->devises($filters),
            'fuseaux' => $this->service->fuseaux($filters),
            'tva' => $this->service->tauxTva($filters),
            'statuts' => $this->service->statuts($filters),
            'transitions' => $this->service->transitions($filters),
            default => [],
        };
        $this->render('Referentiels/Views/list', [
            'type' => $type,
            'title' => $title,
            'rows' => $rows,
            'filters' => $filters,
            'domaines' => $this->service->domainesStatuts(),
            'pays' => $this->service->pays(['actifs' => true]),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => $title,
        ]);
    }

    private function formData(string $type, ?array $row): array
    {
        $type = $this->normaliserType($type);
        return [
            'type' => $type,
            'row' => $row,
            'pays' => $this->service->pays(['actifs' => true]),
            'statuts' => $this->service->statutsPourSelect(),
            'domaines' => $this->service->domainesStatuts(),
            'csrf_token' => $this->csrfToken(),
        ];
    }

    private function normaliserType(string $type): string
    {
        $type = strtolower(trim($type));
        $aliases = [
            'pays' => 'pays', 'devises' => 'devises', 'devise' => 'devises',
            'fuseaux' => 'fuseaux', 'fuseaux-horaires' => 'fuseaux',
            'tva' => 'tva', 'statuts' => 'statuts', 'transitions' => 'transitions',
        ];
        if (!isset($aliases[$type])) {
            throw new \InvalidArgumentException('Type de référentiel inconnu.');
        }
        return $aliases[$type];
    }
}
