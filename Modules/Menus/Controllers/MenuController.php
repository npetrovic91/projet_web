<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Menus\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Modules\Menus\Services\MenuService;

class MenuController extends BaseController
{
    private MenuService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new MenuService();
    }

    public function index(): void
    {
        $this->requirePermission('menus.read');
        $societeId = $this->societeActiveId();
        $this->render('Menus/Views/index', [
            'data' => $this->service->tableauDeBord($societeId),
            'pageTitle' => 'Menus dynamiques',
        ]);
    }

    public function menus(): void
    {
        $this->requirePermission('menus.read');
        $filters = $this->filters();
        $this->render('Menus/Views/menus', [
            'rows' => $this->service->menus($filters),
            'filters' => $filters,
            'listes' => $this->service->listes(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Menus',
        ]);
    }

    public function elements(): void
    {
        $this->requirePermission('menus.read');
        $filters = $this->filters();
        $this->render('Menus/Views/elements', [
            'rows' => $this->service->elements($filters),
            'filters' => $filters,
            'listes' => $this->service->listes(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Éléments de menu',
        ]);
    }

    public function create(): void
    {
        $this->requirePermission('menus.manage');
        $this->render('Menus/Views/form_menu', [
            'row' => null,
            'listes' => $this->service->listes(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Créer un menu',
        ]);
    }

    public function edit(string $id): void
    {
        $this->requirePermission('menus.manage');
        $row = $this->service->trouverMenu((int) $id);
        if (!$row) {
            $this->flash()->error('Menu introuvable.');
            $this->redirect('/menus');
        }
        $this->render('Menus/Views/form_menu', [
            'row' => $row,
            'listes' => $this->service->listes(),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Modifier un menu',
        ]);
    }

    public function store(): void { $this->saveMenu(null); }
    public function update(string $id): void { $this->saveMenu((int) $id); }

    public function delete(string $id): void
    {
        $this->requirePermission('menus.manage');
        $this->validateCsrf();
        try {
            $this->service->supprimerMenu((int) $id, $this->userId(), client_ip());
            $this->flash()->success('Menu supprimé logiquement.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/menus');
    }

    public function createElement(): void
    {
        $this->requirePermission('menus.manage');
        $listes = $this->service->listes();
        $this->render('Menus/Views/form_element', [
            'row' => null,
            'listes' => $listes,
            'parents' => $listes['parents'],
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Créer un élément de menu',
        ]);
    }

    public function editElement(string $id): void
    {
        $this->requirePermission('menus.manage');
        $row = $this->service->trouverElement((int) $id);
        if (!$row) {
            $this->flash()->error('Élément de menu introuvable.');
            $this->redirect('/menus/elements');
        }
        $listes = $this->service->listes();
        $this->render('Menus/Views/form_element', [
            'row' => $row,
            'listes' => $listes,
            'parents' => $this->service->parentsPossibles((int) $row['eme_menu_id'], (int) $row['eme_id']),
            'csrf_token' => $this->csrfToken(),
            'pageTitle' => 'Modifier un élément de menu',
        ]);
    }

    public function storeElement(): void { $this->saveElement(null); }
    public function updateElement(string $id): void { $this->saveElement((int) $id); }

    public function deleteElement(string $id): void
    {
        $this->requirePermission('menus.manage');
        $this->validateCsrf();
        try {
            $this->service->supprimerElement((int) $id, $this->userId(), client_ip());
            $this->flash()->success('Élément supprimé logiquement.');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
        }
        $this->redirect('/menus/elements');
    }

    public function navigationJson(): void
    {
        $this->requirePermission('menus.read');
        $this->json(true, $this->service->navigation($this->societeActiveId()), 'Navigation active filtrée par société, modules et permissions.');
    }

    public function exportJson(): void
    {
        $this->requirePermission('menus.read');
        $this->json(true, $this->service->export($this->societeActiveId()), 'Export des menus dynamiques.');
    }

    private function saveMenu(?int $id): void
    {
        $this->requirePermission('menus.manage');
        $this->validateCsrf();
        try {
            $saved = $this->service->enregistrerMenu($_POST, $id, $this->userId(), client_ip());
            $this->flash()->success($id ? 'Menu mis à jour.' : 'Menu créé.');
            $this->redirect('/menus/' . $saved . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect($id ? '/menus/' . $id . '/edit' : '/menus/create');
        }
    }

    private function saveElement(?int $id): void
    {
        $this->requirePermission('menus.manage');
        $this->validateCsrf();
        try {
            $saved = $this->service->enregistrerElement($_POST, $id, $this->userId(), client_ip());
            $this->flash()->success($id ? 'Élément de menu mis à jour.' : 'Élément de menu créé.');
            $this->redirect('/menus/elements/' . $saved . '/edit');
        } catch (\Throwable $e) {
            $this->flash()->error($e->getMessage());
            $this->redirect($id ? '/menus/elements/' . $id . '/edit' : '/menus/elements/create');
        }
    }

    private function filters(): array
    {
        return [
            'q' => (string) $this->get('q', ''),
            'module_id' => (string) $this->get('module_id', ''),
            'menu_id' => (string) $this->get('menu_id', ''),
        ];
    }

    private function societeActiveId(): ?int
    {
        $value = $_SESSION['active_company_id'] ?? $_SESSION['user']['actual_company_id'] ?? $_SESSION['user']['actual_society_id'] ?? null;
        return $value ? (int) $value : null;
    }
}
