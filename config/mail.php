<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Acces direct interdit.');

defined('MAIL_HOST') || define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.hostinger.com');
defined('MAIL_PORT') || define('MAIL_PORT', (int) (getenv('MAIL_PORT') ?: 587));
defined('MAIL_ENCRYPTION') || define('MAIL_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls');
defined('MAIL_USERNAME') || define('MAIL_USERNAME', getenv('MAIL_USERNAME') ?: 'noreply@autosav.fr');
defined('MAIL_PASSWORD') || define('MAIL_PASSWORD', getenv('MAIL_PASSWORD') ?: '');
defined('MAIL_FROM_EMAIL') || define('MAIL_FROM_EMAIL', getenv('MAIL_FROM_EMAIL') ?: 'noreply@autosav.fr');
defined('MAIL_FROM_NAME') || define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'Autosav');
defined('MAIL_REPLY_TO') || define('MAIL_REPLY_TO', getenv('MAIL_REPLY_TO') ?: MAIL_FROM_EMAIL);
defined('MAIL_CHARSET') || define('MAIL_CHARSET', 'UTF-8');
defined('MAIL_DEBUG') || define('MAIL_DEBUG', 0);
