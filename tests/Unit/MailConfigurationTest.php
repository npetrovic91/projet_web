<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

$config = file_get_contents(dirname(__DIR__, 2) . '/config/mail.php') ?: '';
$forbidden = ['smtp.hostinger.com', 'infos@devnenad.fr', '+3K9@Uu1+D'];
foreach ($forbidden as $secret) {
    if (str_contains($config, $secret)) {
        fwrite(STDERR, "MailConfigurationTest NOTOK : secret ou valeur réelle détectée dans config/mail.php.\n");
        exit(1);
    }
}

foreach (['MAIL_HOST', 'MAIL_FROM_EMAIL', 'MAIL_SANDBOX', 'MAIL_FAIL_IF_UNCONFIGURED'] as $constant) {
    if (!defined($constant)) {
        fwrite(STDERR, "MailConfigurationTest NOTOK : constante {$constant} non définie.\n");
        exit(1);
    }
}

echo "MailConfigurationTest SUCCESS\n";
