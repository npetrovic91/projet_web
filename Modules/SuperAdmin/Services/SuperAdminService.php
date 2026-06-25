<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\SuperAdmin\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\SuperAdmin\Models\SuperAdminModel;

final class SuperAdminService implements ServiceInterface{
    private SuperAdminModel $model;

    public function __construct(?SuperAdminModel $model = null)
    {
        $this->model = $model ?? new SuperAdminModel();
    }

    public function tableauDeBord(): array
    {
        return [
            'indicateurs' => $this->model->indicateurs(),
            'maintenance' => $this->model->etatMaintenance(),
            'modules' => $this->model->modulesInstalles(),
            'parametres' => $this->model->parametresCritiques(),
            'alertes' => $this->model->dernieresAlertes(),
        ];
    }

    public function export(): array
    {
        $data = $this->tableauDeBord();
        foreach ($data['parametres'] as &$parametre) {
            if ((int)($parametre['pap_est_secret'] ?? 0) === 1) {
                $parametre['pap_valeur_json'] = '[SECRET MASQUÉ]';
            }
        }
        return $data;
    }
}
