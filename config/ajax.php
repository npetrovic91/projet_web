<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Acces direct interdit.');

define('AJAX_REQUEST_HEADER', 'HTTP_X_REQUESTED_WITH');
define('AJAX_REQUEST_VALUE', 'XMLHttpRequest');
define('AJAX_HEADER_VALUE', 'XMLHttpRequest');
define('AJAX_CSRF_HEADER', 'HTTP_X_CSRF_TOKEN');
define('AJAX_CSRF_FIELD', defined('CSRF_FORM_FIELD') ? CSRF_FORM_FIELD : '_csrf_token');
define('AJAX_RESPONSE_VERSION', '1.0');
define('AJAX_MAX_PAGE_SIZE', 100);
define('AJAX_DEFAULT_PAGE_SIZE', 25);
define('AJAX_CACHE_SECONDS', 0);
