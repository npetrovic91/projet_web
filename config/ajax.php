<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Acces direct interdit.');

defined('AJAX_REQUEST_HEADER') || define('AJAX_REQUEST_HEADER', 'HTTP_X_REQUESTED_WITH');
defined('AJAX_REQUEST_VALUE') || define('AJAX_REQUEST_VALUE', 'XMLHttpRequest');
defined('AJAX_HEADER_NAME') || define('AJAX_HEADER_NAME', 'X-Requested-With');
defined('AJAX_HEADER_VALUE') || define('AJAX_HEADER_VALUE', 'XMLHttpRequest');
defined('AJAX_CSRF_HEADER') || define('AJAX_CSRF_HEADER', 'HTTP_X_CSRF_TOKEN');
defined('AJAX_CSRF_FIELD') || define('AJAX_CSRF_FIELD', defined('CSRF_FORM_FIELD') ? CSRF_FORM_FIELD : '_csrf_token');
defined('AJAX_RESPONSE_VERSION') || define('AJAX_RESPONSE_VERSION', '1.0');
defined('AJAX_MAX_PAGE_SIZE') || define('AJAX_MAX_PAGE_SIZE', 100);
defined('AJAX_DEFAULT_PAGE_SIZE') || define('AJAX_DEFAULT_PAGE_SIZE', 25);
defined('AJAX_CACHE_SECONDS') || define('AJAX_CACHE_SECONDS', 0);
