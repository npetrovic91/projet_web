<?php
declare(strict_types=1);

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

require_once dirname(__DIR__) . '/bootstrap.php';

$services = glob(SRC_PATH . '/Modules/*/Services/*.php') ?: [];
$erreurs = [];
foreach ($services as $fichier) {
    $contenu = file_get_contents($fichier) ?: '';
    if (!str_contains($contenu, 'implements ServiceInterface')) {
        $erreurs[] = str_replace(SRC_PATH . '/', '', $fichier);
    }
}

assert($erreurs === [], 'Services sans contrat: ' . implode(', ', $erreurs));
assert(interface_exists(ServiceInterface::class));

echo "ServiceContractTest SUCCESS\n";
