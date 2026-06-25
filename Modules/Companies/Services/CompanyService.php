<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Companies\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Brands\Services\BrandService;
use Nenad\Autosav\Modules\Society\Services\ServiceSocietes;

class CompanyService implements ServiceInterface{
    private ServiceSocietes $societes;
    private BrandService $brands;

    public function __construct()
    {
        $this->societes = new ServiceSocietes();
        $this->brands = new BrandService();
    }

    public function listCompanies(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $result = $this->societes->lister([
            'type' => $filters['type_code'] ?? $filters['type'] ?? '',
            'recherche' => $filters['search'] ?? '',
            'active' => $filters['is_active'] ?? '',
        ], $page, $perPage);
        return ['rows' => $result['lignes'], 'total' => $result['total'], 'page' => $result['page'], 'pages' => $result['pages'], 'perPage' => $result['par_page']];
    }

    public function getCompanyFull(int $id): ?array
    {
        $fiche = $this->societes->ficheComplete($id);
        if (!$fiche) {
            return null;
        }
        return ['company' => $fiche['societe'], 'relations' => $fiche['relations'], 'brands' => $fiche['marques']];
    }

    public function getTypes(): array { return $this->societes->types(); }
    public function getHoldings(): array { return $this->societes->holdings(); }

    public function saveCompany(?int $id, array $input, int $userId): array
    {
        return $this->societes->enregistrer($id, $input, $userId);
    }

    public function deleteCompany(int $id, int $userId): bool
    {
        return $this->societes->supprimer($id, $userId);
    }

    public function addRelation(int $parentId, int $childId, string $type, int $userId): array
    {
        return $this->societes->ajouterRelation($parentId, $childId, $type, $userId);
    }

    public function removeRelation(int $id, int $userId): bool
    {
        return $this->societes->retirerRelation($id, $userId);
    }

    public function attachBrand(int $companyId, int $brandId, int $userId, bool $primary = false): bool
    {
        return $this->brands->attachToCompany($companyId, $brandId, $userId, $primary);
    }

    public function detachBrand(int $companyId, int $brandId, int $userId): bool
    {
        return $this->brands->detachFromCompany($companyId, $brandId, $userId);
    }
}
