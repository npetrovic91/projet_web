<?php
declare(strict_types=1);

/**
 * AUTOSAV — Convention de retour formalisée pour les Services (2.3)
 *
 * Avant ce trait, ~11 des 44 Services réinventaient le même tableau
 * ['success'=>bool,'message'=>...,'errors'=>...] par copie, sans
 * définition centrale. Ce test vérifie que le trait existe, expose ok()/
 * fail() avec le shape attendu, et qu'il est utilisable (test
 * fonctionnel direct, pas seulement une vérification de présence de
 * texte).
 */

$root = dirname(__DIR__, 2);
$path = $root . '/Core/Services/Concerns/ServiceResponse.php';
$source = file_get_contents($path) ?: '';
assert($source !== '', 'Core/Services/Concerns/ServiceResponse.php introuvable.');
assert(str_contains($source, 'trait ServiceResponse'), 'ServiceResponse doit être un trait.');

require_once $path;

$harness = new class {
    use \Nenad\Autosav\Core\Services\Concerns\ServiceResponse;
    public function okPublic(array $extra = [], string $message = ''): array { return $this->ok($extra, $message); }
    public function failPublic(string $message, array $errors = []): array { return $this->fail($message, $errors); }
};

$ok = $harness->okPublic(['id' => 42]);
assert($ok['success'] === true, 'ok() doit retourner success=true.');
assert($ok['id'] === 42, 'ok() doit fusionner les données supplémentaires (id).');
assert($ok['errors'] === [], 'ok() doit retourner errors=[] par défaut.');

$fail = $harness->failPublic('Code invalide.');
assert($fail['success'] === false, 'fail() doit retourner success=false.');
assert($fail['message'] === 'Code invalide.', 'fail() doit conserver le message fourni.');
assert($fail['errors'] === ['Code invalide.'], 'fail() doit retomber sur [message] si aucune erreur détaillée n\'est fournie.');

echo "ServiceResponseConventionTest SUCCESS\n";
