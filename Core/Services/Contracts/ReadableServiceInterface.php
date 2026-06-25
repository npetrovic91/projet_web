<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Services\Contracts;

/** Contrat recommande pour les services exposant une liste paginee ou filtree. */
interface ReadableServiceInterface extends ServiceInterface
{
    public function lister(array $filtres = []): array;
}
