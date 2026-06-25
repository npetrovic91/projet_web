<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Administration\Controllers;

use Nenad\Autosav\Modules\Roles\Controllers\RoleController;

/**
 * Compatibilite : les anciennes routes Administration/Roles utilisent
 * désormais le module Roles aligné sur sav_roles / sav_permissions.
 */
class RolesController extends RoleController
{
}
