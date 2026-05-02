<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;
use Nenad\Autosav\Modules\Auth\Models\TermsVersionModel;

class TermsAjaxController extends AjaxController
{
    public function status(): void
    {
        $version = (new TermsVersionModel())->findCurrent();
        AjaxResponseService::success('Statut CGU charge.', [
            'pending' => (bool) ($_SESSION['terms_pending'] ?? false),
            'version' => $version['trv_version'] ?? null,
            'content' => $version['trv_content'] ?? null,
        ]);
    }
}
