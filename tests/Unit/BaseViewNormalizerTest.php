<?php
declare(strict_types=1);

use Nenad\Autosav\Core\View\ViewDataNormalizer;

require_once dirname(__DIR__) . '/bootstrap.php';

$ligne = ViewDataNormalizer::societe([
    'soc_id' => 7,
    'soc_nom' => 'Garage Test',
    'soc_code_postal' => '75001',
]);

assert($ligne['com_id'] === 7);
assert($ligne['com_name'] === 'Garage Test');
assert($ligne['com_postal_code'] === '75001');
assert($ligne['soc_nom'] === 'Garage Test');

echo "BaseViewNormalizerTest SUCCESS\n";
