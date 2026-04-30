<?php
declare(strict_types=1);
defined('AUTOSAV_ROOT') or die('Acces direct interdit.');

defined('TERMS_CURRENT_VERSION') || define('TERMS_CURRENT_VERSION', '1.0');
defined('TERMS_FORCE_EACH_LOGIN') || define('TERMS_FORCE_EACH_LOGIN', false);
defined('TERMS_MODAL_TITLE') || define('TERMS_MODAL_TITLE', 'Conditions Generales d\'Utilisation');
defined('TERMS_REFUSE_REDIRECT_URL') || define('TERMS_REFUSE_REDIRECT_URL', '/');
defined('TERMS_REFUSE_FLASH_MSG') || define(
    'TERMS_REFUSE_FLASH_MSG',
    'Vous devez accepter les conditions d\'utilisation pour acceder a l\'application.'
);
defined('TERMS_FALLBACK_CONTENT') || define(
    'TERMS_FALLBACK_CONTENT',
    '<p>Ces conditions encadrent l\'utilisation de l\'application AutoSAV. L\'acces est reserve aux utilisateurs autorises. Toute action sensible peut etre journalisee pour securite, audit et conformite RGPD.</p>'
);
