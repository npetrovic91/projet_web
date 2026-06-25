<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Auth\Models\AuthModel;

class TermsAjaxController extends AjaxController
{
    /**
     * Corrigé le 2026-06-25 : instanciait Nenad\Autosav\Modules\Auth\Models\
     * TermsVersionModel, une classe qui n'existe nulle part dans le projet
     * (détecté par PHPStan niveau 0) — cet endpoint plantait au premier
     * appel. Le vrai mécanisme CGU/conditions (utilisé par
     * AuthService::accepterDernieresConditions) repose sur
     * sav_documents_juridiques via AuthModel::trouverDernierDocumentJuridique().
     */
    public function status(): void
    {
        $model = new AuthModel();
        $document = $model->trouverDernierDocumentJuridique('cgu')
            ?? $model->trouverDernierDocumentJuridique('conditions_generales')
            ?? $model->trouverDernierDocumentJuridique('terms');

        AjaxResponseService::success('Statut CGU chargé.', [
            'pending' => (bool) ($_SESSION['terms_pending'] ?? false),
            'version' => $document['dju_version'] ?? null,
            'content' => $document['dju_contenu'] ?? null,
        ]);
    }
}
