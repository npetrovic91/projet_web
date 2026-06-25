<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

$bootstrap = file_get_contents($root . '/bootstrap.php') ?: '';
$emailService = file_get_contents($root . '/Modules/Emails/Services/EmailService.php') ?: '';
$settingsModel = file_get_contents($root . '/Modules/Settings/Models/ApplicationSettingModel.php') ?: '';
$fileModel = file_get_contents($root . '/Modules/Files/Models/FileModel.php') ?: '';
$fileService = file_get_contents($root . '/Modules/Files/Services/FileService.php') ?: '';
$theme = file_get_contents($root . '/Core/Theme/Vue.php') ?: '';
$navbar = file_get_contents($root . '/Core/Theme/Components/Navbar.php') ?: '';
$alerts = file_get_contents($root . '/Core/Alertes/SweetAlertGenerator.php') ?: '';
$bulkMailController = file_get_contents($root . '/Modules/BulkMail/Controllers/BulkMailController.php') ?: '';
$bulkMailService = file_get_contents($root . '/Modules/BulkMail/Services/BulkMailService.php') ?: '';
$dashboardService = file_get_contents($root . '/Modules/Dashboard/Services/DashboardService.php') ?: '';
$maintenance = file_get_contents($root . '/Core/Middleware/MaintenanceMiddleware.php') ?: '';

assert(str_contains($bootstrap, "require_once AUTOSAV_ROOT . '/config/sessions.php'"));
assert(str_contains($bootstrap, 'SessionHandler::init()'));
assert(!str_contains($emailService, 'autosav-local-secret'));
assert(str_contains($emailService, "defined('ENCRYPTION_KEY')"));
assert(!str_contains($settingsModel, "defined('DB_NAME') ? DB_NAME : 'autosav'"));
assert(str_contains($fileService, 'private function scope(): array'));
assert(str_contains($fileModel, 'private function addAccessScope'));
assert(str_contains($fileModel, "access_link.lfi_cible_type = \\'societe\\'"));
assert(str_contains($fileModel, "new Encryption())->encrypt"));
assert(str_contains($fileModel, "'societe', \$companyId, 'contexte_societe'"));
assert(str_contains($theme, 'CspNonce::attribute()'));
assert(str_contains($navbar, '<script<?= CspNonce::attribute() ?>>'));
assert(str_contains($alerts, "'<script' . CspNonce::attribute()"));
assert(substr_count($bulkMailController, "requirePermission('notifications.manage')") === 7);
assert(str_contains($bulkMailService, "\$filters = ['company_id' => \$companyId]"));
assert(str_contains($dashboardService, "\$context['recent_security_attempts'] = \$globalScope ?"));
assert(str_contains($maintenance, "str_starts_with(\$uri, '/auth/')"));

echo "CrossCuttingHardeningTest SUCCESS\n";
