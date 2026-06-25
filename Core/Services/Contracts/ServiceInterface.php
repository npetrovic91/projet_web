<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Services\Contracts;

/**
 * Contrat minimal commun a tous les services applicatifs AUTOSAV.
 *
 * Cette interface est volontairement non intrusive : elle sert de point
 * d'ancrage pour l'injection, l'inventaire, les AutoTests et les futurs
 * contrats specialises sans imposer une refonte des services existants.
 */
interface ServiceInterface
{
}
