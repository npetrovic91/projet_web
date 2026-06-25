<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Companies\Controllers;

/**
 * Compatibilite des anciennes routes /companies.
 * Le module metier de reference est desormais Society, aligne sur sav_societes.
 */
class CompanyController extends \Nenad\Autosav\Modules\Society\Controllers\SocieteController
{
    public function index(): void { parent::index(); }
    public function create(): void { parent::create(); }
    public function store(): void { parent::store(); }
    public function show(string $id): void { parent::show($id); }
    public function edit(string $id): void { parent::edit($id); }
    public function update(string $id): void { parent::update($id); }
    public function delete(string $id): void { parent::delete($id); }
    public function restore(string $id): void { parent::restore($id); }
    public function addRelation(): void { parent::addRelation(); }
    public function removeRelation(string $id): void { parent::removeRelation($id); }
    public function attachBrand(): void { parent::attachBrand(); }
    public function detachBrand(): void { parent::detachBrand(); }

    // ── LOT40 : infos complémentaires ───────────────────────────────
    public function saveInfosCompl(string $id): void { parent::saveInfosCompl($id); }

    // ── LOT40 : comptes bancaires ────────────────────────────────────
    public function addCompteBancaire(string $id): void { parent::addCompteBancaire($id); }
    public function deleteCompteBancaire(string $id, string $cid): void { parent::deleteCompteBancaire($id, $cid); }

    // ── LOT40 : mandats SEPA ─────────────────────────────────────────
    public function addMandat(string $id): void { parent::addMandat($id); }
    public function updateMandat(string $id, string $mid): void { parent::updateMandat($id, $mid); }
    public function deleteMandat(string $id, string $mid): void { parent::deleteMandat($id, $mid); }

    // ── LOT40 : parc véhicules ───────────────────────────────────────
    public function addVehicule(string $id): void { parent::addVehicule($id); }
    public function deleteVehicule(string $id, string $vid): void { parent::deleteVehicule($id, $vid); }
}