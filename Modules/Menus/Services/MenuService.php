<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Menus\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Menus\Models\MenuModel;

class MenuService implements ServiceInterface{
    private MenuModel $model;

    public function __construct(?MenuModel $model = null)
    {
        $this->model = $model ?? new MenuModel();
    }

    public function tableauDeBord(?int $societeId = null): array
    {
        return $this->model->tableauDeBord($societeId);
    }

    public function menus(array $filters = []): array
    {
        return $this->model->menus($filters);
    }

    public function elements(array $filters = []): array
    {
        return $this->model->elements($filters);
    }

    public function trouverMenu(int $id): ?array
    {
        return $this->model->trouverMenu($id);
    }

    public function trouverElement(int $id): ?array
    {
        return $this->model->trouverElement($id);
    }

    public function enregistrerMenu(array $input, ?int $id, ?int $userId, ?string $ip): int
    {
        $saved = $this->model->enregistrerMenu($input, $id);
        $this->model->audit($id ? 'menu.modifier' : 'menu.creer', 'sav_menus', $saved, $userId, $ip, ['lot' => 26]);
        return $saved;
    }

    public function supprimerMenu(int $id, ?int $userId, ?string $ip): void
    {
        $this->model->supprimerMenu($id);
        $this->model->audit('menu.supprimer', 'sav_menus', $id, $userId, $ip, ['suppression_logique' => true]);
    }

    public function enregistrerElement(array $input, ?int $id, ?int $userId, ?string $ip): int
    {
        $saved = $this->model->enregistrerElement($input, $id);
        $this->model->audit($id ? 'menu.element.modifier' : 'menu.element.creer', 'sav_elements_menus', $saved, $userId, $ip, ['lot' => 26]);
        return $saved;
    }

    public function supprimerElement(int $id, ?int $userId, ?string $ip): void
    {
        $this->model->supprimerElement($id);
        $this->model->audit('menu.element.supprimer', 'sav_elements_menus', $id, $userId, $ip, ['suppression_logique' => true]);
    }

    public function navigation(?int $societeId = null): array
    {
        return $this->model->navigation($societeId);
    }

    public function export(?int $societeId = null): array
    {
        return [
            'menus' => $this->model->menus([], 1000),
            'elements' => $this->model->elements([], 2000),
            'navigation_active' => $this->model->navigation($societeId),
        ];
    }

    public function listes(): array
    {
        return [
            'menus' => $this->model->menus([], 1000),
            'modules' => $this->model->modules(),
            'permissions' => $this->model->permissions(),
            'statuts' => $this->model->statuts(),
            'parents' => $this->model->parentsPossibles(),
        ];
    }

    public function parentsPossibles(?int $menuId = null, ?int $excludeId = null): array
    {
        return $this->model->parentsPossibles($menuId, $excludeId);
    }
}
