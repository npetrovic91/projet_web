<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Acces direct interdit.');

define('DASHBOARD_MAX_WIDGETS', 12);
define('DASHBOARD_DEFAULT_COLUMNS', 3);
define('DASHBOARD_SKELETON_DELAY_MS', 300);
define('DASHBOARD_ASYNC_DELAY_MS', 150);
define('DASHBOARD_WIDGETS_PATH', SRC_PATH . '/Modules/Dashboard/Views/widgets/');
define('DASHBOARD_WIDGET_CACHE_TTL', 300);
define('DASHBOARD_DEFAULT_ROLE', 'USER');
